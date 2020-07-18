<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Support\Connection;

use Illuminate\Database\ConnectionInterface;
use Plexikon\Chronicle\Exception\TransactionAlreadyStarted;
use Plexikon\Chronicle\Exception\TransactionNotStarted;
use Throwable;

trait HasConnectionTransaction
{
    protected ConnectionInterface $connection;

    public function beginTransaction(): void
    {
        if ($this->isTransactionDisabled()) {
            return;
        }

        try {
            $this->connection->beginTransaction();
        } catch (Throwable $exception) {
            throw new TransactionAlreadyStarted('Transaction already started');
        }
    }

    public function commitTransaction(): void
    {
        if ($this->isTransactionDisabled()) {
            return;
        }

        try {
            $this->connection->commit();
        } catch (Throwable $exception) {
            throw new TransactionNotStarted('Transaction not started');
        }
    }

    public function rollbackTransaction(): void
    {
        if ($this->isTransactionDisabled()) {
            return;
        }

        try {
            $this->connection->rollBack();
        } catch (Throwable $exception) {
            throw new TransactionNotStarted('Transaction not started');
        }
    }

    public function inTransaction(): bool
    {
        return !$this->isTransactionDisabled() && $this->connection->transactionLevel() > 0;
    }

    /**
     * @return bool
     */
    abstract protected function isTransactionDisabled(): bool;
}
