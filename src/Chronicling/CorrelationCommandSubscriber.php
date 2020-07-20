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
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageSubscriber;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageTracker;

final class CorrelationCommandSubscriber implements MessageSubscriber
{
    private array $eventListeners = [];
    private array $messageListeners = [];

    /**
     * @var Chronicler|EventChronicler
     */
    private Chronicler $chronicler;

    public function __construct(Chronicler $chronicler)
    {
        $this->chronicler = $chronicler;
    }

    public function attachToTracker(MessageTracker $tracker): void
    {
        $this->messageListeners[] = $tracker->listen(Reporter::DISPATCH_EVENT,
            function (MessageContext $context): void {
                $message = $context->getMessage();

                if ($this->supportCorrelation($message)) {
                    $this->subscribeToChronicler($message);
                }
            }, 1000);

        $this->messageListeners[] = $tracker->listen(Reporter::FINALIZE_EVENT,
            function () use ($tracker) {
                $this->chronicler->unsubscribe(...$this->eventListeners);
                $this->eventListeners = [];
                $this->messageListeners = []; // checkMe
            }, 1000);
    }

    private function subscribeToChronicler(Message $message): void
    {
        $messageDecorator = $this->correlationMessageDecorator($message);

        $this->eventListeners[] = $this->chronicler->subscribe(EventChronicler::FIRST_COMMIT_EVENT,
            function (EventContext $context) use ($messageDecorator): void {
                $context->decorateMessage($messageDecorator);
            }, 1000);

        $this->eventListeners[] = $this->chronicler->subscribe(EventChronicler::PERSIST_STREAM_EVENT,
            function (EventContext $context) use ($messageDecorator): void {
                $context->decorateMessage($messageDecorator);
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
        if (!$message->isMessaging() || $message->event()->messageType() !== Messaging::COMMAND) {
            return false;
        }

        return $this->chronicler instanceof EventChronicler;
    }
}
