<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Reporter\Subscribers;

use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Reporter\Subscribers\TrackingCommandSubscriber;
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Tests\Double\SomeCommand;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use Plexikon\Chronicle\Tracker\TrackingReport;
use RuntimeException;

final class TrackingCommandSubscriberTest extends TestCase
{
    /**
     * @test
     */
    public function it_track_command_on_dispatch_and_handle_one_message_handler(): void
    {
        $isMessageHandled = false;
        $messageHandler = function (SomeCommand $command) use (&$isMessageHandled): void {
            $isMessageHandled = true;
        };
        $message = new Message(SomeCommand::fromPayload(['foo']),[
            'foo_bar' => 'baz'
        ]);

        $subscriber = new TrackingCommandSubscriber();
        $tracker = new TrackingReport();

        $subscriber->attachToTracker($tracker);

        $context = $tracker->newContext(Reporter::DISPATCH_EVENT);

        $this->assertFalse($context->isMessageHandled());

        $context->withMessage($message);
        $context->withMessageHandlers([$messageHandler]);
        $tracker->fire($context);

        $this->assertEquals(['foo_bar' => 'baz'], $context->getMessage()->headers());
        $this->assertTrue($context->isMessageHandled());
        $this->assertTrue($isMessageHandled);
    }

    /**
     * @test
     */
    public function it_track_command_on_finalize_and_raise_context_exception_if_exists(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('foo');

        $subscriber = new TrackingCommandSubscriber();
        $tracker = new TrackingReport();

        $subscriber->attachToTracker($tracker);

        $context = $tracker->newContext(Reporter::FINALIZE_EVENT);
        $context->withRaisedException(new RuntimeException('foo'));
        $tracker->fire($context);
    }
}
