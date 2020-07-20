<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Messaging\Decorator;

use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Clock;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageDecorator;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;

final class TimeOfRecordingMessageDecorator implements MessageDecorator
{
    private Clock $clock;

    public function __construct(Clock $clock)
    {
        $this->clock = $clock;
    }

    public function decorate(Message $message): Message
    {
        $recordedAt = $message->header(MessageHeader::TIME_OF_RECORDING);

        if (null === $recordedAt) {
            $message = $message->withHeader(
                MessageHeader::TIME_OF_RECORDING, $this->clock->pointInTime()
            );
        }

        return $message;
    }
}
