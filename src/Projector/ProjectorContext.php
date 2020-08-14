<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector;

use Assert\AssertionFailedException;
use Plexikon\Chronicle\Support\Contract\Chronicling\Model\ProjectionState;
use Plexikon\Chronicle\Support\Contract\Chronicling\QueryFilter;
use Plexikon\Chronicle\Support\Contract\ProjectionQueryFilter;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorOption;
use Plexikon\Chronicle\Support\Projector\EventCounter;
use Plexikon\Chronicle\Support\Projector\StreamPosition;

class ProjectorContext
{
    public ?string $currentStreamName = null;
    public bool $isStopped = false;
    public bool $isStreamCreated = false;
    public ProjectorContextFactory $factory;
    public ProjectorOption $option;
    public StreamPosition $position;
    public ProjectionState $state;
    public ProjectionStatus $status;
    public EventCounter $counter;

    public function __construct(ProjectorOption $option, StreamPosition $position, ProjectionState $state)
    {
        $this->option = $option;
        $this->position = $position;
        $this->state = $state;
        $this->counter = new EventCounter();
        $this->status = ProjectionStatus::IDLE();
        $this->factory = new ProjectorContextFactory();
    }

    /**
     * @param object $eventHandlerContext
     * @throws AssertionFailedException
     */
    public function setUpProjection(object $eventHandlerContext): void
    {
        $this->factory->validate();

        $this->factory->bindHandlers($eventHandlerContext);

        if (is_callable($this->factory->getInit())) {
            $this->state->setState(
                $this->factory->bindInit($eventHandlerContext)
            );
        }
    }

    public function hasSingleHandler(): bool
    {
        return !is_array($this->factory->getEventHandlers());
    }

    public function initCallback(): ?callable
    {
        return $this->factory->getInit();
    }

    /**
     * @return QueryFilter|ProjectionQueryFilter
     */
    public function queryFilter(): QueryFilter
    {
        return $this->factory->getQueryFilter();
    }

    /**
     * @return array|callable
     */
    public function eventHandlers()
    {
        return $this->factory->getEventHandlers();
    }

    public function streamNames(): array
    {
        return $this->factory->getStreamNames();
    }

    public function keepRunning(): bool
    {
        return $this->factory->getKeepRunning();
    }

    public function dispatchSignal(): void
    {
        if ($this->option->dispatchSignal()) {
            pcntl_signal_dispatch();
        }
    }
}
