<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit;

use Generator;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Plexikon\Chronicle\Exception\ConcurrencyException;
use Plexikon\Chronicle\Exception\QueryFailure;
use Plexikon\Chronicle\Exception\StreamAlreadyExists;
use Plexikon\Chronicle\Exception\StreamNotFound;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\PgsqlChronicler;
use Plexikon\Chronicle\Stream\Stream;
use Plexikon\Chronicle\Stream\StreamName;
use Plexikon\Chronicle\Support\Connection\StreamEventLoader;
use Plexikon\Chronicle\Support\Contract\Chronicling\Model\EventStreamProvider;
use Plexikon\Chronicle\Support\Contract\Chronicling\PersistenceStrategy;
use Plexikon\Chronicle\Support\Contract\Chronicling\QueryFilter;
use Plexikon\Chronicle\Support\Contract\Chronicling\WriteLockStrategy;
use Plexikon\Chronicle\Support\Json;
use Plexikon\Chronicle\Tests\Double\SomeAggregateId;
use Plexikon\Chronicle\Tests\Double\SomeEvent;
use Plexikon\Chronicle\Tests\Double\SomePDOException;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionClass;
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

        $chronicler = $this->pgsqlChroniclerInstance();
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

        $chronicler = $this->pgsqlChroniclerInstance();
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

        $chronicler = $this->pgsqlChroniclerInstance();
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

        $chronicler = $this->pgsqlChroniclerInstance();
        $chronicler->persistFirstCommit($stream);
    }

    /**
     * @test
     */
    public function it_persist_stream_events(): void
    {
        $streamName = new StreamName('foo');

        $this->persistenceStrategy->tableName($streamName)->willReturn('bar_table')->shouldBeCalled();
        $this->writeLockStrategy->getLock('bar_table')->willReturn(true);

        $events = [
            0 => new Message(SomeEvent::fromPayload(['foo' => 'bar'])),
            1 => new Message(SomeEvent::fromPayload(['baz' => 'bar'])),
        ];

        $data = [
            [
                'event_id' => 'some.event_id',
                'event_type' => 'some.event',
                'payload' => Json::encode($events[0]->event()->toPayload()),
                'headers' => Json::encode($events[0]->headers()),
                'created_at' => 'time',
            ],
            [
                'event_id' => 'another.event_id',
                'event_type' => 'some.event',
                'payload' => Json::encode($events[1]->event()->toPayload()),
                'headers' => Json::encode($events[1]->headers()),
                'created_at' => 'time',
            ],
        ];

        $this->persistenceStrategy
            ->serializeMessage(Argument::type(Message::class))
            ->will(function (array $events) use (&$data) {
                return array_shift($data);
            });

        $builder = $this->prophesize(Builder::class);
        $builder->insert($data)->shouldBeCalled();

        $this->connection->table('bar_table')->willReturn($builder);
        $this->writeLockStrategy->releaseLock('bar_table')->shouldBeCalled();

        $chronicler = $this->pgsqlChroniclerInstance();
        $chronicler->persist(new Stream($streamName, $events));
    }

    /**
     * @test
     */
    public function it_raise_exception_while_acquiring_lock_failed_during_persist_events(): void
    {
        $this->expectException(ConcurrencyException::class);

        $streamName = new StreamName('foo');

        $this->persistenceStrategy->tableName($streamName)->willReturn('bar_table')->shouldBeCalled();
        $this->writeLockStrategy->getLock('bar_table')->willReturn(false);

        $events = [
            0 => new Message(SomeEvent::fromPayload(['foo' => 'bar'])),
            1 => new Message(SomeEvent::fromPayload(['baz' => 'bar'])),
        ];

        $this->persistenceStrategy->serializeMessage()->shouldNotBeCalled();
        $this->writeLockStrategy->releaseLock()->shouldNotBeCalled();

        $chronicler = $this->pgsqlChroniclerInstance();
        $chronicler->persist(new Stream($streamName, $events));
    }

    /**
     * @test
     */
    public function it_raise_exception_if_table_name_does_not_exists_during_persist_events(): void
    {
        $this->expectException(StreamNotFound::class);

        $streamName = new StreamName('foo');

        $this->persistenceStrategy->tableName($streamName)->willReturn('bar_table')->shouldBeCalled();
        $this->writeLockStrategy->getLock('bar_table')->willReturn(true);
        $this->persistenceStrategy->serializeMessage(Argument::type(Message::class))->willReturn([]);

        $events = [
            0 => new Message(SomeEvent::fromPayload(['foo' => 'bar'])),
            1 => new Message(SomeEvent::fromPayload(['baz' => 'bar'])),
        ];

        $exception = new QueryException('sql', [], new SomePDOException('42P01'));
        $this->connection->table('bar_table')->willThrow($exception);

        $this->writeLockStrategy->releaseLock()->shouldNotBeCalled();

        $chronicler = $this->pgsqlChroniclerInstance();
        $chronicler->persist(new Stream($streamName, $events));
    }

    /**
     * @test
     * @dataProvider providePersistingExceptionCode
     * @param string $exceptionCode
     */
    public function it_raise_exception_with_violation_constraint_during_persist_events(string $exceptionCode): void
    {
        $this->expectException(ConcurrencyException::class);

        $streamName = new StreamName('foo');

        $this->persistenceStrategy->tableName($streamName)->willReturn('bar_table')->shouldBeCalled();
        $this->writeLockStrategy->getLock('bar_table')->willReturn(true);
        $this->persistenceStrategy->serializeMessage(Argument::type(Message::class))->willReturn([]);

        $events = [
            0 => new Message(SomeEvent::fromPayload(['foo' => 'bar'])),
            1 => new Message(SomeEvent::fromPayload(['baz' => 'bar'])),
        ];

        $exception = new QueryException('sql', [], new SomePDOException($exceptionCode));
        $this->connection->table('bar_table')->willThrow($exception);

        $this->writeLockStrategy->releaseLock()->shouldNotBeCalled();

        $chronicler = $this->pgsqlChroniclerInstance();
        $chronicler->persist(new Stream($streamName, $events));
    }

    /**
     * @test
     */
    public function it_raise_exception_during_persist_events(): void
    {
        $this->expectException(QueryFailure::class);

        $streamName = new StreamName('foo');

        $this->persistenceStrategy->tableName($streamName)->willReturn('bar_table')->shouldBeCalled();
        $this->writeLockStrategy->getLock('bar_table')->willReturn(true);
        $this->persistenceStrategy->serializeMessage(Argument::type(Message::class))->willReturn([]);

        $events = [
            0 => new Message(SomeEvent::fromPayload(['foo' => 'bar'])),
            1 => new Message(SomeEvent::fromPayload(['baz' => 'bar'])),
        ];

        $exception = new QueryException('sql', [], new RuntimeException('baz'));
        $this->connection->table('bar_table')->willThrow($exception);

        $this->writeLockStrategy->releaseLock()->shouldNotBeCalled();

        $chronicler = $this->pgsqlChroniclerInstance();
        $chronicler->persist(new Stream($streamName, $events));
    }

    /**
     * @test
     */
    public function it_delete_stream(): void
    {
        $streamName = new StreamName('foo');

        $this->eventStreamProvider->deleteStream($streamName)->willReturn(true)->shouldBeCalled();
        $this->persistenceStrategy->tableName($streamName)->willReturn('bar_table');

        $builder = $this->prophesize(SchemaBuilder::class);
        $builder->drop('bar_table')->shouldBeCalled();
        $this->connection->getSchemaBuilder()->willReturn($builder);

        $chronicler = $this->pgsqlChroniclerInstance();
        $chronicler->delete($streamName);
    }

    /**
     * @test
     */
    public function it_raise_exception_if_stream_not_found_while_deleting_stream(): void
    {
        $this->expectException(StreamNotFound::class);

        $streamName = new StreamName('foo');

        $this->eventStreamProvider->deleteStream($streamName)->willReturn(false)->shouldBeCalled();
        $this->persistenceStrategy->tableName()->shouldNotBeCalled();

        $this->connection->getSchemaBuilder()->shouldNotBeCalled();

        $chronicler = $this->pgsqlChroniclerInstance();
        $chronicler->delete($streamName);
    }

    /**
     * @test
     */
    public function it_raise_exception_if_exception_raised_while_dropping_table(): void
    {
        $this->expectException(QueryFailure::class);

        $streamName = new StreamName('foo');

        $this->eventStreamProvider->deleteStream($streamName)->willReturn(true)->shouldBeCalled();
        $this->persistenceStrategy->tableName($streamName)->willReturn('bar_table');

        $builder = $this->prophesize(SchemaBuilder::class);
        $builder
            ->drop('bar_table')
            ->willThrow(new QueryException('sql', [], new RuntimeException('foo')))
            ->shouldBeCalled();
        $this->connection->getSchemaBuilder()->willReturn($builder);

        $chronicler = $this->pgsqlChroniclerInstance();
        $chronicler->delete($streamName);
    }

    /**
     * @test
     * *@dataProvider provideQueryDirection
     * @param string $direction
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
            ->query($builder, $streamName)
            ->will(function () use ($events): Generator {
                yield from $events;

                return count($events);
            })
            ->shouldBeCalled();

        $chronicler = $this->pgsqlChroniclerInstance();
        $streamEvents = $chronicler->retrieveAll($aggregateId, $streamName, $direction);

        $this->assertEquals($events, iterator_to_array($streamEvents));
    }

    /**
     * @test
     */
    public function it_retrieve_events_with_query_filter(): void
    {
        $streamName = new StreamName('foo');

        $events = [
            0 => new Message(SomeEvent::fromPayload(['foo', 'bar'])),
            1 => new Message(SomeEvent::fromPayload(['baz', 'bar'])),
        ];

        $this->persistenceStrategy->tableName($streamName)->willReturn('bar_table')->shouldBeCalled();

        $builder = $this->prophesize(Builder::class);
        $queryFilter = $this->prophesize(QueryFilter::class);
        $queryFilter->filterQuery()->willReturn(function (Builder $builder) {

        })->shouldBeCalled();

        $this->connection->table('bar_table')->willReturn($builder)->shouldBeCalled();

        $this->streamEventLoader
            ->query($builder, $streamName)
            ->will(function () use ($events): Generator {
                yield from $events;

                return count($events);
            })
            ->shouldBeCalled();

        $chronicler = $this->pgsqlChroniclerInstance();
        $streamEvents = $chronicler->retrieveWithQueryFilter($streamName, $queryFilter->reveal());

        $this->assertEquals($events, iterator_to_array($streamEvents));
    }

    /**
     * @test
     */
    public function it_fetch_stream_names_from_event_stream_provider(): void
    {
        $streamNameDoesNotExists = new StreamName('foo');
        $streamNameExists = new StreamName('bar');

        $chronicler = $this->pgsqlChroniclerInstance();

        $this->eventStreamProvider->filterByStreams([$streamNameDoesNotExists, $streamNameExists])->willReturn([$streamNameExists]);

        $this->assertEquals([$streamNameExists], $chronicler->fetchStreamNames($streamNameDoesNotExists, $streamNameExists));
    }

    /**
     * @test
     * @dataProvider provideStreamNameExistence
     * @param StreamName $streamName
     * @param bool $shouldExists
     */
    public function it_check_if_stream_name_exists(StreamName $streamName, bool $shouldExists): void
    {
        $chronicler = $this->pgsqlChroniclerInstance();

        $this->eventStreamProvider->hasRealStreamName($streamName)->willReturn($shouldExists);

        $this->assertEquals($shouldExists, $chronicler->hasStream($streamName));
    }

    /**
     * @test
     * @dataProvider provideBool
     * @param bool $isTransactionDisabled
     */
    public function it_check_if_transaction_is_disabled(bool $isTransactionDisabled): void
    {
        $chronicler = $this->pgsqlChroniclerInstance($isTransactionDisabled);

        $class = new ReflectionClass($chronicler);
        $method = $class->getMethod('isTransactionDisabled');
        $method->setAccessible(true);

        $this->assertEquals($isTransactionDisabled, $method->invoke($chronicler));
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

    public function provideStreamNameExistence(): Generator
    {
        yield[new StreamName('foo'), true];

        yield[new StreamName('bar'), true];
    }

    public function provideBool(): Generator
    {
        yield [true];
        yield [false];
    }

    public function providePersistingExceptionCode(): Generator
    {
        yield['23000'];
        yield['23505'];
    }

    private function pgsqlChroniclerInstance(bool $disableTransaction = false): PgsqlChronicler
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
