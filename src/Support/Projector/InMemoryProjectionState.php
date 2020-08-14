<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Support\Projector;

use Plexikon\Chronicle\Support\Contract\Chronicling\Model\ProjectionState;

final class InMemoryProjectionState implements ProjectionState
{
    /**
     * @var array<array>
     */
    private array $state = [];

    public function setState($state): void
    {
        if (is_array($state)) {
            $this->state = $state;
        }
    }

    /**
     * @return array<array>
     */
    public function getState(): array
    {
        return $this->state;
    }

    public function resetState(): void
    {
        $this->state = [];
    }
}
