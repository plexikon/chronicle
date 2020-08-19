<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Reporter\Subscribers;

use Plexikon\Chronicle\Exception\UnauthorizedException;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Support\Contract\Reporter\AuthorizationService;
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageSubscriber;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageTracker;

final class RouteGuardSubscriber implements MessageSubscriber
{
    private AuthorizationService $authorizationService;
    private MessageAlias $messageAlias;

    public function __construct(AuthorizationService $authorizationService, MessageAlias $messageAlias)
    {
        $this->authorizationService = $authorizationService;
        $this->messageAlias = $messageAlias;
    }

    public function attachToTracker(MessageTracker $tracker): void
    {
        $tracker->listen(Reporter::DISPATCH_EVENT, function (MessageContext $context): void {
            /** @var Message $message */
            $message = $context->getMessage();

            $eventAlias = $this->messageAlias->typeToAlias(
                $message->header(MessageHeader::EVENT_TYPE)
            );

            if (!$this->authorizationService->isGranted($eventAlias, $message)) {
                $context->stopPropagation(true);

                throw new UnauthorizedException("Unauthorized for event $eventAlias");
            }
        }, Reporter::PRIORITY_ROUTE + 1000);
    }
}
