<?php

namespace Plexikon\Chronicle\Support\Contract\Reporter;

use Plexikon\Chronicle\Messaging\Message;

interface Router
{
    /**
     * @param Message $message
     * @return array<null|callable>
     */
    public function route(Message $message): array;
}
