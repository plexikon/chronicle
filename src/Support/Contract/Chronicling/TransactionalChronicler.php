<?php

namespace Plexikon\Chronicle\Support\Contract\Chronicling;

use Plexikon\Chronicle\Exception\TransactionAlreadyStarted;
use Plexikon\Chronicle\Exception\TransactionNotStarted;

interface TransactionalChronicler extends Chronicler
{
    public const BEGIN_TRANSACTION_EVENT = 'begin_transaction_event';
    public const COMMIT_TRANSACTION_EVENT = 'commit_transaction_event';
    public const ROLLBACK_TRANSACTION_EVENT = 'rollback_transaction_event';

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
