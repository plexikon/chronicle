<?php

namespace Plexikon\Chronicle\Support\Contract\Projector;

use Plexikon\Chronicle\Support\Contract\Chronicling\QueryFilter;

interface ProjectorFactory extends Projector
{
    /**
     * @param callable $initCallback
     * @return ProjectorFactory
     */
    public function initialize(callable $initCallback): ProjectorFactory;

    /**
     * @param string ...$streamNames
     * @return ProjectorFactory
     */
    public function fromStreams(string ...$streamNames): ProjectorFactory;

    /**
     * @return ProjectorFactory
     */
    public function fromAll(): ProjectorFactory;

    /**
     * @param array $eventHandlers
     * @return ProjectorFactory
     */
    public function when(array $eventHandlers): ProjectorFactory;

    /**
     * @param callable $eventHandler
     * @return ProjectorFactory
     */
    public function whenAny(callable $eventHandler): ProjectorFactory;

    /**
     *
     * checkMe projector query filter
     * @param QueryFilter $queryFilter
     * @return ProjectorFactory
     */
    public function withQueryFilter(QueryFilter $queryFilter): ProjectorFactory;
}
