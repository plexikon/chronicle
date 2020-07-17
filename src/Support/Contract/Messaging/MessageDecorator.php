<?php

namespace Plexikon\Chronicle\Support\Contract\Messaging;

use Plexikon\Chronicle\Messaging\Message;

interface MessageDecorator
{
    /**
     * @param Message $message
     * @return Message
     */
    public function decorate(Message $message): Message;
}
