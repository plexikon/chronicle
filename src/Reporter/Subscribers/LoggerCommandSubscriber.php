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

            $this->logger->debug('Command on dispatch', [
                'context' => [
                    'message_name' => $this->determineMessageName($message),
                    'has_message_handlers' => !empty($context->messageHandlers()),
                    'exception' => $context->hasException() ? $context->getException() : null,
                    'message' => $this->messageSerializer->serializeMessage($message)
                ]
            ]);
        }, 1); //add debug before message factory subscriber

        $tracker->listen(Reporter::FINALIZE_EVENT, function (MessageContext $context): void {
            $this->logger->debug('Command on finalize', [
                'context' => [
                    'message_name' => $this->determineMessageName($context->getMessage()),
                    'message_handled' => $context->isMessageHandled(),
                    'exception' => $context->hasException() ? $context->getException() : null,
                ]
            ]);
        }, 1);
    }

    private function determineMessageName(Message $message): string
    {
        $eventType = $message->header(MessageHeader::EVENT_TYPE);

        return $eventType ?? get_class($message->event());
    }
}
