<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Double;

final class SomeCommandHandler
{
    private bool $commandHandled = false;

    public function __invoke(SomeCommand $command): void
    {
        $this->commandHandled = true;
    }

    public function command(SomeCommand $command): void
    {
        $this->commandHandled = true;
    }

    public function isCommandHandled(): bool
    {
        return $this->commandHandled;
    }
}
