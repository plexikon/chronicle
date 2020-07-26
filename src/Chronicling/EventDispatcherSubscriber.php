<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Chronicling;

use Generator;
use Illuminate\Support\Arr;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Chronicling\EventChronicler;
use Plexikon\Chronicle\Support\Contract\Chronicling\EventDispatcher;
use Plexikon\Chronicle\Support\Contract\Chronicling\TransactionalChronicler;
use Plexikon\Chronicle\Support\Contract\Tracker\EventContext;
use Plexikon\Chronicle\Support\Contract\Tracker\EventSubscriber;

final class EventDispatcherSubscriber implements EventSubscriber
{
    private array $recordedStreams = [];
    private EventDispatcher $eventDispatcher;

    public function __construct(EventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function attachToChronicler(Chronicler $chronicler): void
    {
        if ($chronicler instanceof EventChronicler) {
            $this->subscribeToEventChronicler($chronicler);
        }

        if ($chronicler instanceof TransactionalChronicler) {
            $this->subscribeToTransactionalChronicler($chronicler);
        }
    }

    private function subscribeToEventChronicler(EventChronicler $chronicler): void
    {
        $chronicler->subscribe($chronicler::PERSIST_STREAM_EVENT,
            function (EventContext $context) use ($chronicler): void {
                $recordedEvents = $context->stream()->events();

                if (!$this->inTransaction($chronicler)) {
                    if (!$context->hasStreamNotFound() && !$context->hasRaceCondition()) {
                        $this->eventDispatcher->dispatch(...$recordedEvents);
                    }
                } else {
                    $this->recordEvents($recordedEvents);
                }
            });

        $chronicler->subscribe($chronicler::FIRST_COMMIT_EVENT,
            function (EventContext $context) use ($chronicler): void {
                $stream = $context->stream();
                $streamEvents = iterator_to_array($stream->events());

                if (0 === count($streamEvents)) {
                    return;
                }

                if (!$this->inTransaction($chronicler)) {
                    if (!$context->hasStreamAlreadyExits()) {
                        $this->eventDispatcher->dispatch(...$streamEvents);
                    }
                } else {
                    $this->recordEvents($streamEvents);
                }
            });
    }

    /**
     * @param TransactionalChronicler|EventChronicler $chronicler
     */
    private function subscribeToTransactionalChronicler(TransactionalChronicler $chronicler): void
    {
        $chronicler->subscribe($chronicler::COMMIT_TRANSACTION_EVENT,
            function () {
                $recordedStreams = $this->recordedStreams;

                $this->recordedStreams = [];

                $this->eventDispatcher->dispatch(...$recordedStreams);
            });

        $chronicler->subscribe($chronicler::ROLLBACK_TRANSACTION_EVENT,
            function () {
                $this->recordedStreams = [];
            });
    }

    private function recordEvents(iterable $events): void
    {
        if ($events instanceof Generator) {
            $events = iterator_to_array($events);
        }

        $events = Arr::flatten($events);

        $this->recordedStreams += $events;//fixMe
    }

    private function inTransaction(Chronicler $chronicler): bool
    {
        return $chronicler instanceof TransactionalChronicler && $chronicler->inTransaction();
    }
}
