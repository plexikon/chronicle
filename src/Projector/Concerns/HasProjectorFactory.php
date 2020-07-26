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
        $this->projectorContext->withCallback($initCallback);

        return $this;
    }

    /**
     * @param QueryFilter $queryFilter
     * @return ProjectorFactory&static
     */
    public function withQueryFilter(QueryFilter $queryFilter): ProjectorFactory
    {
        $this->projectorContext->withQueryFilter($queryFilter);

        return $this;
    }

    /**
     * @param $streamNames
     * @return ProjectorFactory&static
     */
    public function fromStreams(string ...$streamNames): ProjectorFactory
    {
        $this->projectorContext->withStreams(...$streamNames);

        return $this;
    }

    /**
     * @return ProjectorFactory&static
     */
    public function fromAll(): ProjectorFactory
    {
        $this->projectorContext->withAllStreams();

        return $this;
    }

    /**
     * @param array $eventHandlers
     * @return ProjectorFactory&static
     */
    public function when(array $eventHandlers): ProjectorFactory
    {
        $this->projectorContext->when($eventHandlers);

        return $this;
    }

    /**
     * @param callable $eventHandler
     * @return ProjectorFactory&static
     */
    public function whenAny(callable $eventHandler): ProjectorFactory
    {
        $this->projectorContext->whenAny($eventHandler);

        return $this;
    }
}
