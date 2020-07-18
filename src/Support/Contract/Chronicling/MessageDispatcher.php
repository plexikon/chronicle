<?php

namespace Plexikon\Chronicle\Support\Contract\Chronicling;

use Plexikon\Chronicle\Messaging\Message;

interface MessageDispatcher
{
    /**
     * @param Message ...$messages
     */
    public function dispatch(Message ...$messages): void;
}
