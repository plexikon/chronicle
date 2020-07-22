<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Chronicling;

use Generator;
use Plexikon\Chronicle\Exception\ConcurrencyException;
use Plexikon\Chronicle\Exception\StreamAlreadyExists;
use Plexikon\Chronicle\Exception\StreamNotFound;
use Plexikon\Chronicle\Stream\Stream;
use Plexikon\Chronicle\Stream\StreamName;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateId;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Chronicling\EventChronicler as EventChroniclerDecorator;
use Plexikon\Chronicle\Support\Contract\Chronicling\QueryFilter;
use Plexikon\Chronicle\Support\Contract\Tracker\EventContext;
use Plexikon\Chronicle\Support\Contract\Tracker\EventTracker;
use Plexikon\Chronicle\Support\Contract\Tracker\Listener;

class EventChronicler implements EventChroniclerDecorator
{
    protected Chronicler $chronicler;
    protected EventTracker $tracker;

    public function __construct(Chronicler $chronicler, EventTracker $tracker)
    {
        $tracker->listen(self::FIRST_COMMIT_EVENT, function (EventContext $contextEvent): void {
            try {
                $this->chronicler->persistFirstCommit($contextEvent->stream());
            } catch (StreamAlreadyExists $exception) {
                $contextEvent->withRaisedException($exception);
            }
        });

        $tracker->listen(self::PERSIST_STREAM_EVENT, function (EventContext $context): void {
            try {
                $this->chronicler->persist($context->stream());
            } catch (StreamNotFound | ConcurrencyException $exception) {
                $context->withRaisedException($exception);
            }
        });

        $tracker->listen(self::DELETE_STREAM_EVENT, function (EventContext $context): void {
            try {
                $this->chronicler->delete($context->streamName());
            } catch (StreamNotFound $exception) {
                $context->withRaisedException($exception);
            }
        });

        $tracker->listen(self::RETRIEVE_ALL_STREAM_EVENT, function (EventContext $context): void {
            try {
                $streamEvents = $this->chronicler->retrieveAll(
                    $context->aggregateId(),
                    $context->streamName(),
                    $context->direction()
                );

                $newStream = new Stream($context->streamName(), $streamEvents);

                $context->withStream($newStream);
            } catch (StreamNotFound $exception) {
                $context->withRaisedException($exception);
            }
        });

        $tracker->listen(self::RETRIEVE_ALL_REVERSE_STREAM_EVENT, function (EventContext $context): void {
            try {
                $streamEvents = $this->chronicler->retrieveAll(
                    $context->aggregateId(),
                    $context->streamName(),
                    $context->direction()
                );

                $newStream = new Stream($context->streamName(), $streamEvents);

                $context->withStream($newStream);
            } catch (StreamNotFound $exception) {
                $context->withRaisedException($exception);
            }
        });

        $tracker->listen(self::RETRIEVE_ALL_FILTERED_STREAM_EVENT, function (EventContext $context): void {
            try {
                $streamName = $context->streamName();

                $stream = new Stream(
                    $streamName,
                    $this->chronicler->retrieveWithQueryFilter($streamName, $context->queryFilter())
                );

                $context->withStream($stream);
            } catch (StreamNotFound $exception) {
                $context->withRaisedException($exception);
            }
        });

        $tracker->listen(self::HAS_STREAM_EVENT, function (EventContext $context): void {
            $streamExists = $this->chronicler->hasStream($context->streamName());

            $context->setStreamExists($streamExists);
        });

        $this->chronicler = $chronicler;
        $this->tracker = $tracker;
    }

    public function persistFirstCommit(Stream $stream): void
    {
        $context = $this->tracker->newContext(self::FIRST_COMMIT_EVENT);
        $context->withStream($stream);

        $this->tracker->fire($context);

        if ($context->hasStreamAlreadyExits()) {
            throw $context->getException();
        }
    }

    public function persist(Stream $stream): void
    {
        $context = $this->tracker->newContext(self::PERSIST_STREAM_EVENT);
        $context->withStream($stream);

        $this->tracker->fire($context);

        if ($context->hasStreamNotFound() || $context->hasRaceCondition()) {
            throw $context->getException();
        }
    }

    public function delete(StreamName $streamName): void
    {
        $context = $this->tracker->newContext(self::DELETE_STREAM_EVENT);
        $context->withStreamName($streamName);

        $this->tracker->fire($context);

        if ($context->hasStreamNotFound()) {
            throw $context->getException();
        }
    }

    public function retrieveAll(AggregateId $aggregateId, StreamName $streamName, string $direction = 'asc'): Generator
    {
        $event = $direction === 'asc' ? self::RETRIEVE_ALL_STREAM_EVENT : self::RETRIEVE_ALL_REVERSE_STREAM_EVENT;

        $context = $this->tracker->newContext($event);
        $context->withStreamName($streamName);
        $context->withAggregateId($aggregateId);
        $context->withDirection($direction);

        $this->tracker->fire($context);

        if ($context->hasStreamNotFound()) {
            throw $context->getException();
        }

        return yield from $context->stream()->events();
    }

    public function retrieveWithQueryFilter(StreamName $streamName, QueryFilter $queryFilter): Generator
    {
        $context = $this->tracker->newContext(self::RETRIEVE_ALL_FILTERED_STREAM_EVENT);
        $context->withStreamName($streamName);
        $context->withQueryFilter($queryFilter);

        $this->tracker->fire($context);

        if ($context->hasStreamNotFound()) {
            throw $context->getException();
        }

        return yield from $context->stream()->events();
    }

    public function fetchStreamNames(StreamName ...$streamNames): array
    {
        return $this->chronicler->fetchStreamNames(...$streamNames);
    }

    public function hasStream(StreamName $streamName): bool
    {
        $context = $this->tracker->newContext(self::HAS_STREAM_EVENT);
        $context->withStreamName($streamName);

        $this->tracker->fire($context);

        return $context->hasStream();
    }

    public function subscribe(string $eventName, callable $eventContext, int $priority = 0): Listener
    {
        return $this->tracker->listen($eventName, $eventContext, $priority);
    }

    public function unsubscribe(Listener ...$eventSubscribers): void
    {
        foreach ($eventSubscribers as $eventSubscriber) {
            $this->tracker->forget($eventSubscriber);
        }
    }

    public function innerChronicler(): Chronicler
    {
        return $this->chronicler;
    }
}
