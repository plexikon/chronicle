<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\Concerns;

use DateTimeImmutable;
use Plexikon\Chronicle\Projector\ProjectionStatus;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorLock;

trait HasProjectorLock
{
    protected ProjectorLock $projectorLock;

    public function createProjection(): void
    {
        $this->projectorLock->createProjection();
    }

    public function loadProjectionState(): void
    {
        $this->projectorLock->loadProjectionState();
    }

    public function stopProjection(): void
    {
        $this->projectorLock->stopProjection();
    }

    public function startProjectionAgain(): void
    {
        $this->projectorLock->startProjectionAgain();
    }

    public function updateProjectionOnCounter(): void
    {
        $this->projectorLock->updateProjectionOnCounter();
    }

    public function fetchProjectionStatus(): ProjectionStatus
    {
        return $this->projectorLock->fetchProjectionStatus();
    }

    public function isProjectionExists(): bool
    {
        return $this->projectorLock->isProjectionExists();
    }

    public function acquireLock(): void
    {
        $this->projectorLock->acquireLock();
    }

    public function updateLock(): void
    {
        $this->projectorLock->updateLock();
    }

    public function releaseLock(): void
    {
        $this->projectorLock->releaseLock();
    }

    public function shouldUpdateLock(DateTimeImmutable $dateTime): bool
    {
        return $this->projectorLock->shouldUpdateLock($dateTime);
    }

    public function getStreamName(): string
    {
        return $this->projectorLock->getStreamName();
    }
}
