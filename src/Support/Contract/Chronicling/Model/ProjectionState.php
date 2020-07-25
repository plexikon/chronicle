<?php

namespace Plexikon\Chronicle\Support\Contract\Chronicling\Model;

interface ProjectionState
{
    /**
     * Set projection state
     * Only non empty array will be set
     * @param mixed $state
     */
    public function setState($state): void;

    /**
     * Return state
     *
     * @return array
     */
    public function getState(): array;

    /**
     * Reset projection state
     */
    public function resetState(): void;
}
