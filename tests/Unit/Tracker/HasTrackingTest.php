<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Tracker;

use Illuminate\Support\Collection;
use Plexikon\Chronicle\Support\Contract\Tracker\Context;
use Plexikon\Chronicle\Support\Contract\Tracker\Tracker;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use Plexikon\Chronicle\Tracker\Concerns\HasContext;
use Plexikon\Chronicle\Tracker\Concerns\HasTracking;
use RuntimeException;

final class HasTrackingTest extends TestCase
{
    /**
     * @test
     */
    public function it_listen_to_events(): void
    {
        $tracker = $this->trackerInstance();

        $this->assertTrue($tracker->getListeners()->isEmpty());

        $firstListener = $tracker->listen('foo', function (Context $context): void {
            //
        }, 1);

        $this->assertEquals($firstListener, $tracker->getListeners()->first());

        $tracker->listen('foo', function (Context $context): void {
            //
        }, 1);

        $this->assertCount(2, $tracker->getListeners());
    }

    /**
     * @test
     */
    public function it_forget_event(): void
    {
        $tracker = $this->trackerInstance();

        $this->assertTrue($tracker->getListeners()->isEmpty());

        $firstListener = $tracker->listen('foo', function (Context $context): void {
            //
        }, 1);

        $this->assertEquals($firstListener, $tracker->getListeners()->first());

        $tracker->forget($firstListener);

        $this->assertTrue($tracker->getListeners()->isEmpty());

        $secondListener = $tracker->listen('foo', function (Context $context): void {
            //
        }, 1);

        $thirdListener = $tracker->listen('foo', function (Context $context): void {
            //
        }, 1);

        $this->assertCount(2, $tracker->getListeners());

        $tracker->forget($secondListener);

        $this->assertEquals($thirdListener, $tracker->getListeners()->first());
    }

    /**
     * @test
     */
    public function it_fire_event_and_execute_listeners_by_sorting_priorities_descendant(): void
    {
        $tracker = $this->trackerInstance();

        $tracker->listen('another_event', function (Context $context): void {
            throw new RuntimeException("Should not be executed");
        });

        $executionOrdered = [];
        foreach ([10, 20, 30] as $listenerPriority) {
            $tracker->listen('foo', function (Context $context) use (&$executionOrdered, $listenerPriority): void {
                $executionOrdered[] = $listenerPriority;
            }, $listenerPriority);
        }

        $this->assertCount(4, $tracker->getListeners());

        $context = $tracker->newContext('foo');
        $tracker->fire($context);

        $this->assertEquals([30, 20, 10], $executionOrdered);
    }

    /**
     * @test
     */
    public function it_fire_event_and_stop_execution_on_true_callback(): void
    {
        $tracker = $this->trackerInstance();

        $executionOrdered = [];
        foreach ([1, 2, 3] as $listenerPriority) {
            $tracker->listen('foo', function (Context $context) use (&$executionOrdered, $listenerPriority): void {
                $executionOrdered[] = $listenerPriority;

                if ($listenerPriority === 2) {
                    $context->withRaisedException(new RuntimeException("trigger callback"));
                } elseif ($listenerPriority === 1) {
                    throw new RuntimeException("Should not be executed");
                }
            }, $listenerPriority);
        }

        $this->assertCount(3, $tracker->getListeners());

        $context = $tracker->newContext('foo');

        $tracker->fireUntil($context, function (Context $context): bool {
            return $context->hasException()
                && $context->getException()->getMessage() === 'trigger callback';
        });

        $this->assertEquals([3, 2], $executionOrdered);
    }

    private function trackerInstance(): Tracker
    {
        return new class() implements Tracker {
            use HasTracking;

            public function __construct()
            {
                $this->listeners = new Collection();
            }

            public function newContext(string $eventName): Context
            {
                return $this->contextInstance($eventName);
            }

            public function getListeners(): Collection
            {
                return $this->listeners;
            }

            private function contextInstance(string $eventName): Context
            {
                return new class($eventName) implements Context {
                    use HasContext;

                    public function __construct(string $eventName)
                    {
                        $this->currentEvent = $eventName;
                    }
                };
            }
        };
    }
}
