<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Chronicling\Aggregate\Concerns;

use Generator;
use Plexikon\Chronicle\Exception\StreamNotFound;
use Plexikon\Chronicle\Reporter\DomainEvent;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateId;

trait HasAggregateRoot
{
    private int $version = 0;
    private array $recordedEvents = [];
    private AggregateId $aggregateId;

    protected function __construct(AggregateId $aggregateId)
    {
        $this->aggregateId = $aggregateId;
    }

    public function aggregateId(): AggregateId
    {
        return $this->aggregateId;
    }

    public function version(): int
    {
        return $this->version;
    }

    protected function apply(DomainEvent $event): void
    {
        $parts = explode('\\', get_class($event));

        $this->{'apply' . end($parts)}($event);

        ++$this->version;
    }

    protected function recordThat(DomainEvent $event): void
    {
        $this->apply($event);

        $this->recordedEvents[] = $event;
    }

    public function releaseEvents(): array
    {
        $releasedEvents = $this->recordedEvents;

        $this->recordedEvents = [];

        return $releasedEvents;
    }

    public static function reconstituteFromEvents(AggregateId $aggregateId, Generator $events): self
    {
        /** @var HasAggregateRoot&static $aggregateRoot */
        $aggregateRoot = new static($aggregateId);

        try {
            $noStreamEvent = false;

            foreach ($events as $event) {
                $aggregateRoot->apply($event);
            }
        } catch (StreamNotFound $streamNotFound) {
            $noStreamEvent = true;
        }

        $aggregateRoot->version = $noStreamEvent ? 0 : ((int)$events->getReturn() ?: 0);

        return $aggregateRoot;
    }

    public function exists(): bool
    {
        return $this->version > 0;
    }
}
