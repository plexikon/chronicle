<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\Pipe;

use Plexikon\Chronicle\Projector\ProjectorContext;
use Plexikon\Chronicle\Support\Contract\Projector\Pipe;

final class SignalDispatcher implements Pipe
{
    public function __invoke(ProjectorContext $context, callable $next)
    {
        $context->dispatchSignal();

        return $next($context);
    }
}
