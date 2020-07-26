<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Chronicling;

use Plexikon\Chronicle\InMemoryChronicler;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Chronicling\EventDispatcher;
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageSubscriber;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageTracker;

final class InMemoryEventDispatcherSubscriber implements MessageSubscriber
{
    private array $cachedEvents = [];
    private array $eventListeners = [];
    private EventDispatcher $eventDispatcher;

    /**
     * @var Chronicler|InMemoryChronicler
     */
    private Chronicler $chronicler;

    public function __construct(Chronicler $chronicler, EventDispatcher $eventDispatcher)
    {
        $this->chronicler = $chronicler;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function attachToTracker(MessageTracker $tracker): void
    {
        if (!$this->chronicler instanceof InMemoryChronicler) {
            return;
        }

        $this->eventListeners[] = $tracker->listen(Reporter::DISPATCH_EVENT,
            function (): void {
                $this->chronicler->beginTransaction();
            }, 1000);


        $this->eventListeners [] = $tracker->listen(Reporter::DISPATCH_EVENT,
            function (): void {
                if ($this->chronicler->inTransaction()) {
                    $this->cachedEvents = $this->chronicler->getCachedEvents();

                    if (!empty($this->cachedEvents)) {
                        $this->chronicler->commitTransaction();

                        $this->eventDispatcher->dispatch(...$this->cachedEvents);

                        $this->cachedEvents = [];
                    }
                }
            });

        $this->eventListeners [] = $tracker->listen(Reporter::FINALIZE_EVENT,
            function (MessageContext $context): void {
                if ($context->hasException()) {
                    $this->chronicler->rollbackTransaction();
                }
            });
    }
}
