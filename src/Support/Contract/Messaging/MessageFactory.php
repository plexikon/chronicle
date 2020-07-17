<?php

namespace Plexikon\Chronicle\Support\Contract\Messaging;

use Plexikon\Chronicle\Messaging\Message;

interface MessageFactory
{
    /**
     * @param object|array $message
     * @return Message
     */
    public function createMessageFrom($message): Message;
}
