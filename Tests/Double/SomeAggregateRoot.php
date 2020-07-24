<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Double;

use Plexikon\Chronicle\Chronicling\Aggregate\Concerns\HasAggregateRoot;
use Plexikon\Chronicle\Reporter\DomainEvent;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateId;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateRoot;

final class SomeAggregateRoot implements AggregateRoot
{
    use HasAggregateRoot;

    private array $appliedEvents = [];
    public static function create(AggregateId $aggregateId): self
    {
        return new self($aggregateId);
    }

    public function recordEvent(DomainEvent ... $events): void
    {
        foreach ($events as $event) {
            $this->recordThat($event);
        }
    }

    public function getRecordedEvents(): array
    {
        return $this->recordedEvents;
    }

    public function getAppliedEvents(): array
    {
        return $this->appliedEvents;
    }

    protected function applySomeAggregateChanged(SomeAggregateChanged $event): void
    {
        $this->appliedEvents[] = $event;
    }
}
