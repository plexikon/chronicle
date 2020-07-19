<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Messaging\Decorator;

use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageDecorator;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Ramsey\Uuid\Uuid;

final class EventIdMessageDecorator implements MessageDecorator
{
    public function decorate(Message $message): Message
    {
        if(!$message->isMessaging()){
            return $message;
        }

        $eventId = $message->header(MessageHeader::EVENT_ID);

        if (null === $eventId) {
            $message = $message->withHeader(MessageHeader::EVENT_ID, Uuid::uuid4());
        }

        return $message;
    }
}
