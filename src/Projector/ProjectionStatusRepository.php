<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector;

use Plexikon\Chronicle\Support\Contract\Projector\ProjectorRepository as BaseProjectorLock;

final class ProjectionStatusRepository
{
    private BaseProjectorLock $projectorLock;

    public function __construct(BaseProjectorLock $projectorLock)
    {
        $this->projectorLock = $projectorLock;
    }

    public function updateStatus(bool $shouldStop, bool $keepRunning): bool
    {
        switch ($this->projectorLock->loadStatus()) {
            case ProjectionStatus::STOPPING():
                if ($shouldStop) {
                    $this->projectorLock->loadState();
                }

                $this->projectorLock->stop();

                return $shouldStop;
            case ProjectionStatus::DELETING():
                $this->projectorLock->delete(false);

                return $shouldStop;
            case ProjectionStatus::DELETING_EMITTED_EVENTS():
                $this->projectorLock->delete(true);

                return $shouldStop;
            case ProjectionStatus::RESETTING():
                $this->projectorLock->reset();

                if (!$shouldStop && $keepRunning) {
                    $this->projectorLock->startAgain();
                }

                return false;
            default:
                return false;
        }
    }
}
