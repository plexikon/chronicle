<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Factory;

use Plexikon\Chronicle\Clock\SystemClock;
use Plexikon\Chronicle\Messaging\Alias\ClassNameMessageAlias;
use Plexikon\Chronicle\Messaging\Decorator\ChainMessageDecorator;
use Plexikon\Chronicle\Messaging\Decorator\EventIdMessageDecorator;
use Plexikon\Chronicle\Messaging\Decorator\EventTypeMessageDecorator;
use Plexikon\Chronicle\Messaging\Decorator\TimeOfRecordingMessageDecorator;
use Plexikon\Chronicle\Reporter\Subscribers\ChainMessageDecoratorSubscriber;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageSubscriber;
use Plexikon\Chronicle\Support\Contract\Tracker\Tracker;

final class MessageSubscriberFactory
{
    private MessageAlias $messageAlias;
    private array $messageSubscribers = [];

    protected function __construct(?MessageAlias $messageAlias)
    {
        $this->messageAlias = $messageAlias ?? new ClassNameMessageAlias();
    }

    public static function create(?MessageAlias $messageAlias): self
    {
        return new self($messageAlias);
    }

    public function addSubscribers(MessageSubscriber ...$messageSubscribers): self
    {
        $this->messageSubscribers += $messageSubscribers;

        return $this;
    }

    public function withDefaultMessageDecorators(): self
    {
        $this->messageSubscribers[] = new ChainMessageDecoratorSubscriber(
            new ChainMessageDecorator(
                new EventIdMessageDecorator(),
                new EventTypeMessageDecorator($this->messageAlias),
                new TimeOfRecordingMessageDecorator(new SystemClock()),
            )
        );

        return $this;
    }

    public function onDispatch(callable $callback, int $priority = 0): MessageSubscriber
    {
        return $this->instance($callback, Reporter::DISPATCH_EVENT, $priority);
    }

    public function onFinalize(callable $callback, int $priority = 0): MessageSubscriber
    {
        return $this->instance($callback, Reporter::FINALIZE_EVENT, $priority);
    }

    public function instance(callable $callback, string $eventName, int $priority = 0): MessageSubscriber
    {
        return new class($callback, $eventName, $priority) implements MessageSubscriber {
            private $callback;
            private string $eventName;
            private int $priority;

            public function __construct(callable $callback, string $eventName, int $priority)
            {
                $this->callback = $callback;
                $this->eventName = $eventName;
                $this->priority = $priority;
            }

            public function attachToTracker(Tracker $tracker): void
            {
                $tracker->listen($this->eventName, function (MessageContext $context): void {
                    ($this->callback)($context);
                }, $this->priority);
            }
        };
    }

    final public function messageSubscribers(): array
    {
        return $this->messageSubscribers;
    }
}
