<?php

namespace Plexikon\Chronicle\Support\Contract\Chronicling;

use Generator;
use Plexikon\Chronicle\Stream\StreamName;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateId;

interface ReadOnlyChronicler
{
    /**
     * @param AggregateId $aggregateId
     * @param StreamName $streamName
     * @param string $direction
     * @return Generator
     */
    public function retrieveAll(AggregateId $aggregateId, StreamName $streamName, string $direction = 'asc'): Generator;

    /**
     * @param StreamName $streamName
     * @param QueryFilter $queryFilter
     * @return Generator
     */
    public function retrieveWithQueryFilter(StreamName $streamName, QueryFilter $queryFilter): Generator;

    /**
     * @param StreamName ...$streamNames
     * @return array
     */
    public function fetchStreamNames(StreamName ...$streamNames): array;

    /**
     * @param StreamName $streamName
     * @return bool
     */
    public function hasStream(StreamName $streamName): bool;
}
