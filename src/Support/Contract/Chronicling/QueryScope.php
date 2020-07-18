<?php

namespace Plexikon\Chronicle\Support\Contract\Chronicling;

interface QueryScope
{
    /**
     * @param string $aggregateId
     * @param string $aggregateType
     * @param int $aggregateVersion
     * @return callable
     */
    public function matchAggregateIdAndTypeGreaterThanVersion(string $aggregateId,
                                                              string $aggregateType,
                                                              int $aggregateVersion): callable;

    /**
     * @param int $from
     * @param int $to
     * @param string|null $direction
     * @return callable
     */
    public function fromToPosition(int $from, int $to, ?string $direction): callable;

    /**
     * @param int $position
     * @return callable
     */
    public function fromIncludedPosition(int $position): callable;
}
