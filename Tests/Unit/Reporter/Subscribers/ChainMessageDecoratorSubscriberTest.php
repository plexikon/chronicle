<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Reporter\Subscribers;

use Plexikon\Chronicle\Messaging\Decorator\ChainMessageDecorator;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Reporter\Subscribers\ChainMessageDecoratorSubscriber;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageDecorator;
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Tests\Double\SomeCommand;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use Plexikon\Chronicle\Tracker\DefaultMessageContext;
use Plexikon\Chronicle\Tracker\TrackingReport;

final class ChainMessageDecoratorSubscriberTest extends TestCase
{
    /**
     * @test
     */
    public function it_subscribe_to_tracker_and_decorate_message(): void
    {
        $message = new Message(SomeCommand::fromPayload(['value']));
        $decorator = $this->someMessageDecorator();
        $subscriber = new ChainMessageDecoratorSubscriber($decorator);

        $context = new DefaultMessageContext(Reporter::DISPATCH_EVENT);
        $context->withMessage($message);

        $tracker = new TrackingReport();
        $tracker->listen(Reporter::DISPATCH_EVENT, function (MessageContext $context): void {
            $this->assertEquals(['baz' => 'foo_bar', 'foo_bar' => 'bar'], $context->getMessage()->headers());
        }, 80000);

        $subscriber->attachToTracker($tracker);

        $tracker->fire($context);
    }

    private function someMessageDecorator(): MessageDecorator
    {
        return new ChainMessageDecorator(
            new class() implements MessageDecorator {

                public function decorate(Message $message): Message
                {
                    return $message->withHeader('baz', 'foo_bar');
                }
            },
            new class() implements MessageDecorator {

                public function decorate(Message $message): Message
                {
                    return $message->withHeader('foo_bar', 'bar');
                }
            },
        );
    }
}
