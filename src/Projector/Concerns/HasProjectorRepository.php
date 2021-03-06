<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\Concerns;

use DateTimeImmutable;
use Plexikon\Chronicle\Projector\ProjectionStatus;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorRepository;

trait HasProjectorRepository
{
    protected ProjectorRepository $projectorRepository;

    public function create(): void
    {
        $this->projectorRepository->create();
    }

    public function loadState(): void
    {
        $this->projectorRepository->loadState();
    }

    public function stop(): void
    {
        $this->projectorRepository->stop();
    }

    public function startAgain(): void
    {
        $this->projectorRepository->startAgain();
    }

    public function isProjectionExists(): bool
    {
        return $this->projectorRepository->isProjectionExists();
    }

    public function loadStatus(): ProjectionStatus
    {
        return $this->projectorRepository->loadStatus();
    }

    public function acquireLock(): void
    {
        $this->projectorRepository->acquireLock();
    }

    public function updateLock(): void
    {
        $this->projectorRepository->updateLock();
    }

    public function releaseLock(): void
    {
        $this->projectorRepository->releaseLock();
    }

    public function shouldUpdateLock(DateTimeImmutable $dateTime): bool
    {
        return $this->projectorRepository->shouldUpdateLock($dateTime);
    }

    public function getStreamName(): string
    {
        return $this->projectorRepository->getStreamName();
    }
}
