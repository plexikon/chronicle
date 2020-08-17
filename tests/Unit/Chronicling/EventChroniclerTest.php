<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Chronicling;

use Generator;
use Plexikon\Chronicle\Chronicling\EventChronicler;
use Plexikon\Chronicle\Exception\ConcurrencyException;
use Plexikon\Chronicle\Exception\StreamAlreadyExists;
use Plexikon\Chronicle\Exception\StreamNotFound;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Stream\Stream;
use Plexikon\Chronicle\Stream\StreamName;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Chronicling\QueryFilter;
use Plexikon\Chronicle\Support\Contract\Tracker\EventContext;
use Plexikon\Chronicle\Tests\Double\SomeAggregateId;
use Plexikon\Chronicle\Tests\Double\SomeEvent;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use Plexikon\Chronicle\Tracker\TrackingEvent;
use Throwable;

final class EventChroniclerTest extends TestCase
{
    /**
     * @test
     */
    public function it_dispatch_first_commit_stream_event(): void
    {
        $stream = new Stream(new StreamName('foo'), []);

        $tracker = new TrackingEvent();
        $chronicler = $this->prophesize(Chronicler::class);
        $chronicler->persistFirstCommit($stream)->shouldBeCalled();

        $eventChronicler = new EventChronicler($chronicler->reveal(), $tracker);

        $tracker->listen(EventChronicler::FIRST_COMMIT_EVENT,
            function (EventContext $context) use ($stream): void {
                $this->assertEquals($stream, $context->stream());
            });

        $eventChronicler->persistFirstCommit($stream);
    }

    /**
     * @test
     */
    public function it_dispatch_first_commit_and_raise_exception(): void
    {
        $this->expectException(StreamAlreadyExists::class);

        $stream = new Stream(new StreamName('foo'), []);

        $tracker = new TrackingEvent();
        $chronicler = $this->prophesize(Chronicler::class);

        $exception = StreamAlreadyExists::withStreamName(new StreamName('foo'));
        $chronicler
            ->persistFirstCommit($stream)
            ->willThrow($exception)
            ->shouldBeCalled();

        $eventChronicler = new EventChronicler($chronicler->reveal(), $tracker);

        $tracker->listen(EventChronicler::FIRST_COMMIT_EVENT,
            function (EventContext $context) use ($exception): void {
                $this->assertEquals($exception, $context->getException());
            });

        $eventChronicler->persistFirstCommit($stream);
    }

    /**
     * @test
     */
    public function it_dispatch_persist_stream_event(): void
    {
        $stream = new Stream(new StreamName('foo'), []);

        $tracker = new TrackingEvent();
        $chronicler = $this->prophesize(Chronicler::class);
        $chronicler->persist($stream)->shouldBeCalled();

        $eventChronicler = new EventChronicler($chronicler->reveal(), $tracker);

        $tracker->listen(EventChronicler::PERSIST_STREAM_EVENT,
            function (EventContext $context) use ($stream): void {
                $this->assertEquals($stream, $context->stream());
            });

        $eventChronicler->persist($stream);
    }

    /**
     * @test
     * @dataProvider provideException
     * @param Throwable $exception
     */
    public function it_dispatch_persist_stream_event_and_raise_exception(Throwable $exception): void
    {
        $this->expectException(get_class($exception));

        $stream = new Stream(new StreamName('foo'), []);

        $tracker = new TrackingEvent();
        $chronicler = $this->prophesize(Chronicler::class);

        $chronicler
            ->persist($stream)
            ->willThrow($exception)
            ->shouldBeCalled();

        $eventChronicler = new EventChronicler($chronicler->reveal(), $tracker);

        $tracker->listen(EventChronicler::PERSIST_STREAM_EVENT,
            function (EventContext $context) use ($exception): void {
                $this->assertEquals($exception, $context->getException());
            });

        $eventChronicler->persist($stream);
    }

    /**
     * @test
     */
    public function it_dispatch_delete_stream_event(): void
    {
        $streamName = new StreamName('foo');

        $tracker = new TrackingEvent();
        $chronicler = $this->prophesize(Chronicler::class);
        $chronicler->delete($streamName)->shouldBeCalled();

        $eventChronicler = new EventChronicler($chronicler->reveal(), $tracker);

        $tracker->listen(EventChronicler::PERSIST_STREAM_EVENT,
            function (EventContext $context) use ($streamName): void {
                $this->assertEquals($streamName, $context->$streamName());
            });

        $eventChronicler->delete($streamName);
    }

    /**
     * @test
     */
    public function it_dispatch_delete_stream_event_and_raise_exception(): void
    {
        $this->expectException(StreamNotFound::class);

        $streamName = new StreamName('foo');

        $tracker = new TrackingEvent();
        $chronicler = $this->prophesize(Chronicler::class);

        $exception = StreamNotFound::withStreamName(new StreamName('foo'));
        $chronicler
            ->delete($streamName)
            ->willThrow($exception)
            ->shouldBeCalled();

        $eventChronicler = new EventChronicler($chronicler->reveal(), $tracker);

        $tracker->listen(EventChronicler::DELETE_STREAM_EVENT,
            function (EventContext $context) use ($exception): void {
                $this->assertEquals($exception, $context->getException());
            });

        $eventChronicler->delete($streamName);
    }


    /**
     * @test
     * @dataProvider provideEventDirection
     * @param string $event
     * @param string $direction
     */
    public function it_dispatch_retrieve_all_events_with_direction(string $event, string $direction): void
    {
        $aggregateId = SomeAggregateId::create();
        $streamName = new StreamName('foo');
        $expectedEvents = [
            new Message(SomeEvent::fromPayload(['foo'])),
            new Message(SomeEvent::fromPayload(['bar']))
        ];

        $tracker = new TrackingEvent();
        $chronicler = $this->prophesize(Chronicler::class);
        $chronicler
            ->retrieveAll($aggregateId, $streamName, $direction)
            ->willYield($expectedEvents)
            ->shouldBeCalled();

        $eventChronicler = new EventChronicler($chronicler->reveal(), $tracker);

        $tracker->listen($event, function (EventContext $context) use ($event, $direction, $aggregateId): void {
            $this->assertEquals($event, $context->getCurrentEvent());
            $this->assertEquals($direction, $context->direction());
            $this->assertEquals($aggregateId, $context->aggregateId());
        });

        $messages = $eventChronicler->retrieveAll($aggregateId, $streamName, $direction);

        $messages = iterator_to_array($messages);

        $this->assertEquals($expectedEvents, $messages);
    }

    /**
     * @test
     */
    public function it_dispatch_retrieve_events_with_query_filter(): void
    {
        $streamName = new StreamName('foo');
        $queryFilter = $this->prophesize(QueryFilter::class)->reveal();
        $expectedEvents = [
            new Message(SomeEvent::fromPayload(['foo'])),
            new Message(SomeEvent::fromPayload(['bar']))
        ];

        $tracker = new TrackingEvent();
        $chronicler = $this->prophesize(Chronicler::class);
        $chronicler
            ->retrieveWithQueryFilter($streamName, $queryFilter)
            ->willYield($expectedEvents)
            ->shouldBeCalled();

        $eventChronicler = new EventChronicler($chronicler->reveal(), $tracker);

        $tracker->listen(EventChronicler::RETRIEVE_ALL_FILTERED_STREAM_EVENT,
            function (EventContext $context) use ($streamName, $queryFilter): void {
                $this->assertEquals($streamName, $context->streamName());
                $this->assertEquals($queryFilter, $context->queryFilter());
            });

        $messages = $eventChronicler->retrieveWithQueryFilter($streamName, $queryFilter);

        $messages = iterator_to_array($messages);

        $this->assertEquals($expectedEvents, $messages);
    }

    /**
     * @test
     */
    public function it_dispatch_retrieve_events_with_query_filter_and_raise_exception(): void
    {
        $this->expectException(StreamNotFound::class);

        $streamName = new StreamName('foo');
        $exception = StreamNotFound::withStreamName($streamName);

        $queryFilter = $this->prophesize(QueryFilter::class)->reveal();

        $tracker = new TrackingEvent();
        $chronicler = $this->prophesize(Chronicler::class);
        $chronicler
            ->retrieveWithQueryFilter($streamName, $queryFilter)
            ->willThrow($exception)
            ->shouldBeCalled();

        $eventChronicler = new EventChronicler($chronicler->reveal(), $tracker);

        $tracker->listen(EventChronicler::RETRIEVE_ALL_FILTERED_STREAM_EVENT,
            function (EventContext $context) use ($streamName, $queryFilter, $exception): void {
                $this->assertEquals($streamName, $context->streamName());
                $this->assertEquals($queryFilter, $context->queryFilter());
                $this->assertEquals($exception, $context->getException());
            });

        $eventChronicler->retrieveWithQueryFilter($streamName, $queryFilter)->current();
    }

    /**
     * @test
     * @dataProvider provideEventDirection
     * @param string $event
     * @param string $direction
     */
    public function it_dispatch_retrieve_all_events_with_direction_and_raise_exception(string $event, string $direction): void
    {
        $this->expectException(StreamNotFound::class);

        $aggregateId = SomeAggregateId::create();
        $streamName = new StreamName('foo');
        $exception = StreamNotFound::withStreamName($streamName);

        $tracker = new TrackingEvent();
        $chronicler = $this->prophesize(Chronicler::class);
        $chronicler
            ->retrieveAll($aggregateId, $streamName, $direction)
            ->willThrow($exception)
            ->shouldBeCalled();

        $eventChronicler = new EventChronicler($chronicler->reveal(), $tracker);

        $tracker->listen($event, function (EventContext $context) use ($event, $direction, $aggregateId, $exception): void {
            $this->assertEquals($event, $context->getCurrentEvent());
            $this->assertEquals($direction, $context->direction());
            $this->assertEquals($aggregateId, $context->aggregateId());
            $this->assertEquals($exception, $context->getException());
        });

        $eventChronicler->retrieveAll($aggregateId, $streamName, $direction)->current();
    }

    /**
     * @test
     * @dataProvider provideBool
     * @param bool $streamExists
     */
    public function it_dispatch_has_stream_event(bool $streamExists): void
    {
        $streamName = new StreamName('foo');
        $tracker = new TrackingEvent();
        $chronicler = $this->prophesize(Chronicler::class);
        $chronicler->hasStream($streamName)->willReturn($streamExists)->shouldBeCalled();

        $eventChronicler = new EventChronicler($chronicler->reveal(), $tracker);
        $tracker->listen(EventChronicler::HAS_STREAM_EVENT, function (EventContext $context) use ($streamExists): void {
            $this->assertEquals($streamExists, $context->hasStream());
        });

        $eventChronicler->hasStream($streamName);
    }

    /**
     * @test
     */
    public function it_dispatch_fetch_stream_names_and_return_stream_names_if_exists(): void
    {
        $fooStreamName = new StreamName('foo');
        $barStreamName = new StreamName('bar');

        $tracker = new TrackingEvent();
        $chronicler = $this->prophesize(Chronicler::class);
        $chronicler->fetchStreamNames($fooStreamName, $barStreamName)->willReturn([
            $fooStreamName
        ])->shouldBeCalled();

        $eventChronicler = new EventChronicler($chronicler->reveal(), $tracker);
        $tracker->listen(EventChronicler::FETCH_STREAM_NAMES, function (EventContext $context) use ($fooStreamName): void {
            $this->assertEquals([$fooStreamName], $context->streamNames());
        });

        $eventChronicler->fetchStreamNames($fooStreamName, $barStreamName);
    }

    /**
     * @test
     */
    public function it_access_inner_chronicler(): void
    {
        $tracker = new TrackingEvent();
        $chronicler = $this->prophesize(Chronicler::class)->reveal();

        $eventChronicler = new EventChronicler($chronicler, $tracker);

        $this->assertEquals($chronicler, $eventChronicler->innerChronicler());
    }

    public function provideException(): Generator
    {
        yield [StreamNotFound::withStreamName(new StreamName('foo'))];

        yield [new ConcurrencyException('foo')];
    }

    public function provideEventDirection(): Generator
    {
        yield [EventChronicler::RETRIEVE_ALL_STREAM_EVENT, 'asc'];

        yield [EventChronicler::RETRIEVE_ALL_REVERSE_STREAM_EVENT, 'desc'];
    }

    public function provideBool(): Generator
    {
        yield [true];

        yield [false];
    }
}
