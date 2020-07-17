<?php

namespace Plexikon\Chronicle\Support\Contract\Chronicling;

use Plexikon\Chronicle\Exception\TransactionAlreadyStarted;
use Plexikon\Chronicle\Exception\TransactionNotStarted;

interface TransactionalChronicler extends Chronicler
{
    /**
     * @throws TransactionAlreadyStarted
     */
    public function beginTransaction(): void;

    /**
     * @throws TransactionNotStarted
     */
    public function commitTransaction(): void;

    /**
     * @throws TransactionNotStarted
     */
    public function rollbackTransaction(): void;

    /**
     * @return bool
     */
    public function inTransaction(): bool;
}
