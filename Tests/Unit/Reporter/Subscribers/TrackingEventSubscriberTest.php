<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Reporter\Subscribers;

use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Reporter\Subscribers\TrackingEventSubscriber;
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Tests\Double\SomeEvent;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use Plexikon\Chronicle\Tracker\TrackingReport;
use RuntimeException;

final class TrackingEventSubscriberTest extends TestCase
{
    /**
     * @test
     */
    public function it_track_event_on_dispatch_and_handle_message_handlers(): void
    {
        $isMessageHandled = [false, false];
        $oneMessageHandler = function (SomeEvent $command) use (&$isMessageHandled): void {
            $isMessageHandled[0] = true;
        };
        $secondMessageHandler = function (SomeEvent $command) use (&$isMessageHandled): void {
            $isMessageHandled[1] = true;
        };

        $message = new Message(SomeEvent::fromPayload(['foo']), [
            'foo_bar' => 'baz'
        ]);

        $subscriber = new TrackingEventSubscriber();
        $tracker = new TrackingReport();

        $subscriber->attachToTracker($tracker);

        $context = $tracker->newContext(Reporter::DISPATCH_EVENT);

        $this->assertFalse($context->isMessageHandled());

        $context->withMessage($message);
        $context->withMessageHandlers([$oneMessageHandler, $secondMessageHandler]);
        $tracker->fire($context);

        $this->assertEquals(['foo_bar' => 'baz'], $context->getMessage()->headers());
        $this->assertTrue($context->isMessageHandled());
        $this->assertTrue($isMessageHandled[0]);
        $this->assertTrue($isMessageHandled[1]);
    }

    /**
     * @test
     */
    public function it_track_event_on_finalize_and_raise_context_exception_if_exists(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('foo');

        $subscriber = new TrackingEventSubscriber();
        $tracker = new TrackingReport();

        $subscriber->attachToTracker($tracker);

        $context = $tracker->newContext(Reporter::FINALIZE_EVENT);
        $context->withRaisedException(new RuntimeException('foo'));
        $tracker->fire($context);
    }
}
