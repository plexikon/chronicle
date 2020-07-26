<?php

namespace Plexikon\Chronicle\Support\Contract\Projector;

interface ProjectorManager extends ReadProjectorManager
{
    /**
     * @param array $options
     * @return ProjectorFactory
     */
    public function createQuery(array $options = []): ProjectorFactory;

    /**
     * @param string $name
     * @param array $options
     * @return ProjectorFactory
     */
    public function createProjection(string $name, array $options = []): ProjectorFactory;

    /**
     * @param string $name
     * @param ReadModel $readModel
     * @param array $options
     * @return ProjectorFactory
     */
    public function createReadModelProjection(string $name,
                                              ReadModel $readModel,
                                              array $options = []): ProjectorFactory;

    /**
     * @param string $name
     */
    public function stopProjection(string $name): void;

    /**
     * @param string $name
     */
    public function resetProjection(string $name): void;

    /**
     * @param string $name
     * @param bool $deleteEmittedEvents
     */
    public function deleteProjection(string $name, bool $deleteEmittedEvents): void;
}
