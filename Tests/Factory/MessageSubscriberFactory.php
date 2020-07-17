<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Factory;

use Plexikon\Chronicle\Clock\SystemClock;
use Plexikon\Chronicle\Messaging\Alias\ClassNameMessageAlias;
use Plexikon\Chronicle\Messaging\Decorator\ChainMessageDecorator;
use Plexikon\Chronicle\Messaging\Decorator\EventIdMessageDecorator;
use Plexikon\Chronicle\Messaging\Decorator\EventTypeMessageDecorator;
use Plexikon\Chronicle\Messaging\Decorator\TimeOfRecordingMessageDecorator;
use Plexikon\Chronicle\Reporter\Subscriber\ChainMessageDecoratorSubscriber;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageSubscriber;

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

    final public function messageSubscribers(): array
    {
        return $this->messageSubscribers;
    }
}
