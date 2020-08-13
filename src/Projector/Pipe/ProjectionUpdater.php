<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\Pipe;

use Plexikon\Chronicle\Projector\ProjectorContext;
use Plexikon\Chronicle\Support\Contract\Projector\Pipe;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorLock;

final class ProjectionUpdater implements Pipe
{
    private ProjectorLock $lock;

    public function __construct(ProjectorLock $lock)
    {
        $this->lock = $lock;
    }

    public function __invoke(ProjectorContext $context, callable $next)
    {
        $context->counter->isReset()
            ? $this->sleepBeforeUpdateLock($context->option->sleep())
            : $this->lock->persistProjection();

        $context->counter->reset();

        return $next($context);
    }

    private function sleepBeforeUpdateLock(int $sleep): void
    {
        usleep($sleep);

        $this->lock->updateLock();
    }
}
