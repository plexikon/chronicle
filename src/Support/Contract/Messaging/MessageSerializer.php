<?php

namespace Plexikon\Chronicle\Support\Contract\Messaging;

use Generator;
use Plexikon\Chronicle\Messaging\Message;

interface MessageSerializer
{
    /**
     * @param Message $message
     * @return array
     */
    public function serializeMessage(Message $message): array;

    /**
     * @param array $payload
     * @return Generator
     */
    public function unserializePayload(array $payload): Generator;
}
