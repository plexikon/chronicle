<?php

namespace Plexikon\Chronicle\Support\Contract\Projector;

use DateTimeImmutable;
use Plexikon\Chronicle\Projector\ProjectionStatus;

interface ProjectorRepository
{
    /**
     * Prepare projection
     * @param ReadModel|null $readModel
     */
    public function prepare(?ReadModel $readModel): void;

    /**
     * Create new projection
     */
    public function create(): void;

    /**
     * Load projection state
     */
    public function loadState(): void;

    /**
     * Stop projection
     */
    public function stop(): void;

    /**
     * Start projection again
     */
    public function startAgain(): void;

    /**
     * Fetch projection status
     *
     * @return ProjectionStatus
     */
    public function loadStatus(): ProjectionStatus;

    /**
     * Persist projection
     */
    public function persist(): void;

    /**
     * Update projection on counter
     */
    public function updateOnCounter(): void;

    /**
     * Reset projection
     */
    public function reset(): void;

    /**
     * Delete projection
     * @param bool $deleteEmittedEvents
     */
    public function delete(bool $deleteEmittedEvents): void;

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
