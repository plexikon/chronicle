<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Messaging\Serializer;

use Plexikon\Chronicle\Chronicling\Aggregate\AggregateChanged;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateId;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Support\Contract\Messaging\PayloadSerializer as BasePayloadSerializer;
use Plexikon\Chronicle\Support\Contract\Messaging\SerializablePayload;

final class PayloadSerializer implements BasePayloadSerializer
{
    public function serializePayload(object $event): array
    {
        return $event->toPayload();
    }

    public function unserializePayload(string $className, array $payload): object
    {
        if (is_subclass_of($className, AggregateChanged::class)) {
            $aggregateRootId = $payload['headers'][MessageHeader::AGGREGATE_ID];

            if ($aggregateRootId instanceof AggregateId) {
                $aggregateRootId = $aggregateRootId->toString();
            }

            /** @var AggregateChanged $className */
            return $className::occur($aggregateRootId, $payload['payload']);
        }

        /* @var SerializablePayload $className */
        return $className::fromPayload($payload);
    }
}
