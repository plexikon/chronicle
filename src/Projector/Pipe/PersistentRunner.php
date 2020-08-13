<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\Pipe;

use Plexikon\Chronicle\Projector\ProjectionStatusRepository;
use Plexikon\Chronicle\Projector\ProjectorContext;
use Plexikon\Chronicle\Support\Contract\Projector\PipeOnce;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorRepository;

final class PersistentRunner implements PipeOnce
{
    private bool $hasBeenPrepared = false;
    private ProjectionStatusRepository $statusRepository;
    private ProjectorRepository $projectorLock;

    public function __construct(ProjectionStatusRepository $statusLoader, ProjectorRepository $projectorLock)
    {
        $this->statusRepository = $statusLoader;
        $this->projectorLock = $projectorLock;
    }

    public function __invoke(ProjectorContext $context, callable $next)
    {
        if (!$this->hasBeenPrepared) {
            $this->hasBeenPrepared = true;

            if ($this->statusRepository->updateStatus(true, $context->keepRunning())) {
                return true;
            }

            $this->projectorLock->prepare(null);
        }

        return $next($context);
    }

    public function isAlreadyPiped(): bool
    {
        return $this->hasBeenPrepared;
    }
}
