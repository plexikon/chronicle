<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\Pipe;

use Plexikon\Chronicle\Projector\ProjectionStatusLoader;
use Plexikon\Chronicle\Projector\ProjectorContext;
use Plexikon\Chronicle\Support\Contract\Projector\PipeOnce;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorLock;
use Plexikon\Chronicle\Support\Contract\Projector\ReadModel;

final class PersistentRunner implements PipeOnce
{
    private bool $hasBeenPrepared = false;
    private ProjectionStatusLoader $statusLoader;
    private ProjectorLock $lock;
    private ?ReadModel $readModel;

    public function __construct(ProjectionStatusLoader $statusLoader,
                                ProjectorLock $lock,
                                ?ReadModel $readModel)
    {
        $this->statusLoader = $statusLoader;
        $this->lock = $lock;
        $this->readModel = $readModel;
    }

    public function __invoke(ProjectorContext $context, callable $next)
    {
        if (!$this->hasBeenPrepared) {
            if ($this->statusLoader->fromRemote(true, $context->keepRunning())) {
                return true;
            }

            $this->preparePersistentRunner($context);

            $this->hasBeenPrepared = true;
        }

        return $next($context);
    }

    private function preparePersistentRunner(ProjectorContext $context): void
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
