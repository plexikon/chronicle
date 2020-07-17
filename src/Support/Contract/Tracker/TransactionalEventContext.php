<?php

namespace Plexikon\Chronicle\Support\Contract\Tracker;

interface TransactionalEventContext extends EventContext
{
    /**
     * @return bool
     */
    public function hasTransactionNotStarted(): bool;

    /**
     * @return bool
     */
    public function hasTransactionAlreadyStarted(): bool;
}
