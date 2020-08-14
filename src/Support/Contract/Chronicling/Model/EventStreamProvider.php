<?php

namespace Plexikon\Chronicle\Support\Contract\Chronicling\Model;

use Plexikon\Chronicle\Stream\StreamName;

interface EventStreamProvider
{
    /**
     * @private
     */
    public const INTERNAL_PREFIX = '$';

    /**
     * @param StreamName $streamName
     * @param string $tableName
     * @return bool
     */
    public function createStream(StreamName $streamName, string $tableName): bool;

    /**
     * @param StreamName $streamName
     * @return bool
     */
    public function deleteStream(StreamName $streamName): bool;

    /**
     * @param array $streamNames
     * @return array
     */
    public function filterByStream(array $streamNames): array;

    /**
     * @return array
     */
    public function allStreamWithoutInternal(): array;

    /**
     * @param StreamName $streamName
     * @return bool
     */
    public function hasRealStreamName(StreamName $streamName): bool;
}
