<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Messaging\Decorator;

use Generator;
use Plexikon\Chronicle\Exception\RuntimeException;
use Plexikon\Chronicle\Messaging\Decorator\AggregateIdTypeEventDecorator;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateId;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Tests\Double\SomeAggregateId;
use Plexikon\Chronicle\Tests\Double\SomeCommand;
use Plexikon\Chronicle\Tests\Double\SomeEvent;
use Plexikon\Chronicle\Tests\Double\SomeQuery;
use Plexikon\Chronicle\Tests\Unit\TestCase;

final class AggregateIdTypeEventDecoratorTest extends TestCase
{
    /**
     * @test
     */
    public function it_decorate_message_with_aggregate_id_and_aggregate_type(): void
    {
        $aggregateId = SomeAggregateId::create();

        $message = new Message(SomeEvent::fromPayload([]), [
            MessageHeader::AGGREGATE_ID_TYPE => null,
            MessageHeader::AGGREGATE_ID => $aggregateId
        ]);

        $alias = $this->prophesize(MessageAlias::class);
        $alias->instanceToType($aggregateId)->willReturn('some.aggregate_id')->shouldBeCalled();

        $decorator = new AggregateIdTypeEventDecorator($alias->reveal());

        $decoratedMessage = $decorator->decorate($message);

        $this->assertEquals(
            'some.aggregate_id',
            $decoratedMessage->header(MessageHeader::AGGREGATE_ID_TYPE)
        );

        $this->assertNotEquals($message, $decoratedMessage);
    }

    /**
     * @test
     * @dataProvider provideUnsupportedMessage
     * @param Message $unsupportedMessage
     */
    public function it_does_not_decorate_unsupported_message(Message $unsupportedMessage): void
    {
        $this->assertNull($unsupportedMessage->header(MessageHeader::AGGREGATE_ID_TYPE));

        $alias = $this->prophesize(MessageAlias::class);
        $alias->instanceToType()->shouldNotBeCalled();

        $decorator = new AggregateIdTypeEventDecorator($alias->reveal());

        $decoratedMessage = $decorator->decorate($unsupportedMessage);

        $this->assertNull($decoratedMessage->header(MessageHeader::AGGREGATE_ID_TYPE));

        $this->assertEquals($unsupportedMessage, $decoratedMessage);
    }

    /**
     * @test
     */
    public function it_does_not_decorate_if_aggregate_type_header_already_present(): void
    {
        $message = new Message(SomeEvent::fromPayload([]), [
            MessageHeader::AGGREGATE_ID_TYPE => 'foo.bar',
        ]);

        $alias = $this->prophesize(MessageAlias::class);
        $alias->instanceToType()->shouldNotBeCalled();

        $decorator = new AggregateIdTypeEventDecorator($alias->reveal());

        $decoratedMessage = $decorator->decorate($message);

        $this->assertEquals($message, $decoratedMessage);
    }

    /**
     * @test
     */
    public function it_raise_exception_if_aggregate_id_not_an_instance(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Aggregate id must be an instance of ' . AggregateId::class);

        $aggregateId = SomeAggregateId::create()->toString();

        $message = new Message(SomeEvent::fromPayload([]), [
            MessageHeader::AGGREGATE_ID_TYPE => null,
            MessageHeader::AGGREGATE_ID => $aggregateId
        ]);

        $alias = $this->prophesize(MessageAlias::class);
        $alias->instanceToType()->shouldNotBeCalled();

        $decorator = new AggregateIdTypeEventDecorator($alias->reveal());

        $decorator->decorate($message);
    }

    public function provideUnsupportedMessage(): Generator
    {
        yield [new Message(SomeCommand::fromPayload([]))];

        yield [new Message(SomeQuery::fromPayload([]))];
    }
}
