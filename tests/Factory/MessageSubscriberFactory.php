<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Factory;

use PHPUnit\Framework\TestCase;
use Plexikon\Chronicle\Clock\PointInTime;
use Plexikon\Chronicle\Clock\SystemClock;
use Plexikon\Chronicle\Messaging\Alias\ClassNameMessageAlias;
use Plexikon\Chronicle\Messaging\Decorator\ChainMessageDecorator;
use Plexikon\Chronicle\Messaging\Decorator\EventIdMessageDecorator;
use Plexikon\Chronicle\Messaging\Decorator\EventTypeMessageDecorator;
use Plexikon\Chronicle\Messaging\Decorator\TimeOfRecordingMessageDecorator;
use Plexikon\Chronicle\Reporter\Subscribers\ChainMessageDecoratorSubscriber;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Support\Contract\Reporter\TrackingReporter;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageSubscriber;
use Plexikon\Chronicle\Support\Contract\Tracker\Tracker;
use Ramsey\Uuid\UuidInterface;

final class MessageSubscriberFactory
{
    /**
     * @var MessageSubscriber[]
     */
    private array $messageSubscribers = [];
    private MessageAlias $messageAlias;

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
        $this->messageSubscribers = array_merge($this->messageSubscribers, $messageSubscribers);

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
        return $this->onEvent(Reporter::DISPATCH_EVENT, $callback, $priority);
    }

    public function onFinalize(callable $callback, int $priority = 0): MessageSubscriber
    {
        return $this->onEvent(Reporter::FINALIZE_EVENT, $callback, $priority);
    }

    public function onEvent(string $eventName, callable $callback, int $priority = 0): MessageSubscriber
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

    public function withDefaultMessageHeaderAssertion(TestCase $testCase, string $reporterName, string $fqcnEvent): self
    {
        $context = function (MessageContext $context) use ($testCase, $reporterName, $fqcnEvent): void {
            $message = $context->getMessage();

            $testCase->assertEquals($reporterName, $message->header(MessageHeader::MESSAGE_BUS_NAME));
            $testCase->assertInstanceOf(UuidInterface::class, $message->header(MessageHeader::EVENT_ID));
            $testCase->assertInstanceOf(PointInTime::class, $message->header(MessageHeader::TIME_OF_RECORDING));
            $testCase->assertEquals(
                $this->messageAlias->classToType($fqcnEvent),
                $message->header(MessageHeader::EVENT_TYPE)
            );
        };

        $this->addSubscribers($this->onDispatch($context, Reporter::PRIORITY_MESSAGE_DECORATOR - 1));

        return $this;
    }

    public function withMessagePayloadAssertion(TestCase $testCase, array $payload): self
    {
        $context = function (MessageContext $context) use ($testCase, $payload): void {
            $message = $context->getMessage();

            $testCase->assertEquals($payload, $message->event()->toPayload());
        };

        $this->addSubscribers($this->onDispatch($context, Reporter::PRIORITY_MESSAGE_DECORATOR - 1));

        return $this;
    }

    public function withMessageHandledAssertion(TestCase $testCase): self
    {
        $context = function (MessageContext $context) use ($testCase): void {
            $testCase->assertTrue($context->isMessageHandled());
        };

        $this->addSubscribers($this->onFinalize($context));

        return $this;
    }

    public function subscribeToReporter(TrackingReporter $reporter): void
    {
        foreach($this->messageSubscribers() as $messageSubscriber){
            $reporter->subscribe($messageSubscriber);
        }
    }

    final public function messageSubscribers(): array
    {
        return $this->messageSubscribers;
    }
}
