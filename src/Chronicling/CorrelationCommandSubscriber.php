<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Chronicling;

use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Chronicling\EventChronicler;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageDecorator;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Support\Contract\Messaging\Messaging;
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Support\Contract\Tracker\EventContext;
use Plexikon\Chronicle\Support\Contract\Tracker\EventSubscriber;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageSubscriber;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageTracker;

final class CorrelationCommandSubscriber implements MessageSubscriber, EventSubscriber
{
    private ?Message $command = null;
    private array $eventListeners = [];
    private array $messageListeners = [];

    public function attachToTracker(MessageTracker $tracker): void
    {
        $this->messageListeners[] = $tracker->listen(Reporter::DISPATCH_EVENT,
            function (MessageContext $context): void {
                $message = $context->getMessage();

                if ($this->supportCorrelation($message)) {
                    $this->command = $message;
                }
            }, 1000);

        $this->messageListeners[] = $tracker->listen(Reporter::FINALIZE_EVENT,
            function () {
                $this->command = null;
            }, 1000);
    }

    public function attachToChronicler(Chronicler $chronicler): void
    {
        if (!$chronicler instanceof EventChronicler) {
            return;
        }

        $this->eventListeners[] = $chronicler->subscribe(EventChronicler::FIRST_COMMIT_EVENT,
            function (EventContext $context): void {
                if ($this->command) {
                    $messageDecorator = $this->correlationMessageDecorator($this->command);

                    $context->decorateStreamEvents($messageDecorator);
                }
            }, 1000);

        $this->eventListeners[] = $chronicler->subscribe(EventChronicler::PERSIST_STREAM_EVENT,
            function (EventContext $context): void {
                if ($this->command) {
                    $messageDecorator = $this->correlationMessageDecorator($this->command);

                    $context->decorateStreamEvents($messageDecorator);
                }
            }, 1000);
    }

    private function correlationMessageDecorator(Message $message): MessageDecorator
    {
        $eventId = (string)$message->header(MessageHeader::EVENT_ID);
        $eventType = $message->header(MessageHeader::EVENT_TYPE);

        return new class($eventId, $eventType) implements MessageDecorator {
            private string $eventId;
            private string $eventType;

            public function __construct(string $eventId, string $eventType)
            {
                $this->eventId = $eventId;
                $this->eventType = $eventType;
            }

            public function decorate(Message $message): Message
            {
                if (null !== $message->header(MessageHeader::EVENT_CAUSATION_ID)
                    && null !== $message->header(MessageHeader::EVENT_CAUSATION_TYPE)) {
                    return $message;
                }

                return $message->withHeaders([
                    MessageHeader::EVENT_CAUSATION_ID => $this->eventId,
                    MessageHeader::EVENT_CAUSATION_TYPE => $this->eventType
                ]);
            }
        };
    }

    private function supportCorrelation(Message $message): bool
    {
        return ($message->isMessaging() && $message->event()->messageType() === Messaging::COMMAND);
    }
}
