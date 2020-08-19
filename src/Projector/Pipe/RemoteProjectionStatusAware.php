<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\Pipe;

use Plexikon\Chronicle\Projector\ProjectionStatus;
use Plexikon\Chronicle\Support\Contract\Projector\Pipe;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorRepository;

abstract class RemoteProjectionStatusAware implements Pipe
{
    protected ProjectorRepository $projectorRepository;

    public function __construct(ProjectorRepository $projectorRepository)
    {
        $this->projectorRepository = $projectorRepository;
    }

    protected function processOnStatus(bool $shouldStop, bool $keepRunning): bool
    {
        switch ($this->projectorRepository->loadStatus()) {
            case ProjectionStatus::STOPPING():
                if ($shouldStop) {
                    $this->projectorRepository->loadState();
                }

                $this->projectorRepository->stop();

                return $shouldStop;
            case ProjectionStatus::DELETING():
                $this->projectorRepository->delete(false);

                return $shouldStop;
            case ProjectionStatus::DELETING_EMITTED_EVENTS():
                $this->projectorRepository->delete(true);

                return $shouldStop;
            case ProjectionStatus::RESETTING():
                $this->projectorRepository->reset();

                if (!$shouldStop && $keepRunning) {
                    $this->projectorRepository->startAgain();
                }

                return false;
            default:
                return false;
        }
    }
}
