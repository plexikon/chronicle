<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Messaging\Decorator;

use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageDecorator;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;

final class BusTypeMessageDecorator implements MessageDecorator
{
    private string $reporterName;

    public function __construct(string $reporterType)
    {
        $this->reporterName = $reporterType;
    }

    public function decorate(Message $message): Message
    {
        if (null !== $message->header(MessageHeader::MESSAGE_BUS_TYPE)) {
            return $message;
        }

        return $message->withHeader(MessageHeader::MESSAGE_BUS_TYPE, $this->reporterName);
    }
}
