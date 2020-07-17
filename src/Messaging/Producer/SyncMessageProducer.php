<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Messaging\Producer;

use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageProducer;

final class SyncMessageProducer implements MessageProducer
{
    public function isMarkedAsync(Message $message): bool
    {
        return false;
    }

    public function mustBeHandledSync(Message $message): bool
    {
        return true;
    }

    public function produce(Message $message): Message
    {
        return $message;
    }
}
