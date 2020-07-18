<?php

namespace Plexikon\Chronicle\Support\Contract\Projector;

interface ReadModel
{
    /**
     * Initialize read model
     */
    public function initialize(): void;

    /**
     * @return bool
     */
    public function isInitialized(): bool;

    /**
     * Reset read model table
     */
    public function reset(): void;

    /**
     * Drop Read model table
     */
    public function down(): void;

    /**
     * @param string $operation
     * @param mixed ...$arguments
     */
    public function stack(string $operation, ...$arguments): void;

    /**
     * Persist read model
     */
    public function persist(): void;

}
