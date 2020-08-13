<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Messaging\Serializer;

use Plexikon\Chronicle\Chronicling\Aggregate\AggregateChanged;
use Plexikon\Chronicle\Exception\Assert;
use Plexikon\Chronicle\Exception\Assertion;
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

    /**
     * @param string $className
     * @param array $payload
     * @return object
     */
    public function unserializePayload(string $className, array $payload): object
    {
        // From message serializer, payload is a straight event payload
        // From event serializer, payload hold headers

        Assertion::classExists($className, 'Invalid event class name');

        if (is_subclass_of($className, AggregateChanged::class)) {
            $aggregateRootId = $payload['headers'][MessageHeader::AGGREGATE_ID] ?? null;

            if ($aggregateRootId instanceof AggregateId) {
                $aggregateRootId = $aggregateRootId->toString();
            }

            Assert::that($aggregateRootId, 'Invalid aggregate root id')
                ->string()
                ->notBlank();

            /** @var AggregateChanged $className */
            return $className::occur($aggregateRootId, $payload['payload']);
        }

        /* @var SerializablePayload $className */
        return $className::fromPayload($payload);
    }
}
