<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit;

use Generator;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Plexikon\Chronicle\Exception\QueryFailure;
use Plexikon\Chronicle\Exception\StreamAlreadyExists;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\PgsqlChronicler;
use Plexikon\Chronicle\Stream\Stream;
use Plexikon\Chronicle\Stream\StreamName;
use Plexikon\Chronicle\Support\Connection\StreamEventLoader;
use Plexikon\Chronicle\Support\Contract\Chronicling\Model\EventStreamProvider;
use Plexikon\Chronicle\Support\Contract\Chronicling\PersistenceStrategy;
use Plexikon\Chronicle\Support\Contract\Chronicling\WriteLockStrategy;
use Plexikon\Chronicle\Tests\Double\SomeAggregateId;
use Plexikon\Chronicle\Tests\Double\SomeEvent;
use Plexikon\Chronicle\Tests\Double\SomePDOException;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;

final class PgsqlChroniclerTest extends TestCase
{
    /**
     * @test
     */
    public function it_persist_first_commit(): void
    {
        $streamName = new StreamName('foo');
        $stream = new Stream($streamName, []);

        $this->persistenceStrategy->tableName($streamName)->willReturn('bar_table')->shouldBeCalled();

        $this->eventStreamProvider
            ->createStream($streamName, 'bar_table')
            ->willReturn(true)
            ->shouldBeCalled();

        $this->persistenceStrategy->up('bar_table')
            ->willReturn(null)
            ->shouldBeCalled();

        $chronicler = $this->pgsqlChroniclerInstance(false);
        $chronicler->persistFirstCommit($stream);
    }

    /**
     * @test
     * @dataProvider provideCreateEventStreamException
     * @param QueryException $exception
     * @param string $expectedException
     */
    public function it_raise_exception_on_create_event_stream(QueryException $exception, string $expectedException): void
    {
        $this->expectException($expectedException);

        $streamName = new StreamName('foo');
        $stream = new Stream($streamName, []);

        $this->persistenceStrategy->tableName($streamName)->willReturn('bar_table')->shouldBeCalled();

        $this->eventStreamProvider
            ->createStream($streamName, 'bar_table')
            ->willThrow($exception);

        $this->persistenceStrategy->up()->shouldNotBeCalled();

        $chronicler = $this->pgsqlChroniclerInstance(false);
        $chronicler->persistFirstCommit($stream);
    }

    /**
     * @test
     */
    public function it_raise_exception_on_create_event_stream_which_return_false(): void
    {
        $this->expectException(QueryFailure::class);
        $this->expectExceptionMessage('Unable to insert data in event stream table');

        $streamName = new StreamName('foo');
        $stream = new Stream($streamName, []);

        $this->persistenceStrategy->tableName($streamName)->willReturn('bar_table')->shouldBeCalled();

        $this->eventStreamProvider
            ->createStream($streamName, 'bar_table')
            ->willReturn(false);

        $this->persistenceStrategy->up()->shouldNotBeCalled();

        $chronicler = $this->pgsqlChroniclerInstance(false);
        $chronicler->persistFirstCommit($stream);
    }

    /**
     * @test
     */
    public function it_raise_exception_on_up_projection_table(): void
    {
        $this->expectException(QueryException::class);

        $streamName = new StreamName('foo');
        $stream = new Stream($streamName, []);

        $this->persistenceStrategy->tableName($streamName)->willReturn('bar_table')->shouldBeCalled();

        $this->eventStreamProvider
            ->createStream($streamName, 'bar_table')
            ->willReturn(true);

        $exception = new QueryException('sql', [], new RuntimeException('baz'));

        $this->persistenceStrategy->up('bar_table')->willThrow($exception);

        $schemaBuilder = $this->prophesize(SchemaBuilder::class);
        $schemaBuilder->drop('bar_table')->shouldBeCalled();

        $this->connection->getSchemaBuilder()->willReturn($schemaBuilder)->shouldBeCalled();

        $this->eventStreamProvider->deleteStream($streamName);

        $chronicler = $this->pgsqlChroniclerInstance(false);
        $chronicler->persistFirstCommit($stream);
    }

    /**
     * @test
     * *@dataProvider provideQueryDirection
     */
    public function it_retrieve_all_events_of_aggregate_id_and_stream_name_with_direction(string $direction): void
    {
        $aggregateId = SomeAggregateId::create();
        $streamName = new StreamName('foo');

        $events = [
            0 => new Message(SomeEvent::fromPayload(['foo', 'bar'])),
            1 => new Message(SomeEvent::fromPayload(['baz', 'bar'])),
        ];

        if ('desc' === $direction) {
            krsort($events);
        }

        $this->persistenceStrategy->tableName($streamName)->willReturn('bar_table')->shouldBeCalled();

        $builder = $this->prophesize(Builder::class);
        $builder
            ->whereJsonContains('headers->__aggregate_id', $aggregateId->toString())
            ->willReturn($builder)
            ->shouldBeCalled();

        $builder
            ->orderBy('no', $direction)
            ->willReturn($builder)
            ->shouldBeCalled();

        $this->connection->table('bar_table')->willReturn($builder)->shouldBeCalled();

        $this->streamEventLoader
            ->query(Argument::type(Builder::class), $streamName)
            ->will(function () use ($events): Generator {
                yield from $events;

                return count($events);
            })
            ->shouldBeCalled();

        $chronicler = $this->pgsqlChroniclerInstance(false);
        $streamEvents = $chronicler->retrieveAll($aggregateId, $streamName, $direction);

        $this->assertEquals($events, iterator_to_array($streamEvents));
    }

    public function provideCreateEventStreamException(): Generator
    {
        yield [new QueryException('sql', [], new SomePDOException('23000')), StreamAlreadyExists::class];

        yield [new QueryException('sql', [], new SomePDOException('23505')), StreamAlreadyExists::class];

        yield [new QueryException('sql', [], new SomePDOException('not 23000 nor 23505')), QueryFailure::class];

        yield [new QueryException('sql', [], new RuntimeException('any')), QueryFailure::class];
    }

    public function provideQueryDirection(): Generator
    {
        yield ['asc'];
        yield ['desc'];
    }

    private function pgsqlChroniclerInstance(bool $disableTransaction): PgsqlChronicler
    {
        return new PgsqlChronicler(
            $this->connection->reveal(),
            $this->eventStreamProvider->reveal(),
            $this->persistenceStrategy->reveal(),
            $this->writeLockStrategy->reveal(),
            $this->streamEventLoader->reveal(),
            $disableTransaction
        );
    }

    private ObjectProphecy $connection;
    private ObjectProphecy $eventStreamProvider;
    private ObjectProphecy $persistenceStrategy;
    private ObjectProphecy $writeLockStrategy;
    private ObjectProphecy $streamEventLoader;

    protected function setUp(): void
    {
        //$this->connection = $this->prophesize(ConnectionInterface::class);
        $this->connection = $this->prophesize(Connection::class);
        $this->eventStreamProvider = $this->prophesize(EventStreamProvider::class);
        $this->persistenceStrategy = $this->prophesize(PersistenceStrategy::class);
        $this->writeLockStrategy = $this->prophesize(WriteLockStrategy::class);
        $this->streamEventLoader = $this->prophesize(StreamEventLoader::class);
    }
}
