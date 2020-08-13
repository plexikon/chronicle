<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector;

use Plexikon\Chronicle\Support\Contract\Projector\ProjectorLock as BaseProjectorLock;

final class ProjectionStatusRepository
{
    private BaseProjectorLock $projectorLock;

    public function __construct(BaseProjectorLock $projectorLock)
    {
        $this->projectorLock = $projectorLock;
    }

    public function updateStatus(bool $shouldStop, bool $keepRunning): bool
    {
        switch ($this->projectorLock->fetchProjectionStatus()) {
            case ProjectionStatus::STOPPING():
                if ($shouldStop) {
                    $this->projectorLock->loadProjectionState();
                }

                $this->projectorLock->stopProjection();

                return $shouldStop;
            case ProjectionStatus::DELETING():
                $this->projectorLock->deleteProjection(false);

                return $shouldStop;
            case ProjectionStatus::DELETING_EMITTED_EVENTS():
                $this->projectorLock->deleteProjection(true);

                return $shouldStop;
            case ProjectionStatus::RESETTING():
                $this->projectorLock->resetProjection();

                if (!$shouldStop && $keepRunning) {
                    $this->projectorLock->startProjectionAgain();
                }

                return false;
            default:
                return false;
        }
    }
}
