<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Chronicling\Aggregate\Concerns;

use Generator;
use Plexikon\Chronicle\Chronicling\Aggregate\MessageEventIterator;
use Plexikon\Chronicle\Stream\StreamName;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateId;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateRoot;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Chronicling\QueryFilter;

trait HasReconstituteAggregate
{
    protected string $aggregateRoot;
    protected Chronicler $chronicler;
    protected StreamName $streamName;

    protected function reconstituteAggregateRoot(AggregateId $aggregateId): AggregateRoot
    {
        /** @var AggregateRoot $aggregateRootClassName */
        $aggregateRootClassName = $this->aggregateRoot;

        return $aggregateRootClassName::reconstituteFromEvents(
            $aggregateId,
            $this->retrieveAllEvents($aggregateId, null)
        );
    }

    private function retrieveAllEvents(AggregateId $aggregateId, ?QueryFilter $snapshotQueryFilter): Generator
    {
        $messages = null !== $snapshotQueryFilter
            ? $this->chronicler->retrieveWithQueryFilter($this->streamName, $snapshotQueryFilter)
            : $this->chronicler->retrieveAll($aggregateId, $this->streamName);


        yield from $events = new MessageEventIterator($messages);

        return $events->count();
    }
}
