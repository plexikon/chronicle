<?php

namespace Plexikon\Chronicle\Support\Contract\Projector;

interface PersistentProjector extends Projector
{
    /**
     * @param bool $deleteEmittedEvents
     */
    public function delete(bool $deleteEmittedEvents): void;

    /**
     * @return string
     */
    public function getStreamName(): string;
}
