<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Support\Contract\Projector;

interface ProjectorOption
{
    public const OPTION_PCNTL_DISPATCH = 'trigger_pcntl_dispatch';

    public const OPTION_SLEEP = 'sleep';

    public const OPTION_PERSIST_BLOCK_SIZE = 'persist_block_size';

    public const OPTION_LOCK_TIMEOUT_MS = 'lock_timeout_ms';

    public const OPTION_UPDATE_LOCK_THRESHOLD = 'update_lock_threshold';

    /**
     * @return bool
     */
    public function dispatchSignal(): bool;

    /**
     * @return int
     */
    public function lockTimoutMs(): int;

    /**
     * @return int
     */
    public function sleep(): int;

    /**
     * @return int
     */
    public function persistBlockSize(): int;

    /**
     * @return int
     */
    public function updateLockThreshold(): int;

    /**
     * @param array $options
     */
    public function withOptions(array $options): void;

}
