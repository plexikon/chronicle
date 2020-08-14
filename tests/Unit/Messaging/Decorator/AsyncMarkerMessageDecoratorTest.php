<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Messaging\Decorator;

use Generator;
use Plexikon\Chronicle\Messaging\Decorator\AsyncMarkerMessageDecorator;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Tests\Double\SomeCommand;
use Plexikon\Chronicle\Tests\Unit\TestCase;

final class AsyncMarkerMessageDecoratorTest extends TestCase
{
    /**
     * @test
     */
    public function it_decorate_message_with_async_marker_header(): void
    {
        $decorator = new AsyncMarkerMessageDecorator();

        $message = new Message(SomeCommand::fromPayload([]));

        $this->assertNull($message->header(MessageHeader::MESSAGE_ASYNC_MARKED));
        $decoratedMessage = $decorator->decorate($message);

        $this->assertFalse($decoratedMessage->header(MessageHeader::MESSAGE_ASYNC_MARKED));
        $this->assertNotEquals($message, $decoratedMessage);
    }

    /**
     * @test
     * @dataProvider provideBool
     * @param bool $asyncMarker
     */
    public function it_does_not_decorate_message_if_async_marker_header_already_exists(bool $asyncMarker): void
    {
        $decorator = new AsyncMarkerMessageDecorator();

        $message = new Message(SomeCommand::fromPayload([]), [MessageHeader::MESSAGE_ASYNC_MARKED => $asyncMarker]);

        $this->assertEquals($asyncMarker, $message->header(MessageHeader::MESSAGE_ASYNC_MARKED));

        $decoratedMessage = $decorator->decorate($message);

        $this->assertEquals($message, $decoratedMessage);
    }

    public function provideBool(): Generator
    {
        yield [true];
        yield [false];
    }
}
