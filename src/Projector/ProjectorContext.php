<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector;

use Closure;
use Plexikon\Chronicle\Projector\Concerns\HasContextFactory;
use Plexikon\Chronicle\Support\Contract\Chronicling\Model\ProjectionState;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorOption as BaseProjectorOption;
use Plexikon\Chronicle\Support\Projector\EventCounter;
use Plexikon\Chronicle\Support\Projector\InMemoryProjectionState;
use Plexikon\Chronicle\Support\Projector\StreamPosition;

class ProjectorContext
{
    use HasContextFactory;

    public BaseProjectorOption $options;
    public ProjectionState $state;
    public ProjectionStatus $status;
    public EventCounter $eventCounter;
    public ?StreamPosition $streamPosition;
    public ?string $currentStreamName = null;
    public bool $isProjectionStopped = false;
    public bool $isStreamCreated = false;

    public function __construct(BaseProjectorOption $projectorOption,
                                StreamPosition $streamPosition,
                                ProjectionState $projectionState = null)
    {
        $this->streamPosition = $streamPosition;
        $this->options = $projectorOption;
        $this->state = $projectionState ?? new InMemoryProjectionState();
        $this->status = ProjectionStatus::IDLE();
        $this->eventCounter = new EventCounter();
    }

    public function setupStreamPosition(): void
    {
        $this->streamPosition->make($this->streamNames);
    }

    public function setUpProjection(object $eventHandlerContext): void
    {
        $this->validateFactory();

        if ($this->hasSingleHandler()) {
            $this->eventHandlers = Closure::bind($this->eventHandlers, $eventHandlerContext);
        } else {
            foreach ($this->eventHandlers as $eventName => $eventHandler) {
                $this->eventHandlers[$eventName] = Closure::bind($eventHandler, $eventHandlerContext);
            }
        }

        if (is_callable($this->initCallback)) {
            $callback  = Closure::bind($this->initCallback, $eventHandlerContext);

            $result = $callback();

            $this->state->setState($result);

            $this->initCallback = $callback;
        }
    }

    public function dispatchPCNTLSignal(): void
    {
        if ($this->options->dispatchSignal()) {
            pcntl_signal_dispatch();
        }
    }
}
