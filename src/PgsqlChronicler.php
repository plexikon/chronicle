<?php
declare(strict_types=1);

namespace Plexikon\Chronicle;

use Generator;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Plexikon\Chronicle\Exception\ConcurrencyException;
use Plexikon\Chronicle\Exception\QueryFailure;
use Plexikon\Chronicle\Exception\StreamAlreadyExists;
use Plexikon\Chronicle\Exception\StreamNotFound;
use Plexikon\Chronicle\Stream\Stream;
use Plexikon\Chronicle\Stream\StreamName;
use Plexikon\Chronicle\Support\Connection\HasConnectionTransaction;
use Plexikon\Chronicle\Support\Connection\StreamEventLoader;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateId;
use Plexikon\Chronicle\Support\Contract\Chronicling\Model\EventStreamProvider;
use Plexikon\Chronicle\Support\Contract\Chronicling\PersistenceStrategy;
use Plexikon\Chronicle\Support\Contract\Chronicling\QueryFilter;
use Plexikon\Chronicle\Support\Contract\Chronicling\TransactionalChronicler;
use Plexikon\Chronicle\Support\Contract\Chronicling\WriteLockStrategy;

final class PgsqlChronicler implements TransactionalChronicler
{
    use HasConnectionTransaction;

    protected ConnectionInterface $connection;
    private EventStreamProvider $eventStreamProvider;
    private PersistenceStrategy $persistenceStrategy;
    private WriteLockStrategy $writeLockStrategy;
    private StreamEventLoader $streamEventLoader;
    private bool $isTransactionDisabled;

    public function __construct(ConnectionInterface $connection,
                                EventStreamProvider $eventStreamProvider,
                                PersistenceStrategy $persistenceStrategy,
                                WriteLockStrategy $writeLockStrategy,
                                StreamEventLoader $streamEventLoader,
                                bool $isTransactionDisabled)
    {
        $this->connection = $connection;
        $this->eventStreamProvider = $eventStreamProvider;
        $this->persistenceStrategy = $persistenceStrategy;
        $this->writeLockStrategy = $writeLockStrategy;
        $this->streamEventLoader = $streamEventLoader;
        $this->isTransactionDisabled = $isTransactionDisabled;
    }

    public function persistFirstCommit(Stream $stream): void
    {
        $streamName = $stream->streamName();

        $tableName = $this->persistenceStrategy->tableName($streamName);

        $this->createEventStream($streamName, $tableName);

        $this->upStreamTable($streamName, $tableName);

        $this->persist($stream);
    }

    public function persist(Stream $stream): void
    {
        if (empty($streamEvents = iterator_to_array($stream->events()))) {
            return;
        }

        $streamName = $stream->streamName();

        $tableName = $this->persistenceStrategy->tableName($streamName);

        if (!$this->writeLockStrategy->getLock($tableName)) {
            throw ConcurrencyException::failedToAcquireLock();
        }

        try {
            $data = [];
            foreach ($streamEvents as $streamEvent) {
                $data[] = $this->persistenceStrategy->serializeMessage($streamEvent);
            }

            $this->queryBuilder($streamName)->insert($data);
        } catch (QueryException $queryException) {
            if ($queryException->getCode() === '42P01') {
                throw StreamNotFound::withStreamName($streamName);
            }

            if (in_array($queryException->getCode(), ['23000', '23505'])) {
                throw ConcurrencyException::fromUnlockStreamFailure($queryException);
            }

            throw QueryFailure::fromQueryException($queryException);
        }

        $this->writeLockStrategy->releaseLock($tableName);
    }

    public function delete(StreamName $streamName): void
    {
        $result = $this->eventStreamProvider->deleteStream($streamName);

        if (!$result) {
            throw StreamNotFound::withStreamName($streamName);
        }

        $tableName = $this->persistenceStrategy->tableName($streamName);

        try {
            $this->connection->getSchemaBuilder()->drop($tableName);
        } catch (QueryException $exception) {
            throw QueryFailure::fromQueryException($exception);
        }
    }

    public function retrieveAll(AggregateId $aggregateId, StreamName $streamName, string $direction = 'asc'): Generator
    {
        return yield from $this->streamEventLoader->query(
            $this->queryBuilder($streamName)
                ->whereJsonContains('headers->__aggregate_id', $aggregateId->toString())
                ->orderBy('no', $direction),
            $streamName
        );
    }

    public function retrieveWithQueryFilter(StreamName $streamName, QueryFilter $queryFilter): Generator
    {
        $builder = $this->queryBuilder($streamName);

        $queryFilter->filterQuery()($builder);

        return yield from $this->streamEventLoader->query($builder, $streamName);
    }

    public function fetchStreamNames(StreamName ...$streamNames): array
    {
        return $this->eventStreamProvider->filterByStreamNames($streamNames);
    }

    public function hasStream(StreamName $streamName): bool
    {
        return $this->eventStreamProvider->hasRealStreamName($streamName);
    }

    protected function isTransactionDisabled(): bool
    {
        return $this->isTransactionDisabled;
    }

    private function queryBuilder(StreamName $streamName): Builder
    {
        return $this->connection->table(
            $this->persistenceStrategy->tableName($streamName)
        );
    }

    private function createEventStream(StreamName $streamName, string $tableName): void
    {
        try {
            $result = $this->eventStreamProvider->createStream($streamName, $tableName);
        } catch (QueryException $exception) {
            if (in_array($exception->getCode(), ['23000', '23505'], true)) {
                throw StreamAlreadyExists::withStreamName($streamName);
            }

            throw QueryFailure::fromQueryException($exception);
        }

        if (!$result) {
            throw new QueryFailure('Unable to insert data in event stream table');
        }
    }

    private function upStreamTable(StreamName $streamName, string $tableName): void
    {
        try {
            $this->persistenceStrategy->up($tableName);
        } catch (QueryException $exception) {
            $this->connection->getSchemaBuilder()->drop($tableName);

            $this->eventStreamProvider->deleteStream($streamName);

            throw $exception;
        }
    }
}
