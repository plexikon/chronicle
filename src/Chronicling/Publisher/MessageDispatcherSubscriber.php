<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Chronicling\Publisher;

use Generator;
use Illuminate\Support\Arr;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Chronicling\EventChronicler;
use Plexikon\Chronicle\Support\Contract\Chronicling\MessageDispatcher as Dispatcher;
use Plexikon\Chronicle\Support\Contract\Chronicling\TransactionalChronicler;
use Plexikon\Chronicle\Support\Contract\Tracker\EventContext;
use Plexikon\Chronicle\Support\Contract\Tracker\EventSubscriber;

final class MessageDispatcherSubscriber implements EventSubscriber
{
    private array $recordedStreams = [];
    private Dispatcher $messageDispatcher;

    public function __construct(Dispatcher $messageDispatcher)
    {
        $this->messageDispatcher = $messageDispatcher;
    }

    public function attachToChronicler(Chronicler $chronicler): void
    {
        if ($chronicler instanceof EventChronicler) {
            $this->subscribeToEventChronicle($chronicler);
        }

        if ($chronicler instanceof TransactionalChronicler) {
            $this->subscribeToTransactionalChronicle($chronicler);
        }
    }

    private function subscribeToEventChronicle(EventChronicler $chronicler): void
    {
        $chronicler->subscribe($chronicler::PERSIST_STREAM_EVENT,
            function (EventContext $context) use ($chronicler): void {
                $recordedEvents = $context->stream()->events();

                if (!$this->inTransaction($chronicler)) {
                    if (!$context->hasStreamNotFound() && !$context->hasRaceCondition()) {
                        $this->messageDispatcher->dispatch(...$recordedEvents);
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
                        $this->messageDispatcher->dispatch(...$streamEvents);
                    }
                } else {
                    $this->recordEvents($streamEvents);
                }
            });
    }

    /**
     * @param TransactionalChronicler|EventChronicler $chronicler
     */
    private function subscribeToTransactionalChronicle(TransactionalChronicler $chronicler): void
    {
        $chronicler->subscribe($chronicler::COMMIT_TRANSACTION_EVENT,
            function () {
                foreach ($this->recordedStreams as $recordedStream) {
                    $this->messageDispatcher->dispatch($recordedStream);
                }

                $this->recordedStreams = [];
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

        $this->recordedStreams += $events;
    }

    private function inTransaction(Chronicler $chronicle): bool
    {
        return $chronicle instanceof TransactionalChronicler && $chronicle->inTransaction();
    }
}
