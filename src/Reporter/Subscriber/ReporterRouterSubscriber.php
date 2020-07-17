<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Reporter\Subscriber;

use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageProducer;
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Support\Contract\Reporter\Router;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageSubscriber;
use Plexikon\Chronicle\Support\Contract\Tracker\Tracker;

final class ReporterRouterSubscriber implements MessageSubscriber
{
    private Router $router;
    private ?MessageProducer $messageProducer;

    public function __construct(Router $router, ?MessageProducer $messageProducer)
    {
        $this->router = $router;
        $this->messageProducer = $messageProducer;
    }

    public function attachToTracker(Tracker $tracker): void
    {
        $tracker->listen(Reporter::DISPATCH_EVENT, function (MessageContext $context): void {
            $this->messageMustBeHandleSync($context->getMessage())
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

    private function messageMustBeHandleSync(Message $message): bool
    {
      return !$this->messageProducer || $this->messageProducer->mustBeHandledSync($message);
    }
}
