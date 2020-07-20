<?php

namespace Plexikon\Chronicle\Support\Contract\Chronicling;

use Plexikon\Chronicle\Messaging\Message;

interface EventDispatcher
{
    /**
     * @param Message ...$messages
     */
    public function dispatch(Message ...$messages): void;
}
