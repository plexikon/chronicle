<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\Pipe;

use Plexikon\Chronicle\Projector\ProjectionStatusLoader;
use Plexikon\Chronicle\Projector\ProjectorContext;
use Plexikon\Chronicle\Support\Contract\Projector\Pipe;

final class ProjectionReset implements Pipe
{
    private ProjectionStatusLoader $statusLoader;

    public function __construct(ProjectionStatusLoader $statusLoader)
    {
        $this->statusLoader = $statusLoader;
    }

    public function __invoke(ProjectorContext $context, callable $next)
    {
        $this->statusLoader->fromRemote(false, $context->keepRunning());

        $context->position->make($context->streamNames());

        return $next($context);
    }
}
