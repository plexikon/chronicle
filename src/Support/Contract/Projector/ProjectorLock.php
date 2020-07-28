<?php

namespace Plexikon\Chronicle\Support\Contract\Projector;

use DateTimeImmutable;
use Plexikon\Chronicle\Projector\ProjectionStatus;

interface ProjectorLock
{
    /**
     * Create new projection
     */
    public function createProjection(): void;

    /**
     * Create new projection
     */
    public function loadProjectionState(): void;

    /**
     * Create new projection
     */
    public function stopProjection(): void;

    /**
     * Create new projection
     */
    public function startProjectionAgain(): void;

    /**
     * @return ProjectionStatus
     */
    public function fetchProjectionStatus(): ProjectionStatus;

    /**
     * Create new projection
     */
    public function persistProjection(): void;

    /**
     * Create new projection
     */
    public function resetProjection(): void;

    /**
     * Create new projection
     * @param bool $deleteEmittedEvents
     */
    public function deleteProjection(bool $deleteEmittedEvents): void;

    /**
     * @return bool
     */
    public function isProjectionExists(): bool;

    /**
     * Create new projection
     */
    public function acquireLock(): void;

    /**
     * Create new projection
     */
    public function updateLock(): void;

    /**
     * Create new projection
     */
    public function releaseLock(): void;

    /**
     * @param DateTimeImmutable $dateTime
     * @return bool
     */
    public function shouldUpdateLock(DateTimeImmutable $dateTime): bool;

    /**
     * @return string
     */
    public function getStreamName(): string;
}
