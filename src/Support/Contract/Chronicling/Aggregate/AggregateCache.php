<?php

namespace Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate;

interface AggregateCache
{
    /**
     * @param AggregateRoot $aggregateRoot
     */
    public function put(AggregateRoot $aggregateRoot): void;

    /**
     * @param AggregateId $aggregateId
     */
    public function forget(AggregateId $aggregateId): void;

    /**
     * @return bool
     */
    public function flush(): bool;

    /**
     * @param AggregateId $aggregateId
     * @return bool
     */
    public function has(AggregateId $aggregateId): bool;

    /**
     * @param AggregateId $aggregateId
     * @return AggregateRoot|null
     */
    public function get(AggregateId $aggregateId): ?AggregateRoot;
}
