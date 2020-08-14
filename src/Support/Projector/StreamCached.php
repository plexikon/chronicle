<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Support\Projector;

use Plexikon\Chronicle\Exception\Assert;
use function in_array;

final class StreamCached
{
    /**
     * @var array<int,null|string>
     */
    private array $container;
    private int $size;
    private int $position = -1;

    public function __construct(int $size)
    {
        Assert::that($size, 'Size must be greater than 0')->greaterThan(0);

        $this->size = $size;

        $this->container = array_fill(0, $size, null);
    }

    public function toNextPosition(string $value): void
    {
        $this->container[$this->nextPosition()] = $value;
    }

    public function getStreamAt(int $position): ?string
    {
        Assert::that($position, "Position must be between 0 and " . ($this->size - 1))
            ->between(0, $this->size - 1);

        return $this->container[$position];
    }

    public function has(string $value): bool
    {
        return in_array($value, $this->container, true);
    }

    public function size(): int
    {
        return $this->size;
    }

    public function all(): array
    {
        return $this->container;
    }

    private function nextPosition(): int
    {
        return $this->position = ++$this->position % $this->size;
    }
}
