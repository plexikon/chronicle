<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Chronicling;

use Plexikon\Chronicle\Exception\TransactionAlreadyStarted;
use Plexikon\Chronicle\Exception\TransactionNotStarted;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Chronicling\TransactionalChronicler;
use Plexikon\Chronicle\Support\Contract\Tracker\EventTracker;
use Plexikon\Chronicle\Support\Contract\Tracker\TransactionalEventContext;
use Plexikon\Chronicle\Support\Contract\Tracker\TransactionalEventTracker;

final class TransactionalEventChronicler extends EventChronicler implements TransactionalChronicler
{
    /**
     * @var Chronicler|TransactionalChronicler
     */
    protected Chronicler $chronicler;
    protected EventTracker $tracker;

    public function __construct(TransactionalChronicler $chronicler, TransactionalEventTracker $tracker)
    {
        parent::__construct($chronicler, $tracker);

        $this->tracker->listen(self::BEGIN_TRANSACTION_EVENT,
            function (TransactionalEventContext $context): void {
                try {
                    $this->chronicler->beginTransaction();
                } catch (TransactionAlreadyStarted $exception) {
                    $context->withRaisedException($exception);
                }
            });

        $this->tracker->listen(self::COMMIT_TRANSACTION_EVENT,
            function (TransactionalEventContext $context): void {
                try {
                    $this->chronicler->commitTransaction();
                } catch (TransactionNotStarted $exception) {
                    $context->withRaisedException($exception);
                }
            });

        $this->tracker->listen(self::ROLLBACK_TRANSACTION_EVENT,
            function (TransactionalEventContext $context): void {
                try {
                    $this->chronicler->rollbackTransaction();
                } catch (TransactionNotStarted $exception) {
                    $context->withRaisedException($exception);
                }
            });
    }

    public function beginTransaction(): void
    {
        /** @var TransactionalEventContext $context */
        $context = $this->tracker->newContext(self::BEGIN_TRANSACTION_EVENT);

        $this->tracker->fire($context);

        if ($context->hasTransactionAlreadyStarted()) {
            throw $context->getException();
        }
    }

    public function commitTransaction(): void
    {
        /** @var TransactionalEventContext $context */
        $context = $this->tracker->newContext(self::COMMIT_TRANSACTION_EVENT);

        $this->tracker->fire($context);

        if ($context->hasTransactionNotStarted()) {
            throw $context->getException();
        }
    }

    public function rollbackTransaction(): void
    {
        /** @var TransactionalEventContext $context */
        $context = $this->tracker->newContext(self::ROLLBACK_TRANSACTION_EVENT);

        $this->tracker->fire($context);

        if ($context->hasTransactionNotStarted()) {
            throw $context->getException();
        }
    }

    public function inTransaction(): bool
    {
        return $this->chronicler->inTransaction();
    }

    public function transactional(callable $callable)
    {
        return $this->chronicler->transactional($callable);
    }
}
