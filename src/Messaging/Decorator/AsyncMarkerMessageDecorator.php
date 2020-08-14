<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Messaging\Decorator;

use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageDecorator;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;

final class AsyncMarkerMessageDecorator implements MessageDecorator
{
    public function decorate(Message $message): Message
    {
        if (!$message->isMessaging()) {
            return $message;
        }

        $asyncMarker = $message->header(MessageHeader::MESSAGE_ASYNC_MARKED);

        if (null === $asyncMarker) {
            $message = $message->withHeader(MessageHeader::MESSAGE_ASYNC_MARKED, false);
        }

        return $message;
    }
}
