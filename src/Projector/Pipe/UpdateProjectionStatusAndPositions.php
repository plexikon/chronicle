<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\Pipe;

use Plexikon\Chronicle\Projector\Concerns\HasRemoteProjectionStatus;
use Plexikon\Chronicle\Projector\ProjectorContext;
use Plexikon\Chronicle\Support\Contract\Projector\Pipe;

final class UpdateProjectionStatusAndPositions implements Pipe
{
    use HasRemoteProjectionStatus;

    public function __invoke(ProjectorContext $context, callable $next)
    {
        $this->processOnStatus(false, $context->keepRunning());

        $context->position->make($context->streamNames());

        return $next($context);
    }
}
