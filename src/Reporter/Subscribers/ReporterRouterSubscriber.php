<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Reporter\Subscribers;

use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageProducer;
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Support\Contract\Reporter\Router;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageSubscriber;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageTracker;

final class ReporterRouterSubscriber implements MessageSubscriber
{
    private Router $router;
    private MessageProducer $messageProducer;

    public function __construct(Router $router, MessageProducer $messageProducer)
    {
        $this->router = $router;
        $this->messageProducer = $messageProducer;
    }

    public function attachToTracker(MessageTracker $tracker): void
    {
        $tracker->listen(Reporter::DISPATCH_EVENT, function (MessageContext $context): void {
            /** @var Message $message */
            $message = $context->getMessage();

            $this->messageProducer->mustBeHandledSync($message)
                ? $this->handleSyncMessage($context)
                : $this->handleAsyncMessage($context);
        }, 1000);
    }

    private function handleSyncMessage(MessageContext $context): void
    {
        /** @var Message $message */
        $message = $context->getMessage();

        $context->withMessageHandlers($this->router->route($message));
    }

    private function handleAsyncMessage(MessageContext $context): void
    {
        /** @var Message $message */
        $message = $context->getMessage();

        $asyncMessage = $this->messageProducer->produce($message);

        $context->withMessage($asyncMessage);
    }
}
