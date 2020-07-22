<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Reporter\Subscribers;

use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Reporter\Subscribers\MessageFactorySubscriber;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageFactory;
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Tests\Double\SomeCommand;
use Plexikon\Chronicle\Tests\Double\SomeEvent;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use Plexikon\Chronicle\Tracker\TrackingMessage;

final class MessageFactorySubscriberTest extends TestCase
{
    /**
     * @test
     */
    public function it_attache_subscriber_to_tracker_and_create_message_instance(): void
    {
        $event = SomeCommand::fromPayload(['foo']);
        $message = new Message(SomeEvent::fromPayload(['bar']));

        $factory = $this->prophesize(MessageFactory::class);
        $factory->createMessageFrom($event)->willReturn($message)->shouldBeCalled();

        $subscriber = new MessageFactorySubscriber($factory->reveal());

        $tracker = new TrackingMessage();
        $subscriber->attachToTracker($tracker);

        $context = $tracker->newContext(Reporter::DISPATCH_EVENT);
        $context->withMessage($event);

        $tracker->fire($context);

        $this->assertEquals($message, $context->getMessage());
    }
}
