<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Chronicling\Publisher;

use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Chronicling\TransactionalChronicler;
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Support\Contract\Tracker\EventTracker;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageSubscriber;
use Plexikon\Chronicle\Support\Contract\Tracker\Tracker;

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

    public function attachToTracker(Tracker $tracker): void
    {
        if (!$this->chronicler instanceof TransactionalChronicler || $tracker instanceof EventTracker) {
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
                ? $this->chronicler->rollbackTransaction() : $this->chronicler->commitTransaction();
        }, 100);
    }
}
