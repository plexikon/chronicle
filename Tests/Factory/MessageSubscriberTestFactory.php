<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Factory;

use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageSubscriber;
use Plexikon\Chronicle\Support\Contract\Tracker\Tracker;
use Plexikon\Chronicle\Tests\Unit\TestCase;

final class MessageSubscriberTestFactory
{
    /**
     * @var callable
     */
    private $testCallback;
    private TestCase $testCase;

    protected function __construct(TestCase $testCase, callable $TestCallback)
    {
        $this->testCase = $testCase;
        $this->testCallback = $TestCallback;
    }

    public static function create(TestCase $testCase, callable $testCallback): self
    {
        return new self($testCase, $testCallback);
    }

    public function onDispatch(int $priority = 0): MessageSubscriber
    {
        return $this->instance(Reporter::DISPATCH_EVENT, $priority);
    }

    public function onFinalize(int $priority = 0): MessageSubscriber
    {
        return $this->instance(Reporter::FINALIZE_EVENT, $priority);
    }

    public function instance(string $eventName, int $priority = 0): MessageSubscriber
    {
        $testCase = $this->testCase;
        $testCallback = $this->testCallback;

        return new class($testCase, $testCallback, $eventName, $priority) implements MessageSubscriber {
            private $testCallback;
            private TestCase $testCase;
            private string $eventName;
            private int $priority;

            public function __construct(TestCase $testCase, callable $testCallback, string $eventName, int $priority)
            {
                $this->testCase = $testCase;
                $this->testCallback = $testCallback;
                $this->eventName = $eventName;
                $this->priority = $priority;
            }

            public function attachToTracker(Tracker $tracker): void
            {
                $tracker->listen($this->eventName, function (MessageContext $context): void {
                    ($this->testCallback)($this->testCase, $context);
                }, $this->priority);
            }
        };
    }
}
