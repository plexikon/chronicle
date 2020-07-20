<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Messaging\Decorator;

use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageDecorator;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;

final class EventTypeMessageDecorator implements MessageDecorator
{
    private MessageAlias $messageAlias;

    public function __construct(MessageAlias $messageAlias)
    {
        $this->messageAlias = $messageAlias;
    }

    public function decorate(Message $message): Message
    {
        $eventType = $message->header(MessageHeader::EVENT_TYPE);

        if (null === $eventType) {
            $message = $message->withHeader(
                MessageHeader::EVENT_TYPE,
                $this->messageAlias->instanceToType($message->event())
            );
        }

        return $message;
    }
}
