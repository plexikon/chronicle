<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit;

use Plexikon\Chronicle\Exception\StreamAlreadyExists;
use Plexikon\Chronicle\Exception\StreamNotFound;
use Plexikon\Chronicle\Exception\TransactionAlreadyStarted;
use Plexikon\Chronicle\Exception\TransactionNotStarted;
use Plexikon\Chronicle\InMemoryChronicler;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Stream\Stream;
use Plexikon\Chronicle\Stream\StreamName;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateId;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Tests\Double\SomeAggregateId;
use Plexikon\Chronicle\Tests\Double\SomeEvent;

final class InMemoryChroniclerTest extends TestCase
{
    /**
     * @test
     */
    public function it_persist_event_stream(): void
    {
        $this->setUpChronicler([], false);
    }

    /**
     * @test
     */
    public function it_raise_exception_if_event_stream_already_exists(): void
    {
        $this->expectException(StreamAlreadyExists::class);

        $chronicler = $this->setUpChronicler([], false);

        $chronicler->persistFirstCommit(new Stream(new StreamName('foo')));
    }

    /**
     * @test
     */
    public function it_persist_event_stream_with_events(): void
    {
        $messages = $this->buildMessagesWithHeaders(SomeAggregateId::create(), 10);

        $chronicler = $this->setUpChronicler($messages, false);

        $this->assertCount(10, $chronicler->getPersistedEvents());
    }

    /**
     * @test
     */
    public function it_cache_stream_events_if_in_transaction(): void
    {
        $this->setUpChronicler(
            $this->buildMessagesWithHeaders(SomeAggregateId::create(), 10),
            true
        );
    }

    /**
     * @test
     */
    public function it_reset_cached_streams_if_rollback_transaction(): void
    {
        $chronicler = new InMemoryChronicler();
        $streamName = new StreamName('foo');

        $chronicler->beginTransaction();

        $messages = $this->buildMessagesWithHeaders(SomeAggregateId::create(), 5);
        $chronicler->persistFirstCommit(new Stream($streamName, $messages));

        $this->assertCount(5, $chronicler->getCachedEvents());

        $chronicler->rollbackTransaction();

        $this->assertEmpty($chronicler->getCachedEvents());
    }

    /**
     * @test
     */
    public function it_raise_exception_if_stream_name_not_found(): void
    {
        $this->expectException(StreamNotFound::class);

        $chronicler = $this->setUpChronicler([], false);

        $chronicler->persist(new Stream(new StreamName('invalid'), [SomeEvent::fromPayload([])]));
    }

    /**
     * @test
     */
    public function it_raise_exception_when_retrieve_all_events_with_empty_stream_events(): void
    {
        $this->expectException(StreamNotFound::class);

        $chronicler = $this->setUpChronicler([], false);

        $chronicler->retrieveAll(SomeAggregateId::create(), new StreamName('foo'))->current();
    }

    /**
     * @test
     */
    public function it_raise_exception_if_begin_transaction_already_in_transaction(): void
    {
        $this->expectException(TransactionAlreadyStarted::class);

        $chronicler = new InMemoryChronicler();

        $chronicler->beginTransaction();

        $chronicler->beginTransaction();
    }

    /**
     * @test
     */
    public function it_raise_exception_if_commit_transaction_not_in_transaction(): void
    {
        $this->expectException(TransactionNotStarted::class);

        $chronicler = new InMemoryChronicler();

        $chronicler->commitTransaction();
    }

    /**
     * @test
     */
    public function it_raise_exception_if_rollback_transaction_not_in_transaction(): void
    {
        $this->expectException(TransactionNotStarted::class);

        $chronicler = new InMemoryChronicler();

        $chronicler->rollbackTransaction();
    }

    /**
     * @test
     */
    public function it_retrieve_events_by_aggregate_id(): void
    {
        $aggregateId = SomeAggregateId::create();
        $messages = $this->buildMessagesWithHeaders($aggregateId, 5);

        $chronicler = $this->setUpChronicler($messages, false);

        $anotherAggregateId = SomeAggregateId::create();
        $this->assertNotEquals($aggregateId, $anotherAggregateId);

        $chronicler->persist(new Stream(new StreamName('foo'), $this->buildMessagesWithHeaders(
            $anotherAggregateId, 10
        )));

        $streamEvents = $chronicler->retrieveAll($aggregateId, new StreamName('foo'), 'asc');

        $arrayEvents = iterator_to_array($streamEvents);
        $this->assertCount(5, $arrayEvents);

        $this->assertEquals(range(0, 4), array_keys($arrayEvents));
    }

    /**
     * @test
     */
    public function it_retrieve_reversed_events_by_aggregate_id(): void
    {
        $aggregateId = SomeAggregateId::create();
        $messages = $this->buildMessagesWithHeaders($aggregateId, 5);

        $chronicler = $this->setUpChronicler($messages, false);
        $streamEvents = $chronicler->retrieveAll($aggregateId, new StreamName('foo'), 'desc');

        $arrayEvents = iterator_to_array($streamEvents);

        $this->assertCount(5, $arrayEvents);

        $this->assertEquals(range(4, 0), array_keys($arrayEvents));
    }

    /**
     * @test
     */
    public function it_retrieve_events_with_query_filter(): void
    {
        $this->markTestSkipped('todo:' . __METHOD__);
    }

    /**
     * @test
     */
    public function it_delete_stream(): void
    {
        $chronicler = $this->setUpChronicler([SomeEvent::fromPayload([])], true);

        $streamName = new StreamName('foo');
        $this->assertTrue($chronicler->hasStream($streamName));

        $chronicler->delete($streamName);

        $this->assertFalse($chronicler->hasStream($streamName));
    }

    /**
     * @test
     */
    public function it_raise_exception_if_delete_a_not_found_stream(): void
    {
        $this->expectException(StreamNotFound::class);

        $chronicler = $this->setUpChronicler([], false);

        $streamName = new StreamName('invalid');
        $this->assertFalse($chronicler->hasStream($streamName));

        $chronicler->delete($streamName);
    }

    /**
     * @test
     */
    public function it_fetch_stream_names(): void
    {
        $chronicler = $this->setUpChronicler([SomeEvent::fromPayload([])], false);

        $fooStreamName = new StreamName('foo');
        $this->assertEquals([$fooStreamName], $chronicler->fetchStreamNames($fooStreamName));

        $barStreamName = new StreamName('bar');
        $chronicler->persistFirstCommit(new Stream($barStreamName));

        $streamNames = $chronicler->fetchStreamNames($barStreamName, $fooStreamName);
        $this->assertContainsEquals($fooStreamName, $streamNames);
        $this->assertContainsEquals($barStreamName, $streamNames);

        $invalidStream = new StreamName('foo_bar');
        $streamNames = $chronicler->fetchStreamNames($barStreamName, $fooStreamName, $invalidStream);

        $this->assertContainsEquals($fooStreamName, $streamNames);
        $this->assertContainsEquals($barStreamName, $streamNames);
        $this->assertNotContains($invalidStream, $streamNames);
    }

    private function setUpChronicler(array $events, bool $inTransaction): InMemoryChronicler
    {
        $chronicler = new InMemoryChronicler();

        $streamName = new StreamName('foo');
        $this->assertFalse($chronicler->hasStream($streamName));

        if ($inTransaction) {
            $chronicler->beginTransaction();

            $this->assertTrue($chronicler->inTransaction());

            $chronicler->persistFirstCommit(new Stream($streamName, $events));

            $this->assertEquals($events, $chronicler->getCachedEvents());

            $chronicler->commitTransaction();

            $this->assertFalse($chronicler->inTransaction());

        } else {
            $this->assertFalse($chronicler->inTransaction());

            $chronicler->persistFirstCommit(new Stream($streamName, $events));
        }

        $this->assertTrue($chronicler->hasStream($streamName));

        $this->assertEquals($events, $chronicler->getPersistedEvents());

        return $chronicler;
    }

    private function buildMessagesWithHeaders(AggregateId $aggregateId, int $num): array
    {
        $messages = [];

        while ($num !== 0) {
            $messages [] = new Message(
                SomeEvent::fromPayload([]),
                [MessageHeader::AGGREGATE_ID => $aggregateId]
            );

            --$num;
        }

        return $messages;
    }
}
