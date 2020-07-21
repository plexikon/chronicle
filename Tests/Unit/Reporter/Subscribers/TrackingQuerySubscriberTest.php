<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Reporter\Subscribers;

use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Reporter\Subscribers\TrackingQuerySubscriber;
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Support\HasPromiseHandler;
use Plexikon\Chronicle\Tests\Double\SomeQuery;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use Plexikon\Chronicle\Tracker\TrackingReport;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use RuntimeException;

final class TrackingQuerySubscriberTest extends TestCase
{
    use HasPromiseHandler;

    /**
     * @test
     */
    public function it_track_query_on_dispatch_and_handle_one_message_handler(): void
    {
        $messageHandler = function (SomeQuery $query, Deferred $promise): void {
            $promise->resolve($query->toPayload());
        };

        $message = new Message(SomeQuery::fromPayload(['foo']), [
            'foo_bar' => 'baz'
        ]);

        $subscriber = new TrackingQuerySubscriber();
        $tracker = new TrackingReport();

        $subscriber->attachToTracker($tracker);

        $context = $tracker->newContext(Reporter::DISPATCH_EVENT);

        $this->assertFalse($context->isMessageHandled());

        $context->withMessage($message);
        $context->withMessageHandlers([$messageHandler]);
        $tracker->fire($context);

        $this->assertEquals(['foo_bar' => 'baz'], $context->getMessage()->headers());
        $this->assertTrue($context->isMessageHandled());

        $promise = $context->getPromise();
        $this->assertInstanceOf(PromiseInterface::class, $promise);

        $this->assertEquals(['foo'], $this->handlePromise($promise));
    }

    /**
     * @test
     */
    public function it_track_query_on_finalize_and_hold_exception_if_raised_on_promise(): void
    {
        $messageHandler = function (SomeQuery $query, Deferred $promise): void {
            throw new RuntimeException('foo');
        };

        $message = new Message(SomeQuery::fromPayload(['foo']), [
            'foo_bar' => 'baz'
        ]);

        $subscriber = new TrackingQuerySubscriber();
        $tracker = new TrackingReport();

        $subscriber->attachToTracker($tracker);

        $context = $tracker->newContext(Reporter::DISPATCH_EVENT);

        $this->assertFalse($context->isMessageHandled());

        $context->withMessage($message);
        $context->withMessageHandlers([$messageHandler]);
        $tracker->fire($context);

        $promise = $context->getPromise();
        $this->assertInstanceOf(PromiseInterface::class, $promise);

        $result = $this->handlePromise($promise, false);

        $this->assertInstanceOf(RuntimeException::class, $result);
        $this->assertEquals('foo', $result->getMessage());
    }
}
