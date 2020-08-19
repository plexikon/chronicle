<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\Pipe;

use Plexikon\Chronicle\Projector\ProjectorContext;
use Plexikon\Chronicle\Support\Contract\Projector\PipeOnce;

final class PreparePersistenceRunner extends RemoteProjectionStatusAware implements PipeOnce
{
    private bool $hasBeenPrepared = false;

    public function __invoke(ProjectorContext $context, callable $next)
    {
        if (!$this->hasBeenPrepared) {
            $this->hasBeenPrepared = true;

            if ($this->processOnStatus(true, $context->keepRunning())) {
                return true;
            }

            $this->projectorRepository->prepare(null);
        }

        return $next($context);
    }

    public function isAlreadyPiped(): bool
    {
        return $this->hasBeenPrepared;
    }
}
