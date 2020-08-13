<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\Pipe;

use Plexikon\Chronicle\Projector\ProjectorContext;
use Plexikon\Chronicle\Support\Contract\Projector\PipeOnce;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorRepository;

final class PersistentRunner implements PipeOnce
{
    private bool $hasBeenPrepared = false;
    private ProjectorRepository $projectorRepository;

    public function __construct(ProjectorRepository $projectorRepository)
    {
        $this->projectorRepository = $projectorRepository;
    }

    public function __invoke(ProjectorContext $context, callable $next)
    {
        if (!$this->hasBeenPrepared) {
            $this->hasBeenPrepared = true;

            if ($this->projectorRepository->updateOnStatus(true, $context->keepRunning())) {
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
