<?php

namespace Plexikon\Chronicle\Support\Contract\Projector;

use DateTimeImmutable;
use Plexikon\Chronicle\Projector\ProjectionStatus;
use Plexikon\Chronicle\Projector\ProjectorContext;

interface ProjectorLock
{
    /**
     * Prepare projection
     * @param ProjectorContext $context
     * @param ReadModel|null $readModel
     */
    public function prepareProjection(ProjectorContext $context, ?ReadModel $readModel = null): void;

    /**
     * Create new projection
     */
    public function createProjection(): void;

    /**
     * Load projection state
     */
    public function loadProjectionState(): void;

    /**
     * Stop projection
     */
    public function stopProjection(): void;

    /**
     * Start projection again
     */
    public function startProjectionAgain(): void;

    /**
     * Fetch projection status
     *
     * @return ProjectionStatus
     */
    public function fetchProjectionStatus(): ProjectionStatus;

    /**
     * Persist projection
     */
    public function persistProjection(): void;

    /**
     * Update projection on counter
     */
    public function updateProjectionOnCounter(): void;

    /**
     * Reset projection
     */
    public function resetProjection(): void;

    /**
     * Delete projection
     * @param bool $deleteEmittedEvents
     */
    public function deleteProjection(bool $deleteEmittedEvents): void;

    /**
     * Check if projection exists
     * @return bool
     */
    public function isProjectionExists(): bool;

    /**
     * Acquire lock
     */
    public function acquireLock(): void;

    /**
     * Update lock
     */
    public function updateLock(): void;

    /**
     * Release lock
     */
    public function releaseLock(): void;

    /**
     * Update lock on condition
     *
     * @param DateTimeImmutable $dateTime
     * @return bool
     */
    public function shouldUpdateLock(DateTimeImmutable $dateTime): bool;

    /**
     * Get stream name
     * @return string
     */
    public function getStreamName(): string;
}
