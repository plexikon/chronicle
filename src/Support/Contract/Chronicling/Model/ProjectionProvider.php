<?php

namespace Plexikon\Chronicle\Support\Contract\Chronicling\Model;

interface ProjectionProvider
{
    /**
     * @param string $name
     * @param string $status
     * @return bool
     */
    public function newProjection(string $name, string $status): bool;

    /**
     * @param string $name
     * @return ProjectionModel|null
     */
    public function findByName(string $name): ?ProjectionModel;

    /**
     * @param string ...$names
     * @return array
     */
    public function findByNames(string ...$names): array;

    /**
     * @param string $name
     * @return int
     */
    public function deleteByName(string $name): int;

    /**
     * @param string $name
     * @param string $status
     * @param string $lockedUntil
     * @param string $now
     * @return int
     */
    public function acquireLock(string $name, string $status, string $lockedUntil, string $now): int;

    /**
     * @param string $name
     * @param array $data
     * @return int
     */
    public function updateStatus(string $name, array $data): int;

    /**
     * @param string $name
     * @return bool
     */
    public function projectionExists(string $name): bool;
}
