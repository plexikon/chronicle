<?php

namespace Plexikon\Chronicle\Support\Contract\Chronicling;

interface WriteLockStrategy
{
    /**
     * @param string $name
     * @return bool
     */
    public function getLock(string $name): bool;

    /**
     * @param string $name
     * @return bool
     */
    public function releaseLock(string $name): bool;
}
