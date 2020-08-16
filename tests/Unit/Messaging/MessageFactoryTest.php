<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Messaging;

use Assert\AssertionFailedException;
use Generator;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Messaging\MessageFactory;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageSerializer;
use Plexikon\Chronicle\Tests\Double\SomeCommand;
use Plexikon\Chronicle\Tests\Unit\TestCase;

final class MessageFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_convert_array_to_valid_message_instance(): void
    {
        $payload = [
            'headers' => [],
            'payload' => [
                'foo' => 'bar'
            ]
        ];

        $serializer = $this->prophesize(MessageSerializer::class);
        $serializer->unserializePayload($payload)->willYield(
            [SomeCommand::fromPayload($payload['payload'])]
        )->shouldBeCalled();

        $factory = new MessageFactory($serializer->reveal());

        $messageInstance = $factory->createMessageFrom($payload);

        $this->assertEquals($payload['payload'], $messageInstance->event()->toPayload());
    }

    /**
     * @test
     */
    public function it_wrap_event_into_message_instance(): void
    {
        $serializer = $this->prophesize(MessageSerializer::class);
        $serializer->unserializePayload()->shouldNotBeCalled();

        $factory = new MessageFactory($serializer->reveal());

        $message = new Message(SomeCommand::fromPayload(['foo' => 'bar']));

        $this->assertEquals($message, $factory->createMessageFrom($message));
    }

    /**
     * @test
     * @dataProvider provideInvalidEvent
     * @param mixed $invalidEvent
     */
    public function it_raise_exception_when_message_is_invalid_type($invalidEvent): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Message can be an array, an object or an instance of ' . Message::class);

        $serializer = $this->prophesize(MessageSerializer::class);
        $serializer->unserializePayload()->shouldNotBeCalled();

        $factory = new MessageFactory($serializer->reveal());

        $factory->createMessageFrom($invalidEvent);
    }

    public function provideInvalidEvent(): Generator
    {
        yield [null];
        yield [1234];
        yield [''];
        yield ['foo'];
    }
}
