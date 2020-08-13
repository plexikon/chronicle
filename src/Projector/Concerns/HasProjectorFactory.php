<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\Concerns;

use Plexikon\Chronicle\Projector\ProjectorContext;
use Plexikon\Chronicle\Support\Contract\Chronicling\QueryFilter;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorFactory;

trait HasProjectorFactory
{
    protected ProjectorContext $projectorContext;

    /**
     * @param callable $initCallback
     * @return ProjectorFactory&static
     */
    public function initialize(callable $initCallback): ProjectorFactory
    {
        $this->projectorContext->factory->withCallback($initCallback);

        return $this;
    }

    /**
     * @param QueryFilter $queryFilter
     * @return ProjectorFactory&static
     */
    public function withQueryFilter(QueryFilter $queryFilter): ProjectorFactory
    {
        $this->projectorContext->factory->withQueryFilter($queryFilter);

        return $this;
    }

    /**
     * @param $streamNames
     * @return ProjectorFactory&static
     */
    public function fromStreams(string ...$streamNames): ProjectorFactory
    {
        $this->projectorContext->factory->withStreams(...$streamNames);

        return $this;
    }

    /**
     * @return ProjectorFactory&static
     */
    public function fromAll(): ProjectorFactory
    {
        $this->projectorContext->factory->withAllStreams();

        return $this;
    }

    /**
     * @param array $eventHandlers
     * @return ProjectorFactory&static
     */
    public function when(array $eventHandlers): ProjectorFactory
    {
        $this->projectorContext->factory->when($eventHandlers);

        return $this;
    }

    /**
     * @param callable $eventHandler
     * @return ProjectorFactory&static
     */
    public function whenAny(callable $eventHandler): ProjectorFactory
    {
        $this->projectorContext->factory->whenAny($eventHandler);

        return $this;
    }
}
