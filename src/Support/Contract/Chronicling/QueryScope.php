<?php

namespace Plexikon\Chronicle\Support\Contract\Chronicling;

use Plexikon\Chronicle\Support\Contract\Projector\ProjectionQueryFilter;

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
     * @return ProjectionQueryFilter
     */
    public function fromIncludedPosition(): ProjectionQueryFilter;
}
