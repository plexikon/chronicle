<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\Pipe;

use Plexikon\Chronicle\Projector\ProjectionStatusRepository;
use Plexikon\Chronicle\Projector\ProjectorContext;
use Plexikon\Chronicle\Support\Contract\Projector\Pipe;

final class ProjectionReset implements Pipe
{
    private ProjectionStatusRepository $statusRepository;

    public function __construct(ProjectionStatusRepository $statusRepository)
    {
        $this->statusRepository = $statusRepository;
    }

    public function __invoke(ProjectorContext $context, callable $next)
    {
        $this->statusRepository->updateStatus(false, $context->keepRunning());

        $context->position->make($context->streamNames());

        return $next($context);
    }
}
