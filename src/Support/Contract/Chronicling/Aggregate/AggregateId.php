<?php

namespace Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate;

interface AggregateId
{
    /**
     * @param string $aggregateId
     * @return static
     */
    public static function fromString(string $aggregateId): AggregateId;

    /**
     * @return string
     */
    public function toString(): string;

    /**
     * @param AggregateId $aggregateId
     * @return bool
     */
    public function equalsTo(AggregateId $aggregateId): bool;
}
