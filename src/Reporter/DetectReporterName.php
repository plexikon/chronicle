<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Reporter;

use Plexikon\Chronicle\Exception\RuntimeException;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Support\Contract\Messaging\Messaging;
use function get_class;
use function is_string;

trait DetectReporterName
{
    /**
     * @param Message $message
     * @return string
     */
    protected function detectBusTypeFromMessage(Message $message): string
    {
        $namedBus = $message->header(MessageHeader::MESSAGE_BUS_NAME);

        return is_string($namedBus) ? $namedBus : $this->detectBusTypeFromEvent($message->event());
    }

    /**
     * @param object $event
     * @return string
     * @throws RuntimeException
     */
    protected function detectBusTypeFromEvent(object $event): string
    {
        if ($event instanceof Messaging) {
            switch ($event->messageType()) {
                case Messaging::COMMAND:
                    return ReportCommand::class;

                case Messaging::EVENT:
                    return ReportEvent::class;

                case Messaging::QUERY:
                    return ReportQuery::class;
            }
        }

        throw new RuntimeException("Can not detect bus name from message event " . (get_class($event)));
    }
}
