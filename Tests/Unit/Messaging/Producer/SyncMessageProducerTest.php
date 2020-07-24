<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Messaging\Producer;

use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Messaging\Producer\SyncMessageProducer;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Tests\Double\SomeCommand;
use Plexikon\Chronicle\Tests\Unit\TestCase;

final class SyncMessageProducerTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideMessages
     * @param Message $message
     */
    public function it_always_return_false_on_is_marked_async(Message $message)
    {
        $producer = new SyncMessageProducer();

        $this->assertFalse($producer->isMarkedAsync($message));
    }

    /**
     * @test
     * @dataProvider provideMessages
     * @param Message $message
     */
    public function it_always_handled_sync_message(Message $message)
    {
        $producer = new SyncMessageProducer();

        $this->assertTrue($producer->mustBeHandledSync($message));
    }

    /**
     * @test
     * @dataProvider provideMessages
     * @param Message $message
     */
    public function it_always_return_same_message(Message $message)
    {
        $producer = new SyncMessageProducer();

        $this->assertEquals($message, $producer->produce($message));
    }

    public function provideMessages(): \Generator
    {
        yield[$message = new Message(SomeCommand::withData([]))];

        yield[$message = new Message(SomeCommand::withData([]),[
            MessageHeader::MESSAGE_ASYNC_MARKED => false
        ])];

        yield[$message = new Message(SomeCommand::withData([]),[
            MessageHeader::MESSAGE_ASYNC_MARKED => true
        ])];
    }
}
