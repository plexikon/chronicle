<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\Concerns;

use Plexikon\Chronicle\Support\Contract\Projector\Projector;

trait HasProjector
{
    protected ?Projector $projector;

    public function stop(): void
    {
        $this->projector->stop();
    }

    public function reset(): void
    {
        $this->projector->reset();
    }

    public function getState(): array
    {
        return $this->projector->getState();
    }
}
