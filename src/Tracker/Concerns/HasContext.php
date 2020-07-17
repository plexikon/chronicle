<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tracker\Concerns;

use Throwable;

trait HasContext
{
    protected ?Throwable $exception = null;
    protected bool $isPropagationStopped = false;
    protected ?string $currentEvent;

    public function __construct(string $currentEvent)
    {
        $this->currentEvent = $currentEvent;
    }

    public function withEvent(string $event): void
    {
        $this->currentEvent = $event;
    }

    public function getCurrentEvent(): string
    {
        return $this->currentEvent;
    }

    public function stopPropagation(bool $stopPropagation): void
    {
        $this->isPropagationStopped = $stopPropagation;
    }

    public function isPropagationStopped(): bool
    {
        return $this->isPropagationStopped;
    }

    public function withRaisedException(Throwable $exception): void
    {
        $this->exception = $exception;
    }

    public function hasException(): bool
    {
        return null !== $this->exception;
    }

    public function getException(): ?Throwable
    {
        return $this->exception;
    }
}
