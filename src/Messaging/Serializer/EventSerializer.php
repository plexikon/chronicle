<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Messaging\Serializer;

use DateTimeImmutable;
use Generator;
use Plexikon\Chronicle\Clock\PointInTime;
use Plexikon\Chronicle\Exception\InvalidArgumentException;
use Plexikon\Chronicle\Exception\RuntimeException;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateId;
use Plexikon\Chronicle\Support\Contract\Messaging\EventSerializer as BaseEventSerializer;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Support\Contract\Messaging\PayloadSerializer as BasePayloadSerializer;

final class EventSerializer implements BaseEventSerializer
{
    private MessageAlias $messageAlias;
    private BasePayloadSerializer $payloadSerializer;

    public function __construct(MessageAlias $messageAlias, BasePayloadSerializer $payloadSerializer)
    {
        $this->messageAlias = $messageAlias;
        $this->payloadSerializer = $payloadSerializer;
    }

    public function serializeMessage(Message $message): array
    {
        $event = $message->event();

        $headers = $this->serializeHeaders($message->headers());

        $payload = $this->payloadSerializer->serializePayload($event);

        return ['headers' => $headers, 'payload' => $payload];
    }

    public function unserializePayload(array $payload): Generator
    {
        $headers = $payload['headers'];

        if (!isset($headers[MessageHeader::INTERNAL_POSITION])) {
            $headers[MessageHeader::INTERNAL_POSITION] = $payload['no'];
        }

        $headers = $this->unserializeHeaders($headers);

        $event = $this->payloadSerializer->unserializePayload(
            $this->messageAlias->typeToClass($headers[MessageHeader::EVENT_TYPE]),
            ['headers' => $headers, 'payload' => $payload['payload']]
        );

        yield new Message($event, $headers);
    }

    private function serializeHeaders(array $headers): array
    {
        $headers = $this->serializeAggregateId($headers);

        $headers = $this->serializeTimeOfRecording($headers);

        return $headers;
    }

    private function unserializeHeaders(array $headers): array
    {
        $headers = $this->unserializeAggregateId($headers);

        $headers = $this->unserializeTimeOfRecording($headers);

        return $headers;
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

    private function serializeTimeOfRecording(array $headers): array
    {
        $timeOfRecording = $headers[MessageHeader::TIME_OF_RECORDING];

        $invalidTimeException = null;

        try {
            if (is_string($timeOfRecording)) {
                $timeOfRecording = PointInTime::fromString($timeOfRecording);
            }
        } catch (InvalidArgumentException $exception) {
            $invalidTimeException = $exception;
        }

        if ($timeOfRecording instanceof PointInTime) {
            $timeOfRecording = $timeOfRecording->toString();
        } elseif ($timeOfRecording instanceof DateTimeImmutable) {
            $timeOfRecording = PointInTime::fromDateTime($timeOfRecording)->toString();
        } else {
            throw new RuntimeException(
                "Unable to serialize time of recording header", 0, $invalidTimeException
            );
        }

        $headers[MessageHeader::TIME_OF_RECORDING] = $timeOfRecording;

        return $headers;
    }

    private function unserializeTimeOfRecording(array $headers): array
    {
        $timeOfRecording = $headers[MessageHeader::TIME_OF_RECORDING];

        $headers[MessageHeader::TIME_OF_RECORDING] = PointInTime::fromString($timeOfRecording);

        return $headers;
    }
}
