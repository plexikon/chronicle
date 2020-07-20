<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Chronicling;

use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Chronicling\TransactionalChronicler;
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageSubscriber;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageTracker;

final class TransactionSubscriber implements MessageSubscriber
{
    /**
     * @var Chronicler|TransactionalChronicler
     */
    private Chronicler $chronicler;

    public function __construct(Chronicler $chronicler)
    {
        $this->chronicler = $chronicler;
    }

    public function attachToTracker(MessageTracker $tracker): void
    {
        if (!$this->chronicler instanceof TransactionalChronicler) {
            return;
        }

        $tracker->listen(Reporter::DISPATCH_EVENT, function (): void {
            $this->chronicler->beginTransaction();
        }, 10000);

        $tracker->listen(Reporter::FINALIZE_EVENT, function (MessageContext $context): void {
            if (!$this->chronicler->inTransaction()) {
                return;
            }

            $context->hasException()
                ? $this->chronicler->rollbackTransaction()
                : $this->chronicler->commitTransaction();
        }, 100);
    }
}
