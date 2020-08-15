<?php

namespace Plexikon\Chronicle\Support\Contract\Projector;

interface Projector
{
    /**
     * @param bool $keepRunning
     */
    public function run(bool $keepRunning = true): void;

    /**
     * Stop projection
     */
    public function stop(): void;

    /**
     * Reset projection
     */
    public function reset(): void;

    /**
     * @return array<mixed>
     */
    public function getState(): array;
}
