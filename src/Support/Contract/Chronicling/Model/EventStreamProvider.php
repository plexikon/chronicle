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
     * Create new stream
     *
     * @param StreamName $streamName
     * @param string     $tableName
     * @return bool
     */
    public function createStream(StreamName $streamName, string $tableName): bool;

    /**
     * Delete stream
     *
     * @param StreamName $streamName
     * @return bool
     */
    public function deleteStream(StreamName $streamName): bool;

    /**
     * Filter by stream names
     *
     * @param array $streamNames
     * @return array
     */
    public function filterByStreams(array $streamNames): array;

    /**
     * Filter streams without internal
     *
     * @return array
     */
    public function allStreamWithoutInternal(): array;

    /**
     * Check existence of stream name
     *
     * @param StreamName $streamName
     * @return bool
     */
    public function hasRealStreamName(StreamName $streamName): bool;
}
