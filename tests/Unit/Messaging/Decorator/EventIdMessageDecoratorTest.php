<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Messaging\Decorator;

use Plexikon\Chronicle\Messaging\Decorator\EventIdMessageDecorator;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Tests\Double\SomeCommand;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class EventIdMessageDecoratorTest extends TestCase
{
    /**
     * @test
     */
    public function it_decorate_message_with_event_id_header(): void
    {
        $command = SomeCommand::withData(['foo' => 'bar']);
        $message = new Message($command);

        $this->assertNull($message->header(MessageHeader::EVENT_ID));

        $decorator = new EventIdMessageDecorator();
        $message = $decorator->decorate($message);

        $this->assertInstanceOf(UuidInterface::class, $message->header(MessageHeader::EVENT_ID));
    }

    /**
     * @test
     */
    public function it_does_not_override_event_id_header_if_already_exists(): void
    {
        $command = SomeCommand::withData(['foo' => 'bar']);
        $message = new Message($command);

        $eventId = Uuid::uuid4();
        $message = $message->withHeader(MessageHeader::EVENT_ID, $eventId);

        $decorator = new EventIdMessageDecorator();
        $decoratedMessage = $decorator->decorate($message);

        $this->assertEquals($eventId, $decoratedMessage->header(MessageHeader::EVENT_ID));
    }
}
