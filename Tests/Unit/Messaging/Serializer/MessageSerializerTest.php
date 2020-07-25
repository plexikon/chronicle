<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Messaging\Serializer;

use Generator;
use Plexikon\Chronicle\Exception\InvalidArgumentException;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Messaging\Serializer\MessageSerializer;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Support\Contract\Messaging\PayloadSerializer;
use Plexikon\Chronicle\Tests\Double\SomeCommand;
use Plexikon\Chronicle\Tests\Double\SomeEvent;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use stdClass;

final class MessageSerializerTest extends TestCase
{
    /**
     * @test
     */
    public function it_serialize_message(): void
    {
        $alias = $this->prophesize(MessageAlias::class);

        $eventPayload = ['foo' => 'bar'];
        $eventHeaders = ['baz' => 'foo_bar'];
        $event = SomeEvent::fromPayload($eventPayload);

        $payloadSerializer = $this->prophesize(PayloadSerializer::class);
        $payloadSerializer->serializePayload($event)->willReturn($eventPayload)->shouldBeCalled();

        $message = new Message($event, $eventHeaders);

        $serializer = new MessageSerializer($alias->reveal(), $payloadSerializer->reveal());

        $eventArray = $serializer->serializeMessage($message);

        $this->assertEquals($eventHeaders, $eventArray['headers']);
        $this->assertEquals($eventPayload, $eventArray['payload']);
    }

    /**
     * @test
     */
    public function it_raise_exception_if_event_not_instance_of_serializable_payload(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $alias = $this->prophesize(MessageAlias::class);
        $payloadSerializer = $this->prophesize(PayloadSerializer::class);
        $payloadSerializer->serializePayload()->shouldNotBeCalled();

        $message = new Message(new stdClass());

        $serializer = new MessageSerializer($alias->reveal(), $payloadSerializer->reveal());

        $serializer->serializeMessage($message);
    }

    /**
     * @test
     */
    public function it_unserialize_payload(): void
    {
        $eventClass = SomeCommand::class;

        $eventPayload = ['foo' => 'bar'];
        $eventHeaders = [MessageHeader::EVENT_TYPE => 'some.command'];

        $payload = ['payload' => $eventPayload, 'headers' => $eventHeaders,];

        $message = new Message(
            $eventClass::fromPayload($eventPayload), $eventHeaders
        );

        $alias = $this->prophesize(MessageAlias::class);
        $alias->typeToClass('some.command')->willReturn($eventClass)->shouldBeCalled();

        $payloadSerializer = $this->prophesize(PayloadSerializer::class);
        $payloadSerializer
            ->unserializePayload($eventClass, $payload['payload'])
            ->willReturn($message)
            ->shouldBeCalled();

        $serializer = new MessageSerializer($alias->reveal(), $payloadSerializer->reveal());

        $message = $serializer->unserializePayload($payload)->current();

        $this->assertInstanceOf(Message::class, $message);
    }

    /**
     * @test
     * @dataProvider provideInvalidPayload
     * @param array $invalidPayload
     */
    public function it_raise_exception_if_missing_payload_keys(array $invalidPayload): void
    {
        $this->expectException(InvalidArgumentException::class);

        $alias = $this->prophesize(MessageAlias::class);
        $alias->typeToClass()->shouldNotBeCalled();

        $payloadSerializer = $this->prophesize(PayloadSerializer::class);
        $payloadSerializer->unserializePayload()->shouldNotBeCalled();

        $serializer = new MessageSerializer($alias->reveal(), $payloadSerializer->reveal());

        $serializer->unserializePayload($invalidPayload)->current();
    }

    public function provideInvalidPayload(): Generator
    {
        yield [['foo' => [], 'bar' => []]];
        yield ['payload' => []];
        yield ['headers' => []];
    }
}
