<?php
declare(strict_types=1);

namespace Plexikon\Chronicle;

use Generator;
use Illuminate\Support\Collection;
use Plexikon\Chronicle\Exception\StreamAlreadyExists;
use Plexikon\Chronicle\Exception\StreamNotFound;
use Plexikon\Chronicle\Exception\TransactionAlreadyStarted;
use Plexikon\Chronicle\Exception\TransactionNotStarted;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Stream\Stream;
use Plexikon\Chronicle\Stream\StreamName;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateId;
use Plexikon\Chronicle\Support\Contract\Chronicling\QueryFilter;
use Plexikon\Chronicle\Support\Contract\Chronicling\TransactionalChronicler;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;

final class InMemoryChronicler implements TransactionalChronicler
{
    private bool $inTransaction = false;
    private Collection $streams;
    private Collection $cachedStreams;

    public function __construct()
    {
        $this->streams = new Collection();
        $this->cachedStreams = new Collection();
    }

    public function persistFirstCommit(Stream $stream): void
    {
        $streamName = $stream->streamName();

        if ($this->hasStream($streamName)) {
            throw StreamAlreadyExists::withStreamName($streamName);
        }

        $this->storeStream($stream);
    }

    public function persist(Stream $stream): void
    {
        $streamName = $stream->streamName();

        if (!$this->hasStream($streamName)) {
            throw StreamNotFound::withStreamName($streamName);
        }

        $events = $this->streams->get($streamName->toString());

        $this->storeStream($stream, $events);
    }

    public function delete(StreamName $streamName): void
    {
        if (!$this->hasStream($streamName)) {
            throw StreamNotFound::withStreamName($streamName);
        }

        $this->streams->forget($streamName->toString());
    }

    public function retrieveAll(AggregateId $id, StreamName $streamName, string $direction = 'asc'): Generator
    {
        $filter = function (Message $message) use ($id) {
            $aggregateId = $message->header(MessageHeader::AGGREGATE_ID);

            if ($aggregateId instanceof AggregateId) {
                $aggregateId = $aggregateId->toString();
            }

            return $aggregateId === $id->toString() ? $message : null;
        };

        return $this->filterMessages($filter, $streamName, $direction);
    }

    public function retrieveWithQueryFilter(StreamName $streamName, QueryFilter $queryFilter): Generator
    {
        return $this->filterMessages($queryFilter->filterQuery(), $streamName);
    }

    public function fetchStreamNames(StreamName ...$streamNames): array
    {
        $foundStreamNames = [];

        foreach ($streamNames as $streamName) {
            if ($this->hasStream($streamName)) {
                $foundStreamNames[] = $streamName;
            }
        }

        return $foundStreamNames;
    }

    public function hasStream(StreamName $streamName): bool
    {
        return $this->streams->has($streamName->toString());
    }

    public function beginTransaction(): void
    {
        if ($this->inTransaction) {
            throw new TransactionAlreadyStarted();
        }

        $this->inTransaction = true;
    }

    public function commitTransaction(): void
    {
        if (!$this->inTransaction) {
            throw new TransactionNotStarted();
        }

        $this->cachedStreams->each(function (string $streamName, array $streamEvents): void {
            $events = $this->streams->get($streamName, []);

            $this->streams->put($streamName, array_merge($events, $streamEvents));
        });

        $this->cachedStreams = new Collection();

        $this->inTransaction = false;
    }

    public function rollbackTransaction(): void
    {
        if (!$this->inTransaction) {
            throw new TransactionNotStarted();
        }

        $this->cachedStreams = new Collection();

        $this->inTransaction = false;
    }

    public function inTransaction(): bool
    {
        return $this->inTransaction;
    }

    public function getStreamEvents(): array
    {
        return $this->streams->toArray();
    }

    private function storeStream(Stream $stream, array $events = []): void
    {
        $streamName = $stream->streamName()->toString();

        $events = array_merge($events, $stream->events());

        $this->inTransaction
            ? $this->cachedStreams->put($streamName, $events)
            : $this->streams->put($streamName, $events);
    }

    private function filterMessages(callable $filter, StreamName $streamName, string $direction = 'asc'): Generator
    {
        if (!$this->hasStream($streamName)) {
            throw StreamNotFound::withStreamName($streamName);
        }

        $messages = array_filter($this->streams->get($streamName->toString()), $filter, ARRAY_FILTER_USE_BOTH);

        if ($direction === 'desc') {
            krsort($messages);
        }

        if (empty($messages = array_filter($messages))) {
            throw StreamNotFound::withStreamName($streamName);
        }

        yield from $messages;

        return count($messages);
    }
}
