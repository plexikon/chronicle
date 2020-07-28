<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Messaging\Decorator;

use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Clock;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageDecorator;

final class DefaultHeadersDecorator implements MessageDecorator
{
    private Clock $clock;
    private MessageAlias $messageAlias;

    public function __construct(Clock $clock, MessageAlias $messageAlias)
    {
        $this->clock = $clock;
        $this->messageAlias = $messageAlias;
    }

    public function decorate(Message $message): Message
    {
        $message = (new EventTypeMessageDecorator($this->messageAlias))->decorate($message);

        $message = (new TimeOfRecordingMessageDecorator($this->clock))->decorate($message);

        return $message;
    }
}
