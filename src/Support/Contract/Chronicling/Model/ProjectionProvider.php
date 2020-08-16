<?php

namespace Plexikon\Chronicle\Support\Contract\Chronicling\Model;

interface ProjectionProvider
{
    /**
     * Create new projection
     *
     * @param string $name
     * @param string $status
     * @return bool
     */
    public function createProjection(string $name, string $status): bool;

    /**
     * Update projection by name
     *
     * @param string $name
     * @param array  $data
     * @return int
     */
    public function updateProjection(string $name, array $data): int;

    /**
     * Check existence of projection by name
     *
     * @param string $name
     * @return bool
     */
    public function projectionExists(string $name): bool;

    /**
     * Find projection by name
     *
     * @param string $name
     * @return ProjectionModel|null
     */
    public function findByName(string $name): ?ProjectionModel;

    /**
     * Find projection ny many names
     *
     * @param string ...$names
     * @return string[]|null[]
     */
    public function findByNames(string ...$names): array;

    /**
     * Delete projection by name
     *
     * @param string $name
     * @return int
     */
    public function deleteByName(string $name): int;

    /**
     * Acquire lock
     *
     * @param string $name
     * @param string $status
     * @param string $lockedUntil
     * @param string $now
     * @return int
     */
    public function acquireLock(string $name, string $status, string $lockedUntil, string $now): int;
}
