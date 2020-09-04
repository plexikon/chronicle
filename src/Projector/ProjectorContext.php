<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector;

use Assert\AssertionFailedException;
use Closure;
use Plexikon\Chronicle\Support\Contract\Chronicling\Model\ProjectionState;
use Plexikon\Chronicle\Support\Contract\Chronicling\QueryFilter;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorOption;
use Plexikon\Chronicle\Support\Projector\EventCounter;
use Plexikon\Chronicle\Support\Projector\StreamPosition;

/**
 * @method void bindHandlers(object $eventHandlerContext)
 * @method array bindInit(object $eventHandlerContext)
 * @method void withCallback(callable $initCallback)
 * @method void withQueryFilter(QueryFilter $queryFilter)
 * @method void withStreams(string ...$streamNames)
 * @method void withAllStreams()
 * @method void withKeepRunning(bool $keepRunning)
 * @method void when(array $eventHandlers)
 * @method void whenAny(callable $eventHandler)
 * @method null|Closure initCallback()
 * @method QueryFilter queryFilter()
 * @method array streamNames()
 * @method array eventHandlers()
 * @method bool keepRunning()
 */
class ProjectorContext
{
    public ?string $currentStreamName = null;
    public bool $isStopped = false;
    public bool $isStreamCreated = false;
    public ProjectorOption $option;
    public StreamPosition $position;
    public ProjectionState $state;
    public ProjectionStatus $status;
    public EventCounter $counter;
    private ProjectorContextFactory $factory;

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

        if (is_callable($this->factory->initCallback())) {
            $this->state->setState(
                $this->factory->bindInit($eventHandlerContext)
            );
        }
    }

    public function hasSingleHandler(): bool
    {
        return !is_array($this->factory->eventHandlers());
    }

    public function dispatchSignal(): void
    {
        if ($this->option->dispatchSignal()) {
            pcntl_signal_dispatch();
        }
    }

    /**
     * @param string $methodName
     * @param array  $arguments
     * @return mixed
     */
    public function __call(string $methodName, array $arguments)
    {
        return call_user_func_array([$this->factory, $methodName], $arguments);
    }
}
