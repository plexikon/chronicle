<?php

namespace Plexikon\Chronicle\Support\Contract\Projector;

interface PipeOnce extends Pipe
{
    /**
     * @return bool
     */
    public function isAlreadyPiped(): bool;
}
