<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Chronicling\WriteLock;


use Plexikon\Chronicle\Support\Contract\Chronicling\WriteLockStrategy;

final class NoWriteLock implements WriteLockStrategy
{
    public function getLock(string $name): bool
    {
       return true;
    }

    public function releaseLock(string $name): bool
    {
        return true;
    }
}
