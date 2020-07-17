<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Messaging\Decorator;

use Plexikon\Chronicle\Messaging\Decorator\EventTypeMessageDecorator;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Tests\Double\SomeCommand;
use Plexikon\Chronicle\Tests\Unit\TestCase;

final class EventTypeMessageDecoratorTest extends TestCase
{
    /**
     * @test
     */
    public function it_decorate_message_with_event_type_header(): void
    {
        $command = SomeCommand::withData(['foo' => 'bar']);
        $message = new Message($command);
        $this->assertNull($message->header(MessageHeader::EVENT_TYPE));

        $messageAlias = $this->prophesize(MessageAlias::class);
        $messageAlias->instanceToType($command)->willReturn('foo_bar');

        $decorator = new EventTypeMessageDecorator($messageAlias->reveal());
        $message = $decorator->decorate($message);

        $this->assertEquals('foo_bar', $message->header(MessageHeader::EVENT_TYPE));
    }

    /**
     * @test
     */
    public function it_does_not_override_event_type_header_if_already_exists(): void
    {
        $command = SomeCommand::withData(['foo' => 'bar']);
        $message = new Message($command);
        $this->assertNull($message->header(MessageHeader::EVENT_TYPE));

        $messageAlias = $this->prophesize(MessageAlias::class);
        $messageAlias->instanceToType($command)->shouldNotBeCalled();

        $decoratedMessage = $message->withHeader(MessageHeader::EVENT_TYPE, 'baz');

        $decorator = new EventTypeMessageDecorator($messageAlias->reveal());
        $decoratedMessage = $decorator->decorate($decoratedMessage);

        $this->assertEquals('baz', $decoratedMessage->header(MessageHeader::EVENT_TYPE));
    }
}
