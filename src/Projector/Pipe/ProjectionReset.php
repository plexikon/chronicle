<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\Pipe;

use Plexikon\Chronicle\Projector\ProjectorContext;
use Plexikon\Chronicle\Support\Contract\Projector\Pipe;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorRepository;

final class ProjectionReset implements Pipe
{
    private ProjectorRepository $projectorRepository;

    public function __construct(ProjectorRepository $projectorRepository)
    {
        $this->projectorRepository = $projectorRepository;
    }

    public function __invoke(ProjectorContext $context, callable $next)
    {
        $this->projectorRepository->updateOnStatus(false, $context->keepRunning());

        $context->position->make($context->streamNames());

        return $next($context);
    }
}
