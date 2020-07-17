<?php

namespace Plexikon\Chronicle\Support\Contract\Reporter;

use Plexikon\Chronicle\Messaging\Message;

interface Router
{
    /**
     * @param Message $message
     * @return array
     */
    public function route(Message $message): array;
}
