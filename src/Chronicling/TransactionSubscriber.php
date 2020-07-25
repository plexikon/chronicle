<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Chronicling;

use Plexikon\Chronicle\Support\Contract\Chronicling\TransactionalChronicler;
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageSubscriber;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageTracker;

final class TransactionSubscriber implements MessageSubscriber
{
    private TransactionalChronicler $chronicler;

    public function __construct(TransactionalChronicler $chronicler)
    {
        $this->chronicler = $chronicler;
    }

    public function attachToTracker(MessageTracker $tracker): void
    {
        $tracker->listen(Reporter::DISPATCH_EVENT, function (): void {
            $this->chronicler->beginTransaction();
        }, 1000);

        $tracker->listen(Reporter::FINALIZE_EVENT, function (MessageContext $context): void {
            if (!$this->chronicler->inTransaction()) {
                return;
            }

            $context->hasException()
                ? $this->chronicler->rollbackTransaction()
                : $this->chronicler->commitTransaction();
        }, 1000);
    }
}
