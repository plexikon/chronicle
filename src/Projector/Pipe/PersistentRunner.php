<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\Pipe;

use Plexikon\Chronicle\Projector\ProjectionStatusRepository;
use Plexikon\Chronicle\Projector\ProjectorContext;
use Plexikon\Chronicle\Support\Contract\Projector\PipeOnce;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorLock;
use Plexikon\Chronicle\Support\Contract\Projector\ReadModel;

final class PersistentRunner implements PipeOnce
{
    private bool $hasBeenPrepared = false;
    private ProjectionStatusRepository $statusRepository;
    private ProjectorLock $lock;
    private ?ReadModel $readModel;

    public function __construct(ProjectionStatusRepository $statusLoader,
                                ProjectorLock $lock,
                                ?ReadModel $readModel)
    {
        $this->statusRepository = $statusLoader;
        $this->lock = $lock;
        $this->readModel = $readModel;
    }

    public function __invoke(ProjectorContext $context, callable $next)
    {
        if (!$this->hasBeenPrepared) {
            $this->hasBeenPrepared = true;

            if ($this->statusRepository->updateStatus(true, $context->keepRunning())) {
                return true;
            }

            $this->prepareProjection($context);
        }

        return $next($context);
    }

    private function prepareProjection(ProjectorContext $context): void
    {
        $context->isStopped = false;

        if (!$this->lock->isProjectionExists()) {
            $this->lock->createProjection();
        }

        $this->lock->acquireLock();

        if ($this->readModel && !$this->readModel->isInitialized()) {
            $this->readModel->initialize();
        }

        $context->position->make($context->streamNames());

        $this->lock->loadProjectionState();
    }

    public function isAlreadyPiped(): bool
    {
        return $this->hasBeenPrepared;
    }
}
