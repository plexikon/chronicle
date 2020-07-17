<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Messaging\Serializer;

use Generator;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateId;
use Plexikon\Chronicle\Support\Contract\Messaging\EventSerializer as BaseEventSerializer;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;

final class EventSerializer implements BaseEventSerializer
{
    private MessageAlias $messageAlias;
    private PayloadSerializer $payloadSerializer;

    public function __construct(MessageAlias $messageAlias, PayloadSerializer $payloadSerializer)
    {
        $this->messageAlias = $messageAlias;
        $this->payloadSerializer = $payloadSerializer;
    }

    public function serializeMessage(Message $message): array
    {
        $event = $message->event();

        $payload = $this->payloadSerializer->serializePayload($event);

        $headers = $this->serializeAggregateId($message->headers());

        return ['headers' => $headers, 'payload' => $payload];
    }

    public function unserializePayload(array $payload): Generator
    {
        $headers = $payload['headers'];

        if (!isset($headers[MessageHeader::INTERNAL_POSITION])) {
            $headers[MessageHeader::INTERNAL_POSITION] = $payload['no'];
        }

        $headers = $this->unserializeAggregateId($headers);

        $event = $this->payloadSerializer->unserializePayload(
            $this->messageAlias->typeToClass($headers[MessageHeader::EVENT_TYPE]),
            ['headers' => $headers, 'payload' => $payload['payload']]
        );

        yield new Message($event, $headers);
    }

    private function serializeAggregateId(array $headers): array
    {
        $aggregateId = $headers[MessageHeader::AGGREGATE_ID];

        if ($aggregateId instanceof AggregateId) {
            $headers[MessageHeader::AGGREGATE_ID] = $aggregateId->toString();
        }

        return $headers;
    }

    private function unserializeAggregateId(array $headers): array
    {
        $aggregateId = $headers[MessageHeader::AGGREGATE_ID];

        if (is_string($aggregateId)) {
            $aggregateIdType = $headers[MessageHeader::AGGREGATE_ID_TYPE];

            /** @var AggregateId $aggregateIdTypeClassName */
            $aggregateIdTypeClassName = $this->messageAlias->typeToClass($aggregateIdType);

            $headers[MessageHeader::AGGREGATE_ID] = $aggregateIdTypeClassName::fromString($aggregateId);
        }

        return $headers;
    }
}
