<?php

namespace Plexikon\Chronicle\Support\Contract\Chronicling\Model;

interface ProjectionState
{
    /**
     * @param $state
     */
    public function setState($state): void;

    /**
     * @return array
     */
    public function getState(): array;

    /**
     * Reset projection state
     */
    public function resetState(): void;
}
