<?php

namespace Plexikon\Chronicle\Support\Contract\Tracker;

use Throwable;

interface Context
{
    /**
     * @param string $event
     */
    public function withEvent(string $event): void;

    /**
     * @return string
     */
    public function getCurrentEvent(): string;

    /**
     * @param bool $stopPropagation
     */
    public function stopPropagation(bool $stopPropagation): void;

    /**
     * @return bool
     */
    public function isPropagationStopped(): bool;

    /**
     * @param Throwable $exception
     */
    public function withRaisedException(Throwable $exception): void;

    /**
     * @return bool
     */
    public function hasException(): bool;

    /**
     * @return Throwable|null
     */
    public function getException(): ?Throwable;
}
