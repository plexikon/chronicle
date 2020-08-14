<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Messaging\Serializer;

use Generator;
use Plexikon\Chronicle\Chronicling\Aggregate\AggregateChanged;
use Plexikon\Chronicle\Messaging\Serializer\PayloadSerializer;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Support\Contract\Messaging\SerializablePayload;
use Plexikon\Chronicle\Tests\Double\SomeAggregateChanged;
use Plexikon\Chronicle\Tests\Double\SomeAggregateId;
use Plexikon\Chronicle\Tests\Double\SomeCommand;
use Plexikon\Chronicle\Tests\Double\SomeEvent;
use Plexikon\Chronicle\Tests\Unit\TestCase;

final class PayloadSerializerTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideSerializablePayload
     * @param SerializablePayload $event
     */
    public function it_serialize_payload(SerializablePayload $event): void
    {
        $serializer = new PayloadSerializer();

        $this->assertEquals(['foo' => 'bar'], $serializer->serializePayload($event));
    }

    /**
     * @test
     */
    public function it_unserialize_payload(): void
    {
        $serializer = new PayloadSerializer();

        $payload = ['foo' => 'bar'];

        /** @var SerializablePayload $event */
        $event = $serializer->unserializePayload(SomeEvent::class, $payload);

        $this->assertInstanceOf(SomeEvent::class, $event);

        $this->assertEquals($payload, $event->toPayload());
    }

    /**
     * @test
     * @dataProvider provideAggregateId
     * @param object|string $aggregateId
     */
    public function it_unserialize_payload_and_return_aggregate_changed($aggregateId): void
    {
        $serializer = new PayloadSerializer();

        $payload = [
            'headers' => [
                MessageHeader::AGGREGATE_ID => $aggregateId
            ],
            'payload' => [
                'foo' => 'bar'
            ]
        ];

        /** @var SerializablePayload $event */
        $event = $serializer->unserializePayload(SomeAggregateChanged::class, $payload);

        $this->assertInstanceOf(AggregateChanged::class, $event);

        $this->assertEmpty($event->headers());

        $this->assertEquals(['foo' => 'bar'], $event->toPayload());
    }

    public function provideAggregateId(): Generator
    {
        $aggregateId = SomeAggregateId::create();

        yield [$aggregateId];
        yield [$aggregateId->toString()];
    }

    public function provideSerializablePayload(): Generator
    {
        $payload = ['foo' => 'bar'];

        yield [SomeCommand::fromPayload($payload)];
        yield [SomeEvent::fromPayload($payload)];

        yield [new class($payload) implements SerializablePayload {

            private array $payload;

            public function __construct(array $payload)
            {
                $this->payload = $payload;
            }

            public function toPayload(): array
            {
                return $this->payload;
            }

            public static function fromPayload(array $payload): SerializablePayload
            {
                return new self($payload);
            }
        }];
    }
}
