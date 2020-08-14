<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Double;

use Plexikon\Chronicle\Reporter\Command;

final class SomeCommand extends Command
{
    public static function withData(array $payload): self
    {
        return new self($payload);
    }
}
