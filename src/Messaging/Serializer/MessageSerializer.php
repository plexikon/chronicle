<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Messaging\Serializer;

use Generator;
use Plexikon\Chronicle\Exception\Assertion;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageSerializer as BaseMessageSerializer;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Support\Contract\Messaging\PayloadSerializer;
use Plexikon\Chronicle\Support\Contract\Messaging\SerializablePayload;

final class MessageSerializer implements BaseMessageSerializer
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

        Assertion::isInstanceOf($event, SerializablePayload::class);

        $payload = $this->payloadSerializer->serializePayload($event);

        return ['headers' => $message->headers(), 'payload' => $payload];
    }

    public function unserializePayload(array $payload): Generator
    {
        Assertion::keyIsset($payload, 'headers', 'Headers key missing');
        Assertion::keyIsset($payload, 'payload', 'Payload key missing');

        $headers = $payload['headers'];

        $className = $this->messageAlias->typeToClass($headers[MessageHeader::EVENT_TYPE]);

        $event = $this->payloadSerializer->unserializePayload($className, $payload['payload']);

        yield new Message($event, $headers);
    }
}
