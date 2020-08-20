<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Chronicling;

use Generator;
use Plexikon\Chronicle\Chronicling\EventDispatcherSubscriber;
use Plexikon\Chronicle\Chronicling\TransactionalEventChronicler;
use Plexikon\Chronicle\Exception\ConcurrencyException;
use Plexikon\Chronicle\Exception\StreamAlreadyExists;
use Plexikon\Chronicle\Exception\StreamNotFound;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Reporter\ReportEvent;
use Plexikon\Chronicle\Stream\Stream;
use Plexikon\Chronicle\Stream\StreamName;
use Plexikon\Chronicle\Support\Contract\Chronicling\EventChronicler;
use Plexikon\Chronicle\Support\Contract\Chronicling\TransactionalChronicler;
use Plexikon\Chronicle\Tests\Double\SomeEvent;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use Plexikon\Chronicle\Tracker\TransactionalTrackingEvent;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionClass;
use Throwable;

final class EventDispatcherSubscriberTest extends TestCase
{
    /**
     * @test
     * @dataProvider providePersistingEvent
     * @param string $event
     * @param bool   $inTransaction
     * @param array  $events
     */
    public function it_dispatch_or_record_events_depends_on_transaction(string $event, bool $inTransaction, array $events): void
    {
        $stream = new Stream(new StreamName('foo'), $events);

        $chronicler = $this->prophesize(TransactionalChronicler::class);
        $chronicler->inTransaction()->willReturn($inTransaction);

        $event === EventChronicler::FIRST_COMMIT_EVENT
            ? $chronicler->persistFirstCommit($stream)
            : $chronicler->persist($stream);

        $chroniclerDecorator = new TransactionalEventChronicler($chronicler->reveal(), $this->tracker);

        $context = $this->tracker->newContext($event);
        $context->withStream($stream);

        $subscriber = new EventDispatcherSubscriber($this->reportEvent->reveal());

        // test flatten array
        if ($inTransaction) {
            $this->reportEvent->publish()->shouldNotBeCalled();
        } else {
            $this->reportEvent->publish(Argument::any())->shouldBeCalled();
        }

        $this->assertRecordedEvents($subscriber, []);

        $subscriber->attachToChronicler($chroniclerDecorator);

        $this->tracker->fire($context);

        $this->assertRecordedEvents($subscriber, $inTransaction ? $events : []);
    }

    /**
     * @test
     * @dataProvider provideExceptionOnEvent
     * @param string    $event
     * @param Throwable $exception
     */
    public function it_does_not_dispatch_events_when_context_as_exception(string $event, Throwable $exception): void
    {
        $events = [
            new Message(SomeEvent::fromPayload(['foo' => 'bar'])),
            new Message(SomeEvent::fromPayload(['baz' => 'bar'])),
        ];

        $stream = new Stream(new StreamName('foo'), $events);

        $chronicler = $this->prophesize(TransactionalChronicler::class);
        $chronicler->inTransaction()->willReturn(false);

        $event === EventChronicler::FIRST_COMMIT_EVENT
            ? $chronicler->persistFirstCommit($stream)
            : $chronicler->persist($stream);

        $chroniclerDecorator = new TransactionalEventChronicler($chronicler->reveal(), $this->tracker);

        $context = $this->tracker->newContext($event);
        $context->withStream($stream);
        $context->withRaisedException($exception);

        $subscriber = new EventDispatcherSubscriber($this->reportEvent->reveal());;

        $this->reportEvent->publish()->shouldNotBeCalled();

        $this->assertRecordedEvents($subscriber, []);

        $subscriber->attachToChronicler($chroniclerDecorator);

        $this->tracker->fire($context);

        $this->assertRecordedEvents($subscriber, []);
    }

    /**
     * @test
     * @dataProvider provideTransactionalEvent
     * @param string $event
     */
    public function it_commit_or_rollback_transaction(string $event): void
    {
        $events = [
            new Message(SomeEvent::fromPayload(['foo' => 'bar'])),
            new Message(SomeEvent::fromPayload(['baz' => 'bar'])),
        ];
        $chronicler = $this->prophesize(TransactionalChronicler::class)->reveal();
        $chroniclerDecorator = new TransactionalEventChronicler($chronicler, $this->tracker);

        $context = $this->tracker->newContext($event);

        $subscriber = new EventDispatcherSubscriber($this->reportEvent->reveal());;

        if ($event === TransactionalChronicler::COMMIT_TRANSACTION_EVENT) {
            $this->reportEvent->publish(Argument::any())->shouldBeCalled();
        } else {
            $this->reportEvent->publish()->shouldNotBeCalled();
        }

        $this->assertRecordedEvents($subscriber, []);

        $sub = new ReflectionClass($subscriber);
        $prop = $sub->getProperty('recordedStreams');
        $prop->setAccessible(true);
        $prop->setValue($subscriber, $events);

        $this->assertRecordedEvents($subscriber, $events);

        $subscriber->attachToChronicler($chroniclerDecorator);

        $this->tracker->fire($context);

        $this->assertRecordedEvents($subscriber, []);
    }

    private function assertRecordedEvents(EventDispatcherSubscriber $subscriber, array $events): void
    {
        $sub = new ReflectionClass($subscriber);
        $prop = $sub->getProperty('recordedStreams');
        $prop->setAccessible(true);

        $recordedEvents = $prop->getValue($subscriber);

        empty($events) ? $this->assertEmpty($recordedEvents) : $this->assertEquals($events, $recordedEvents);
    }

    public function provideExceptionOnEvent(): Generator
    {
        yield [EventChronicler::PERSIST_STREAM_EVENT, new StreamNotFound('foo')];

        yield [EventChronicler::PERSIST_STREAM_EVENT, new ConcurrencyException('foo')];

        yield [EventChronicler::FIRST_COMMIT_EVENT, new StreamAlreadyExists('foo')];
    }

    public function providePersistingEvent(): Generator
    {
        $events = [
            new Message(SomeEvent::fromPayload(['foo' => 'bar'])),
            new Message(SomeEvent::fromPayload(['baz' => 'bar'])),
        ];

        yield [EventChronicler::FIRST_COMMIT_EVENT, true, $events];

        yield [EventChronicler::FIRST_COMMIT_EVENT, false, $events];

        yield [EventChronicler::PERSIST_STREAM_EVENT, true, $events];

        yield [EventChronicler::PERSIST_STREAM_EVENT, false, $events];
    }

    public function provideTransactionalEvent(): Generator
    {
        yield [TransactionalChronicler::COMMIT_TRANSACTION_EVENT];

        yield [TransactionalChronicler::ROLLBACK_TRANSACTION_EVENT];
    }

    private TransactionalTrackingEvent $tracker;
    private ObjectProphecy $reportEvent;

    protected function setUp(): void
    {
        $this->tracker = new TransactionalTrackingEvent();
        $this->reportEvent = $this->prophesize(ReportEvent::class);
    }
}
