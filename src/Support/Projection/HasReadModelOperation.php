<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Support\Projection;

trait HasReadModelOperation
{
    private array $stack = [];

    public function stack(string $operation, ...$args): void
    {
        $this->stack[] = [$operation, $args];
    }

    public function persist(): void
    {
        foreach ($this->stack as [$operation, $args]) {
            $this->{$operation}(...$args);
        }

        $this->stack = [];
    }
}
