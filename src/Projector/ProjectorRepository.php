<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector;

use DateInterval;
use DateTimeImmutable;
use Plexikon\Chronicle\Exception\ProjectionNotFound;
use Plexikon\Chronicle\Support\Contract\Chronicling\Model\ProjectionProvider;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorRepository as BaseProjectorLock;
use Plexikon\Chronicle\Support\Contract\Projector\ReadModel;
use Plexikon\Chronicle\Support\Json;
use Plexikon\Chronicle\Support\Projector\LockWaitTime;

final class ProjectorRepository implements BaseProjectorLock
{
    private ?DateTimeImmutable $lastLockUpdate = null;
    private ProjectorContext $context;
    private ProjectionProvider $provider;
    private string $streamName;

    public function __construct(ProjectorContext $context,
                                ProjectionProvider $provider,
                                string $streamName)
    {
        $this->context = $context;
        $this->provider = $provider;
        $this->streamName = $streamName;
    }

    public function prepare(?ReadModel $readModel): void
    {
        $this->context->isStopped = false;

        if (!$this->isProjectionExists()) {
            $this->create();
        }

        $this->acquireLock();

        if ($readModel && !$readModel->isInitialized()) {
            $readModel->initialize();
        }

        $this->context->position->make($this->context->streamNames());

        $this->loadState();
    }

    public function create(): void
    {
        $this->provider->newProjection(
            $this->streamName,
            $this->context->status->getValue()
        );
    }

    public function stop(): void
    {
        $this->persist();

        $this->context->isStopped = true;
        $idleProjection = ProjectionStatus::IDLE();

        $this->provider->updateStatus($this->streamName, [
            'status' => $idleProjection->getValue()
        ]);

        $this->context->status = $idleProjection;
    }

    public function startAgain(): void
    {
        $this->context->isStopped = false;
        $runningStatus = ProjectionStatus::RUNNING();
        $now = LockWaitTime::fromNow();

        $this->provider->updateStatus($this->streamName, [
            'status' => $runningStatus->getValue(),
            'locked_until' => $this->createLockUntilString($now)
        ]);

        $this->context->status = $runningStatus;
        $this->lastLockUpdate = $now->toDateTime();
    }

    public function persist(): void
    {
        $this->provider->updateStatus($this->streamName, [
            'position' => Json::encode($this->context->position->all()),
            'state' => Json::encode($this->context->state->getState()),
            'locked_until' => $this->createLockUntilString(LockWaitTime::fromNow())
        ]);
    }

    public function reset(): void
    {
        $this->context->position->reset();

        $callback = $this->context->initCallback();

        $this->context->state->resetState();

        if (is_callable($callback)) {
            $this->context->state->setState($callback());
        }

        $this->provider->updateStatus($this->streamName, [
            'position' => Json::encode($this->context->position->all()),
            'state' => Json::encode($this->context->state->getState()),
            'status' => $this->context->status->getValue()
        ]);
    }

    public function delete(bool $deleteEmittedEvents): void
    {
        $this->provider->deleteByName($this->streamName);

        $this->context->isStopped = true;
        $this->context->state->resetState();

        if (is_callable($callback = $this->context->initCallback())) {
            $this->context->state->setState($callback());
        }

        $this->context->position->reset();
    }

    public function loadState(): void
    {
        $result = $this->provider->findByName($this->streamName);

        if (!$result) {
            $exceptionMessage = "Projection not found with stream name {$this->streamName}\n";
            $exceptionMessage .= 'Did you call prepareExecution first on Projector lock instance?';

            throw new ProjectionNotFound($exceptionMessage);
        }

        $this->context->position->mergeStreamsFromRemote(
            Json::decode($result->position())
        );

        if (!empty($state = Json::decode($result->state()))) {
            $this->context->state->setState($state);
        }
    }

    public function loadStatus(): ProjectionStatus
    {
        $result = $this->provider->findByName($this->streamName);

        if (!$result) {
            return ProjectionStatus::RUNNING();
        }

        return ProjectionStatus::byValue($result->status());
    }

    public function updateOnCounter(): void
    {
        $persistBlockSize = $this->context->option->persistBlockSize();

        if ($this->context->counter->equals($persistBlockSize)) {
            $this->persist();

            $this->context->counter->reset();

            $this->context->status = $this->loadStatus();

            $keepProjectionRunning = [ProjectionStatus::RUNNING(), ProjectionStatus::IDLE()];

            if (!in_array($this->context->status, $keepProjectionRunning)) {
                $this->context->isStopped = true;
            }
        }
    }

    public function isProjectionExists(): bool
    {
        return $this->provider->projectionExists($this->streamName);
    }

    public function acquireLock(): void
    {
        $now = LockWaitTime::fromNow();
        $lockUntil = $this->createLockUntilString($now);
        $runningProjection = ProjectionStatus::RUNNING();

        $this->provider->acquireLock(
            $this->streamName,
            $runningProjection->getValue(),
            $lockUntil,
            $now->toString()
        );

        $this->context->status = $runningProjection;

        $this->lastLockUpdate = $now->toDateTime();
    }

    public function updateLock(): void
    {
        $now = LockWaitTime::fromNow();

        if ($this->shouldUpdateLock($now->toDateTime())) {
            $lockedUntil = $this->createLockUntilString($now);

            $this->provider->updateStatus($this->streamName, [
                'locked_until' => $lockedUntil,
                'position' => Json::encode($this->context->position->all())
            ]);

            $this->lastLockUpdate = $now->toDateTime();
        }
    }

    public function releaseLock(): void
    {
        $idleProjection = ProjectionStatus::IDLE();

        $this->provider->updateStatus($this->streamName, [
            'status' => $idleProjection->getValue(),
            'locked_until' => null
        ]);

        $this->context->status = $idleProjection;
    }

    public function shouldUpdateLock(DateTimeImmutable $dateTime): bool
    {
        $threshold = $this->context->option->updateLockThreshold();

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
        return $dateTime->createLockUntil($this->context->option->lockTimoutMs());
    }
}
