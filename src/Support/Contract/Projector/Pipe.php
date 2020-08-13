<?php

namespace Plexikon\Chronicle\Support\Contract\Projector;

use Plexikon\Chronicle\Projector\ProjectorContext;

interface Pipe
{
    /**
     * @param ProjectorContext $context
     * @param callable $next
     * @return callable|bool
     */
    public function __invoke(ProjectorContext $context, callable $next);
}
