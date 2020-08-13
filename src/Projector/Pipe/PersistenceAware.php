<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\Pipe;

use Plexikon\Chronicle\Projector\ProjectorContext;
use Plexikon\Chronicle\Support\Contract\Projector\Pipe;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorRepository;

final class PersistenceAware implements Pipe
{
    private ProjectorRepository $projectorRepository;

    public function __construct(ProjectorRepository $projectorRepository)
    {
        $this->projectorRepository = $projectorRepository;
    }

    public function __invoke(ProjectorContext $context, callable $next)
    {
        $context->counter->isReset()
            ? $this->sleepBeforeUpdateLock($context->option->sleep())
            : $this->projectorRepository->persist();

        $context->counter->reset();

        return $next($context);
    }

    private function sleepBeforeUpdateLock(int $sleep): void
    {
        usleep($sleep);

        $this->projectorRepository->updateLock();
    }
}
