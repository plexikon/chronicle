<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Reporter\Subscribers;

use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Reporter\Subscribers\ReporterRouterSubscriber;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageProducer;
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Support\Contract\Reporter\Router;
use Plexikon\Chronicle\Tests\Double\SomeCommand;
use Plexikon\Chronicle\Tests\Double\SomeEvent;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use Plexikon\Chronicle\Tracker\TrackingReport;

final class ReporterRouterSubscriberTest extends TestCase
{
    /**
     * @test
     */
    public function it_route_sync_message(): void
    {
        $message = new Message(SomeEvent::fromPayload([]));

        $producer = $this->prophesize(MessageProducer::class);
        $producer->mustBeHandledSync($message)->willReturn(true)->shouldBeCalled();

        $router = $this->prophesize(Router::class);
        $router->route($message)->willReturn(['message_handlers'])->shouldBeCalled();

        $subscriber = new ReporterRouterSubscriber($router->reveal(), $producer->reveal());

        $tracker = new TrackingReport();
        $subscriber->attachToTracker($tracker);

        $context = $tracker->newContext(Reporter::DISPATCH_EVENT);
        $context->withMessage($message);
        $tracker->fire($context);

        $this->assertEquals(['message_handlers'], iterator_to_array($context->messageHandlers()));
    }

    /**
     * @test
     */
    public function it_route_async_message(): void
    {
        $message = new Message(SomeEvent::fromPayload([]));
        $messageWithAsyncMarker = new Message(SomeCommand::fromPayload([]));

        $producer = $this->prophesize(MessageProducer::class);
        $producer->mustBeHandledSync($message)->willReturn(false)->shouldBeCalled();
        $producer->produce($message)->willReturn($messageWithAsyncMarker)->shouldBeCalled();

        $router = $this->prophesize(Router::class);
        $router->route($message)->shouldNotBeCalled();

        $subscriber = new ReporterRouterSubscriber($router->reveal(), $producer->reveal());

        $tracker = new TrackingReport();
        $subscriber->attachToTracker($tracker);

        $context = $tracker->newContext(Reporter::DISPATCH_EVENT);
        $context->withMessage($message);
        $tracker->fire($context);

        $this->assertEquals($messageWithAsyncMarker, $context->getMessage());
        $this->assertEmpty(iterator_to_array($context->messageHandlers()));
    }
}
