<?php

namespace Plexikon\Chronicle\Support\Contract\Tracker;

use Plexikon\Chronicle\Stream\Stream;
use Plexikon\Chronicle\Stream\StreamName;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateId;
use Plexikon\Chronicle\Support\Contract\Chronicling\QueryFilter;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageDecorator;

interface EventContext extends Context
{
    /**
     * @param Stream $stream
     */
    public function withStream(Stream $stream): void;

    /**
     * @param StreamName $streamName
     */
    public function withStreamName(StreamName $streamName): void;

    /**
     * @param bool $isStreamExists
     */
    public function setStreamExists(bool $isStreamExists): void;

    /**
     * @param AggregateId $aggregateId
     */
    public function withAggregateId(AggregateId $aggregateId): void;

    /**
     * @param QueryFilter $queryFilter
     */
    public function withQueryFilter(QueryFilter $queryFilter): void;

    /**
     * @param string $direction
     */
    public function withDirection(string $direction): void;

    /**
     * @param MessageDecorator $messageDecorator
     */
    public function decorateStreamEvents(MessageDecorator $messageDecorator): void;

    /**
     * @return bool
     */
    public function hasStream(): bool;

    /**
     * @return bool
     */
    public function hasStreamNotFound(): bool;

    /**
     * @return bool
     */
    public function hasStreamAlreadyExits(): bool;

    /**
     * @return bool
     */
    public function hasRaceCondition(): bool;

    /**
     * @return Stream|null
     */
    public function stream(): ?Stream;

    /**
     * @return StreamName|null
     */
    public function streamName(): ?StreamName;

    /**
     * @return AggregateId|null
     */
    public function aggregateId(): ?AggregateId;

    /**
     * @return string|null
     */
    public function direction(): ?string;

    /**
     * @return QueryFilter|null
     */
    public function queryFilter(): ?QueryFilter;
}
