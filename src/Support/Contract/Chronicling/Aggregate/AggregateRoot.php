<?php

namespace Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate;

use Generator;
use Plexikon\Chronicle\Reporter\DomainEvent;

interface AggregateRoot
{
    /**
     * @param AggregateId $aggregateId
     * @param Generator $events
     * @return static
     */
    public static function reconstituteFromEvents(AggregateId $aggregateId, Generator $events): AggregateRoot;

    /**
     * @return DomainEvent[]
     */
    public function releaseEvents(): array;

    /**
     * @return AggregateId
     */
    public function aggregateId(): AggregateId;

    /**
     * @return int
     */
    public function version(): int;

    /**
     * @return bool
     */
    public function exists(): bool;
}
