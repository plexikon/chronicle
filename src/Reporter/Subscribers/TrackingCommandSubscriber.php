<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Reporter\Subscribers;

use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageSubscriber;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageTracker;
use Plexikon\Chronicle\Support\Contract\Tracker\Tracker;

final class TrackingCommandSubscriber implements MessageSubscriber
{
    public function attachToTracker(MessageTracker $tracker): void
    {
        $this->subscribeToDispatchEvent($tracker);

        $this->subscribeToFinalizeEvent($tracker);
    }

    private function subscribeToDispatchEvent(MessageTracker $tracker): void
    {
        $tracker->listen(Reporter::DISPATCH_EVENT, function (MessageContext $context): void {
            /** @var Message $message */
            $message = $context->getMessage();

            $event = $message->isMessaging() ? $message->eventWithHeaders() : $message->event();

            if ($messageHandler = $context->messageHandlers()->current()) {
                $messageHandler($event);
            }

            $context->markMessageHandled(true);
        }, Reporter::PRIORITY_INVOKE_HANDLER);
    }

    private function subscribeToFinalizeEvent(Tracker $tracker): void
    {
        $tracker->listen(Reporter::FINALIZE_EVENT, function (MessageContext $context): void {
            if ($exception = $context->getException()) {
                throw $exception;
            }
        });
    }
}
