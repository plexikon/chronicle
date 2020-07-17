<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Chronicling\Aggregate;

use Plexikon\Chronicle\Reporter\DomainEvent;

abstract class AggregateChanged extends DomainEvent
{
    private ?string $aggregateRootId;

    public static function occur(string $aggregateRootId, array $payload): self
    {
        $self = new static($payload);

        $self->aggregateRootId = $aggregateRootId;

        return $self;
    }

    public function aggregateRootId(): string
    {
        return $this->aggregateRootId;
    }
}
