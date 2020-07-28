<?php

namespace Plexikon\Chronicle\Support\Contract\Projector;

interface ProjectorRunner
{
    /**
     * @param bool $keepRunning
     */
    public function runProjection(bool $keepRunning): void;
}
