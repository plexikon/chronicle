<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Messaging;

use Generator;
use Plexikon\Chronicle\Exception\RuntimeException;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Messaging\Messaging;
use Plexikon\Chronicle\Tests\Double\SomeCommand;
use Plexikon\Chronicle\Tests\Double\SomeEvent;
use Plexikon\Chronicle\Tests\Double\SomeQuery;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use stdClass;

final class MessageTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_constructed(): void
    {
        $event = SomeCommand::withData(['foo' => 'bar']);
        $message = new Message($event);

        $this->assertEquals($event, $message->event());
        $this->assertEquals($event, $message->eventWithHeaders());
    }

    /**
     * @test
     */
    public function it_can_add_headers_to_message(): void
    {
        $event = SomeCommand::withData(['foo' => 'bar']);
        $message = new Message($event, ['baz' => 'foo_bar']);

        $this->assertEquals($event, $message->event());
        $this->assertNotEquals($event, $message->eventWithHeaders());

        $messageCopy = $message->withHeaders(['bar' => 'baz']);
        $this->assertNotEquals($message, $messageCopy);

        $this->assertEquals(['baz' => 'foo_bar', 'bar' => 'baz'], $messageCopy->headers());
    }

    /**
     * @test
     */
    public function it_override_header(): void
    {
        $event = SomeCommand::withData(['foo' => 'bar']);
        $message = new Message($event, ['baz' => 'foo_bar']);

        $messageCopy = $message->withHeader('baz', 'foo');

        $this->assertEquals('foo_bar', $message->header('baz'));
        $this->assertEquals('foo', $messageCopy->header('baz'));
    }

    /**
     * @test
     * @dataProvider provideEvent
     * @param object $event
     * @param bool $expected
     */
    public function it_can_check_if_event_is_instance_of_messaging(object $event, bool $expected): void
    {
        $message = new Message($event);

        $this->assertEquals($expected, $message->isMessaging());
    }

    /**
     * @test
     */
    public function it_copy_headers_from_message_to_event(): void
    {
        $event = SomeCommand::fromPayload(['foo']);

        $this->assertEmpty($event->headers());

        $message = new Message($event, ['baz' => 'foo_bar']);

        $this->assertEmpty($event->headers());

        $eventWithHeaders = $message->eventWithHeaders();

        $this->assertEquals(['baz' => 'foo_bar'], $eventWithHeaders->headers());
    }

    /**
     * @test
     */
    public function it_raise_exception_if_call_event_with_headers_not_instance_of_messaging(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid event, expected an instance of ' . Messaging::class);

        $message = new Message(new stdClass(), ['foo' => 'bar']);
        $message->eventWithHeaders();
    }

    public function provideEvent(): Generator
    {
        yield [SomeCommand::fromPayload(['foo']), true];

        yield [SomeEvent::fromPayload(['foo']), true];

        yield [SomeQuery::fromPayload(['foo']), true];

        yield [new stdClass(), false];

        yield [new class {
        }, false];
    }
}
