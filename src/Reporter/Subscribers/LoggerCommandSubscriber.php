<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Reporter\Subscribers;

use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageSerializer;
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageSubscriber;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageTracker;
use Psr\Log\LoggerInterface;

final class LoggerCommandSubscriber implements MessageSubscriber
{
    private LoggerInterface $logger;
    private MessageSerializer $messageSerializer;

    public function __construct(LoggerInterface $logger, MessageSerializer $messageSerializer)
    {
        $this->logger = $logger;
        $this->messageSerializer = $messageSerializer;
    }

    public function attachToTracker(MessageTracker $tracker): void
    {
        $tracker->listen(Reporter::DISPATCH_EVENT, function (MessageContext $context): void {
            $message = $context->getMessage();

            if (!is_array($message)) {
                return;
            }

            $this->logger->debug('Command on dispatch', [
                'context' => [
                    'message_name' => $this->determineMessageName($message),
                    'exception' => $context->hasException() ? $context->getException() : null,
                    'message' => $message
                ]
            ]);
        }, 100001);

        $tracker->listen(Reporter::DISPATCH_EVENT, function (MessageContext $context): void {
            /** @var Message $message */
            $message = $context->getMessage();

            $this->logger->debug('Command on dispatch', [
                'context' => [
                    'message_name' => $this->determineMessageName($message),
                    'has_message_handlers' => !empty($context->messageHandlers()),
                    'exception' => $context->hasException() ? $context->getException() : null,
                    'message' => $this->messageSerializer->serializeMessage($message)
                ]
            ]);
        }, 1);

        $tracker->listen(Reporter::FINALIZE_EVENT, function (MessageContext $context): void {
            /** @var Message $message */
            $message = $context->getMessage();

            $this->logger->debug('Command on finalize', [
                'context' => [
                    'message_name' => $this->determineMessageName($message),
                    'message_handled' => $context->isMessageHandled(),
                    'exception' => $context->hasException() ? $context->getException() : null,
                ]
            ]);
        }, 1);
    }

    /**
     * @param array<string,mixed>|Message $message
     * @return string
     */
    private function determineMessageName($message): string
    {
        if ($message instanceof Message) {
            $eventType = $message->header(MessageHeader::EVENT_TYPE);

            return $eventType ?? get_class($message->event());
        }

        $eventType = $message[MessageHeader::EVENT_TYPE];

        return is_string($eventType) ? $eventType : 'invalid event type';
    }
}
