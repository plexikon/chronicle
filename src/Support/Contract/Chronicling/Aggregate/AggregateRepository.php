<?php

namespace Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate;

use Plexikon\Chronicle\Reporter\DomainEvent;
use Plexikon\Chronicle\Support\Contract\Chronicling\ReadOnlyChronicler;

interface AggregateRepository
{
    /**
     * @param AggregateId $aggregateId
     * @return AggregateRoot
     */
    public function retrieve(AggregateId $aggregateId): AggregateRoot;

    /**
     * @param AggregateRoot $aggregateRoot
     */
    public function persist(AggregateRoot $aggregateRoot): void;

    /**
     * @param AggregateId $aggregateId
     * @param int $aggregateVersion
     * @param DomainEvent ...$events
     */
    public function persistEvents(AggregateId $aggregateId, int $aggregateVersion, DomainEvent ...$events): void;

    /**
     * @return ReadOnlyChronicler
     */
    public function chronicler(): ReadOnlyChronicler;

    /**
     * Flush aggregate cache
     */
    public function flushCache(): void;
}
