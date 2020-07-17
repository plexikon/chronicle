<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tracker;

use Plexikon\Chronicle\Exception\TransactionAlreadyStarted;
use Plexikon\Chronicle\Exception\TransactionNotStarted;
use Plexikon\Chronicle\Support\Contract\Tracker\TransactionalEventContext as BaseTransactionalEventContext;

final class TransactionalEventContext extends DefaultEventContext implements BaseTransactionalEventContext
{
    public function hasTransactionNotStarted(): bool
    {
        return $this->exception instanceof TransactionNotStarted;
    }

    public function hasTransactionAlreadyStarted(): bool
    {
        return $this->exception instanceof TransactionAlreadyStarted;
    }
}
