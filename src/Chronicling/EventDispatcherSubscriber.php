<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Chronicling;

use Generator;
use Illuminate\Support\Arr;
use Plexikon\Chronicle\Reporter\ReportEvent;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Chronicling\EventChronicler;
use Plexikon\Chronicle\Support\Contract\Chronicling\TransactionalChronicler;
use Plexikon\Chronicle\Support\Contract\Tracker\EventContext;
use Plexikon\Chronicle\Support\Contract\Tracker\EventSubscriber;

final class EventDispatcherSubscriber implements EventSubscriber
{
    private array $recordedStreams = [];
    private ReportEvent $reporter;

    public function __construct(ReportEvent $reporter)
    {
        $this->reporter = $reporter;
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
                $streamEvents = $context->stream()->events();

                if (!$this->inTransaction($chronicler)) {
                    if (!$context->hasStreamNotFound() && !$context->hasRaceCondition()) {
                        foreach ($streamEvents as $streamEvent) {
                            $this->reporter->publish($streamEvent);
                        }
                    }
                } else {
                    $this->recordEvents($streamEvents);
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
                        foreach ($streamEvents as $streamEvent) {
                            $this->reporter->publish($streamEvent);
                        }
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

                foreach ($recordedStreams as $recordedStream) {
                    $this->reporter->publish($recordedStream);
                }
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
