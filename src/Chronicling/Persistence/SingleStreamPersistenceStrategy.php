<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Chronicling\Persistence;

use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Stream\StreamName;
use Plexikon\Chronicle\Support\Contract\Chronicling\PersistenceStrategy;
use Plexikon\Chronicle\Support\Contract\Messaging\EventSerializer;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Support\Json;

abstract class SingleStreamPersistenceStrategy implements PersistenceStrategy
{
    private EventSerializer $eventSerializer;

    public function __construct(EventSerializer $eventSerializer)
    {
        $this->eventSerializer = $eventSerializer;
    }

    public function tableName(StreamName $streamName): string
    {
        return '_' . sha1($streamName->toString());
    }

    public function serializeMessage(Message $message): array
    {
        $data = $this->eventSerializer->serializeMessage($message);

        return [
            'event_id' => $data['headers'][MessageHeader::EVENT_ID],
            'event_type' => $data['headers'][MessageHeader::EVENT_TYPE],
            'payload' => Json::encode($data['payload']),
            'headers' => Json::encode($data['headers']),
            'created_at' => $data['headers'][MessageHeader::TIME_OF_RECORDING],
        ];
    }
}
