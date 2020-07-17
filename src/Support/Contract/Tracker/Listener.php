<?php

namespace Plexikon\Chronicle\Support\Contract\Tracker;

interface Listener
{
    /**
     * @return string
     */
    public function eventName(): string;

    /**
     * @return callable
     */
    public function context(): callable;

    /**
     * @return int
     */
    public function priority(): int;
}
