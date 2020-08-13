<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\Pipe;

use Plexikon\Chronicle\Projector\ProjectionStatusRepository;
use Plexikon\Chronicle\Projector\ProjectorContext;
use Plexikon\Chronicle\Support\Contract\Projector\PipeOnce;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorLock;

final class PersistentRunner implements PipeOnce
{
    private bool $hasBeenPrepared = false;
    private ProjectionStatusRepository $statusRepository;
    private ProjectorLock $lock;

    public function __construct(ProjectionStatusRepository $statusLoader, ProjectorLock $lock)
    {
        $this->statusRepository = $statusLoader;
        $this->lock = $lock;
    }

    public function __invoke(ProjectorContext $context, callable $next)
    {
        if (!$this->hasBeenPrepared) {
            $this->hasBeenPrepared = true;

            if ($this->statusRepository->updateStatus(true, $context->keepRunning())) {
                return true;
            }

            $this->lock->prepareProjection($context);
        }

        return $next($context);
    }

    public function isAlreadyPiped(): bool
    {
        return $this->hasBeenPrepared;
    }
}
