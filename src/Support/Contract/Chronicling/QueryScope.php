<?php

namespace Plexikon\Chronicle\Support\Contract\Chronicling;

interface QueryScope
{
    /**
     * @param string $aggregateId
     * @param string $aggregateType
     * @param int $aggregateVersion
     * @return QueryFilter
     */
    public function matchAggregateIdAndTypeGreaterThanVersion(string $aggregateId,
                                                              string $aggregateType,
                                                              int $aggregateVersion): QueryFilter;

    /**
     * @param int $from
     * @param int $to
     * @param string|null $direction
     * @return QueryFilter
     */
    public function fromToPosition(int $from, int $to, ?string $direction): QueryFilter;

    /**
     * @param int $position
     * @return QueryFilter
     */
    public function fromIncludedPosition(int $position): QueryFilter;
}
