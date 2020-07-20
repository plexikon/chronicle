<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Reporter\Subscribers;

use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageSubscriber;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageTracker;
use React\Promise\Deferred;
use Throwable;

final class TrackingQuerySubscriber implements MessageSubscriber
{
    public function attachToTracker(MessageTracker $tracker): void
    {
        $tracker->listen(Reporter::DISPATCH_EVENT, function (MessageContext $context): void {
            $message = $context->getMessage();
            $event = $message->isMessaging() ? $message->eventWithHeaders() : $message->event();

            $messageHandler = $context->messageHandlers()->current();

            if ($messageHandler) {
                $deferred = new Deferred();

                try {
                    $messageHandler($event, $deferred);
                } catch (Throwable $exception) {
                    $deferred->reject($exception);
                } finally {
                    $context->withPromise($deferred->promise());
                    $context->setMessageHandled(true);
                }
            }
        });
    }
}
