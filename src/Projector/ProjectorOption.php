<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector;

use Plexikon\Chronicle\Exception\RuntimeException;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorOption as BaseProjectorOption;

final class ProjectorOption implements BaseProjectorOption
{
    protected array $options = [
        self::OPTION_PCNTL_DISPATCH => false,
        self::OPTION_LOCK_TIMEOUT_MS => 1000,
        self::OPTION_SLEEP => 10000,
        self::OPTION_PERSIST_BLOCK_SIZE => 1000,
        self::OPTION_UPDATE_LOCK_THRESHOLD => 0
    ];

    public function __construct(array $options = [])
    {
        $this->mergeOptions($options);
    }

    public function dispatchSignal(): bool
    {
        return $this->options[static::OPTION_PCNTL_DISPATCH];
    }

    public function lockTimoutMs(): int
    {
        return $this->options[static::OPTION_LOCK_TIMEOUT_MS];
    }

    public function sleep(): int
    {
        return $this->options[static::OPTION_SLEEP];
    }

    public function persistBlockSize(): int
    {
        return $this->options[static::OPTION_PERSIST_BLOCK_SIZE];
    }

    public function updateLockThreshold(): int
    {
        return $this->options[static::OPTION_UPDATE_LOCK_THRESHOLD];
    }

    public function withOptions(array $options): void
    {
        $this->mergeOptions($options);
    }

    private function mergeOptions(array $options): void
    {
        foreach ($options as $option => $default) {
            if (!array_key_exists($option, $this->options)) {
                throw new RuntimeException("Projector option $option does not exists");
            }

            if (!is_integer($default) && (!is_bool($default) && $default >= 0)) {
                throw new RuntimeException("Projector option value accept positive integer (or 0) and boolean value only");
            }

            $this->options[$option] = $default;
        }
    }
}
