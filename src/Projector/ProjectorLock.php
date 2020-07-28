<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector;

use DateInterval;
use DateTimeImmutable;
use Plexikon\Chronicle\Exception\ProjectionNotFound;
use Plexikon\Chronicle\Support\Contract\Chronicling\Model\ProjectionProvider;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorLock as BaseProjectorLock;
use Plexikon\Chronicle\Support\Json;
use Plexikon\Chronicle\Support\Projector\LockWaitTime;

final class ProjectorLock implements BaseProjectorLock
{
    private ?DateTimeImmutable $lastLockUpdate = null;
    private ProjectorContext $projectorContext;
    private ProjectionProvider $projectionProvider;
    private string $streamName;

    public function __construct(ProjectorContext $projectorContext,
                                ProjectionProvider $projectionProvider,
                                string $streamName)
    {
        $this->projectorContext = $projectorContext;
        $this->projectionProvider = $projectionProvider;
        $this->streamName = $streamName;
    }

    public function createProjection(): void
    {
        $this->projectionProvider->newProjection(
            $this->streamName,
            $this->projectorContext->status->getValue()
        );
    }

    public function loadProjectionState(): void
    {
        $result = $this->projectionProvider->findByName($this->streamName);

        if (!$result) {
            $exceptionMessage = "Projection not found with stream name {$this->streamName}\n";
            $exceptionMessage .= 'Did you call prepareExecution first on Projector lock instance?';

            throw new ProjectionNotFound($exceptionMessage);
        }

        $this->projectorContext->streamPosition->mergeStreamsFromRemote(
            Json::decode($result->position())
        );

        if (!empty($state = Json::decode($result->state()))) {
            $this->projectorContext->state->setState($state);
        }
    }

    public function stopProjection(): void
    {
        $this->persistProjection();

        $this->projectorContext->isProjectionStopped = true;
        $idleProjection = ProjectionStatus::IDLE();

        $this->projectionProvider->updateStatus($this->streamName, [
            'status' => $idleProjection->getValue()
        ]);

        $this->projectorContext->status = $idleProjection;
    }

    public function startProjectionAgain(): void
    {
        $this->projectorContext->isProjectionStopped = false;
        $runningStatus = ProjectionStatus::RUNNING();
        $now = LockWaitTime::fromNow();

        $this->projectionProvider->updateStatus($this->streamName, [
            'status' => $runningStatus->getValue(),
            'locked_until' => $this->createLockUntilString($now)
        ]);

        $this->projectorContext->status = $runningStatus;
        $this->lastLockUpdate = $now->toDateTime();
    }

    public function persistProjection(): void
    {
        $this->projectionProvider->updateStatus($this->streamName, [
            'position' => Json::encode($this->projectorContext->streamPosition->all()),
            'state' => Json::encode($this->projectorContext->state->getState()),
            'locked_until' => $this->createLockUntilString(LockWaitTime::fromNow())
        ]);
    }

    public function resetProjection(): void
    {
        $this->projectorContext->streamPosition->reset();

        $callback = $this->projectorContext->initCallback();

        $this->projectorContext->state->resetState();

        if (is_callable($callback)) {
            $this->projectorContext->state->setState($callback());
        }

        $this->projectionProvider->updateStatus($this->streamName, [
            'position' => Json::encode($this->projectorContext->streamPosition->all()),
            'state' => Json::encode($this->projectorContext->state->getState()),
            'status' => $this->projectorContext->status->getValue()
        ]);
    }

    public function deleteProjection(bool $deleteEmittedEvents): void
    {
        $this->projectionProvider->deleteByName($this->streamName);

        $this->projectorContext->isProjectionStopped = true;
        $this->projectorContext->state->resetState();

        if (is_callable($callback = $this->projectorContext->initCallback())) {
            $this->projectorContext->state->setState($callback());
        }

        $this->projectorContext->streamPosition->reset();
    }

    public function fetchProjectionStatus(): ProjectionStatus
    {
        $result = $this->projectionProvider->findByName($this->streamName);

        if (!$result) {
            return ProjectionStatus::RUNNING();
        }

        return ProjectionStatus::byValue($result->status());
    }

    public function isProjectionExists(): bool
    {
        return $this->projectionProvider->projectionExists($this->streamName);
    }

    public function acquireLock(): void
    {
        $now = LockWaitTime::fromNow();
        $lockUntil = $this->createLockUntilString($now);
        $runningProjection = ProjectionStatus::RUNNING();

        $this->projectionProvider->acquireLock(
            $this->streamName,
            $runningProjection->getValue(),
            $lockUntil,
            $now->toString()
        );

        $this->projectorContext->status = $runningProjection;

        $this->lastLockUpdate = $now->toDateTime();
    }

    public function updateLock(): void
    {
        $now = LockWaitTime::fromNow();

        if ($this->shouldUpdateLock($now->toDateTime())) {
            $lockedUntil = $this->createLockUntilString($now);

            $this->projectionProvider->updateStatus($this->streamName, [
                'locked_until' => $lockedUntil,
                'position' => Json::encode($this->projectorContext->streamPosition->all())
            ]);

            $this->lastLockUpdate = $now->toDateTime();
        }
    }

    public function releaseLock(): void
    {
        $idleProjection = ProjectionStatus::IDLE();

        $this->projectionProvider->updateStatus($this->streamName, [
            'status' => $idleProjection->getValue(),
            'locked_until' => null
        ]);

        $this->projectorContext->status = $idleProjection;
    }

    public function shouldUpdateLock(DateTimeImmutable $dateTime): bool
    {
        $threshold = $this->projectorContext->options->updateLockThreshold();

        if (null === $this->lastLockUpdate || 0 === $threshold) {
            return true;
        }

        $updateLockThreshold = new DateInterval(sprintf('PT%sS', floor($threshold / 1000)));

        $updateLockThreshold->f = ($threshold % 1000) / 1000;

        $threshold = $this->lastLockUpdate->add($updateLockThreshold);

        return $threshold <= $dateTime;
    }

    public function getStreamName(): string
    {
        return $this->streamName;
    }

    private function createLockUntilString(LockWaitTime $dateTime): string
    {
        return $dateTime->createLockUntil($this->projectorContext->options->lockTimoutMs());
    }
}
