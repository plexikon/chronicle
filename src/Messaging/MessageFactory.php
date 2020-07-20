<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Messaging;

use Plexikon\Chronicle\Exception\Assertion;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageFactory as BaseMessageFactory;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageSerializer;

final class MessageFactory implements BaseMessageFactory
{
    private MessageSerializer $messageSerializer;

    public function __construct(MessageSerializer $messageSerializer)
    {
        $this->messageSerializer = $messageSerializer;
    }

    public function createMessageFrom($message): Message
    {
        if (is_array($message)) {
            $message = $this->messageSerializer->unserializePayload($message)->current();
        }

        Assertion::isObject($message, 'Message can be an array, an object and an instance of ' . Message::class);

        if ($message instanceof Message) {
            return $message;
        }

        return new Message($message);
    }
}
