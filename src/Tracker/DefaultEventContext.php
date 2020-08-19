<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tracker;

use Illuminate\Support\LazyCollection;
use Plexikon\Chronicle\Exception\Assertion;
use Plexikon\Chronicle\Exception\ConcurrencyException;
use Plexikon\Chronicle\Exception\StreamAlreadyExists;
use Plexikon\Chronicle\Exception\StreamNotFound;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Stream\Stream;
use Plexikon\Chronicle\Stream\StreamName;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateId;
use Plexikon\Chronicle\Support\Contract\Chronicling\QueryFilter;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageDecorator;
use Plexikon\Chronicle\Support\Contract\Tracker\EventContext;
use Plexikon\Chronicle\Tracker\Concerns\HasContext;

class DefaultEventContext implements EventContext
{
    use HasContext;

    protected ?Stream $stream = null;
    protected ?StreamName $streamName = null;
    protected ?AggregateId $aggregateId = null;
    protected ?string $direction = null;
    protected ?QueryFilter $queryFilter = null;
    protected array $streamNames = [];
    protected bool $isStreamExists = false;

    public function withStream(Stream $stream): void
    {
        $this->stream = $stream;
    }

    public function withStreamName(StreamName $streamName): void
    {
        $this->streamName = $streamName;
    }

    public function withStreamNames(StreamName ...$streamNames): void
    {
        $this->streamNames = $streamNames;
    }

    public function setStreamExists(bool $isStreamExists): void
    {
        $this->isStreamExists = $isStreamExists;
    }

    public function withAggregateId(AggregateId $aggregateId): void
    {
        $this->aggregateId = $aggregateId;
    }

    public function withQueryFilter(QueryFilter $queryFilter): void
    {
        $this->queryFilter = $queryFilter;
    }

    public function withDirection(string $direction): void
    {
        Assertion::inArray($direction, ['asc', 'desc'], 'Invalid Order by direction');

        $this->direction = $direction;
    }

    public function decorateStreamEvents(MessageDecorator $messageDecorator): void
    {
        if (!$this->stream) {
            return;
        }

        $this->stream = new Stream(
            $this->stream->streamName(),
            (new LazyCollection($this->stream->events()))->tapEach(
                function (Message &$message) use ($messageDecorator): void {
                    $message = $messageDecorator->decorate($message);
                })->toArray()
        );
    }

    public function hasStream(): bool
    {
        return $this->isStreamExists;
    }

    public function hasStreamNotFound(): bool
    {
        return $this->exception instanceof StreamNotFound;
    }

    public function hasStreamAlreadyExits(): bool
    {
        return $this->exception instanceof StreamAlreadyExists;
    }

    public function hasRaceCondition(): bool
    {
        return $this->exception instanceof ConcurrencyException;
    }

    public function stream(): ?Stream
    {
        return $this->stream;
    }

    public function streamName(): ?StreamName
    {
        return $this->streamName;
    }

    public function streamNames(): array
    {
        return $this->streamNames;
    }

    public function aggregateId(): ?AggregateId
    {
        return $this->aggregateId;
    }

    public function direction(): ?string
    {
        return $this->direction;
    }

    public function queryFilter(): ?QueryFilter
    {
        return $this->queryFilter;
    }
}
