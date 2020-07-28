<?php

namespace Plexikon\Chronicle\Support\Contract\Projector;

use Plexikon\Chronicle\Reporter\DomainEvent;

interface ProjectionProjector extends PersistentProjector
{
    /**
     * @param DomainEvent $event
     */
    public function emit(DomainEvent $event): void;

    /**
     * @param string $streamName
     * @param DomainEvent $event
     */
    public function linkTo(string $streamName, DomainEvent $event): void;
}
