<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Feature\Util;

use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageSubscriber;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageTracker;

trait SpyOnDispatchEventReporterTrait
{
    protected function spyAfterMessageDecorator(callable $context, string $configSubscriberKey): void
    {
        $subscriber = $this->messageSubscriber(Reporter::PRIORITY_MESSAGE_DECORATOR - 1, $context);

        $this->mergeConfigSubscribers($configSubscriberKey, $subscriber);
    }

    protected function messageSubscriber(int $priority, callable $callback): MessageSubscriber
    {
        return new class($priority, $callback) implements MessageSubscriber {

            private int $priority;

            /**
             * @var callable
             */
            private $callback;

            public function __construct(int $priority, $callback)
            {
                $this->priority = $priority;
                $this->callback = $callback;
            }

            public function attachToTracker(MessageTracker $tracker): void
            {
                $tracker->listen(Reporter::DISPATCH_EVENT, function (MessageContext $context): void {
                    ($this->callback)($context);
                }, $this->priority);
            }
        };
    }

    protected function mergeConfigSubscribers(string $configSubscriberKey, MessageSubscriber ...$messageSubscribers): void
    {
        $subscribers = array_merge($this->app['config']->get($configSubscriberKey) ?? [], $messageSubscribers);

        $this->app['config']->set($configSubscriberKey, $subscribers);
    }
}
