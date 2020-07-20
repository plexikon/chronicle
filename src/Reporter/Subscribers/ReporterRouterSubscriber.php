<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Reporter\Subscribers;

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
            $this->messageProducer->mustBeHandledSync($context->getMessage())
                ? $this->handleSyncMessage($context)
                : $this->handleAsyncMessage($context);
        }, 1000);
    }

    private function handleSyncMessage(MessageContext $context): void
    {
        $message = $context->getMessage();

        $context->withMessageHandlers($this->router->route($message));
    }

    private function handleAsyncMessage(MessageContext $context): void
    {
        $message = $this->messageProducer->produce(
            $context->getMessage()
        );

        $context->withMessage($message);
    }
}
