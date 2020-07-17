<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Messaging\Producer;

use Generator;
use Plexikon\Chronicle\Exception\RuntimeException;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Messaging\Producer\AsyncMessageProducer;
use Plexikon\Chronicle\Messaging\Producer\IlluminateProducer;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageProducer;
use Plexikon\Chronicle\Support\Contract\Messaging\Messaging;
use Plexikon\Chronicle\Tests\Double\SomeAsyncCommand;
use Plexikon\Chronicle\Tests\Double\SomeCommand;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use Prophecy\Argument;
use stdClass;

final class AsyncMessageProducerTest extends TestCase
{
    /**
     * @test
     */
    public function it_return_same_message_if_message_must_be_handle_sync(): void
    {
        $expectedMessage = new Message(new stdClass());

        $illuminateProducer = $this->prophesize(IlluminateProducer::class);
        $illuminateProducer->handle($expectedMessage)->shouldNotBeCalled();

        $producer = new AsyncMessageProducer($illuminateProducer->reveal(), 'foo_strategy');

        $message = $producer->produce($expectedMessage);

        $this->assertEquals($expectedMessage, $message);
    }

    /**
     * @test
     * @dataProvider provideAsyncProducer
     * @param string $strategy
     * @param Messaging $event
     */
    public function it_mark_async_message_header_if_message_is_produced_async(string $strategy, Messaging $event): void
    {
        $message = new Message($event);
        $message = $message->withHeader(MessageHeader::MESSAGE_ASYNC_MARKED, false);

        $illuminateProducer = $this->prophesize(IlluminateProducer::class);
        $illuminateProducer->handle(Argument::type(Message::class))->shouldBeCalled();

        $producer = new AsyncMessageProducer($illuminateProducer->reveal(), $strategy);

        $asyncMessage = $producer->produce($message);

        $this->assertTrue($asyncMessage->header(MessageHeader::MESSAGE_ASYNC_MARKED));
    }

    /**
     * @test
     * @dataProvider provideSyncProducer
     * @param string $strategy
     * @param object $event
     */
    public function it_handle_message_sync(string $strategy, object $event): void
    {
        $message = $event instanceof Message ? $event : new Message($event);

        $illuminateProducer = $this->prophesize(IlluminateProducer::class);
        $illuminateProducer->handle(Argument::type(Message::class))->shouldNotBeCalled();

        $producer = new AsyncMessageProducer($illuminateProducer->reveal(), $strategy);

        $producer->produce($message);
    }

    /**
     * @test
     */
    public function it_raise_exception_with_invalid_strategy(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Unable to determine producer with strategy foo_strategy");

        $message = new Message(SomeCommand::fromPayload(['bar']));
        $message = $message->withHeader(MessageHeader::MESSAGE_ASYNC_MARKED, false);

        $illuminateProducer = $this->prophesize(IlluminateProducer::class);
        $illuminateProducer->handle(Argument::type(Message::class))->shouldNotBeCalled();

        $producer = new AsyncMessageProducer($illuminateProducer->reveal(), 'foo_strategy');
        $producer->produce($message);
    }

    public function provideAsyncProducer(): Generator
    {
        yield [MessageProducer::ROUTE_ALL_ASYNC, SomeCommand::withData(['foo'])];

        yield [MessageProducer::ROUTE_PER_MESSAGE, SomeAsyncCommand::fromPayload(['bar'])];
    }

    public function provideSyncProducer(): Generator
    {
        // not a serializable payload
        yield ['foo_strategy', new stdClass()];

        // already produced async
        yield [MessageProducer::ROUTE_ALL_ASYNC, (new Message(SomeCommand::withData(['foo'])))->withHeader(
            MessageHeader::MESSAGE_ASYNC_MARKED, true
        )];

        // no async message contract
        yield [MessageProducer::ROUTE_PER_MESSAGE, SomeCommand::withData(['foo'])];

        // strategy none async
        yield [MessageProducer::ROUTE_NONE_ASYNC, SomeCommand::withData(['foo'])];
    }
}
