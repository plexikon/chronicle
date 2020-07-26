<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Chronicling\Aggregate;

use Plexikon\Chronicle\Chronicling\Aggregate\AggregateRepository;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Reporter\DomainEvent;
use Plexikon\Chronicle\Stream\Stream;
use Plexikon\Chronicle\Stream\StreamName;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateCache;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateId;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateRoot;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Chronicling\ReadOnlyChronicler;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageDecorator;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Tests\Double\SomeAggregateId;
use Plexikon\Chronicle\Tests\Double\SomeAggregateRoot;
use Plexikon\Chronicle\Tests\Double\SomeEvent;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

final class AggregateRepositoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_retrieve_aggregate_root_from_cache(): void
    {
        $this->cache->has($this->aggregateId)->willReturn(true);
        $this->cache->get($this->aggregateId)->willReturn($this->aggregateRoot->reveal())->shouldBeCalled();

        $repository = $this->repositoryInstance();
        $root = $repository->retrieve($this->aggregateId);

        $this->assertEquals($this->aggregateRoot->reveal(), $root);
    }

    /**
     * @test
     */
    public function it_reconstitute_aggregate_from_events_and_store_aggregate_in_cache(): void
    {
        $this->cache->has($this->aggregateId)->willReturn(false);

        $events = [
            new Message(SomeEvent::fromPayload(['foo', 'bar'])),
            new Message(SomeEvent::fromPayload(['baz', 'bar'])),
        ];

        $this->chronicler
            ->retrieveAll($this->aggregateId, $this->streamName)
            ->will(function () use ($events) {
                yield from $events;

                return 2;
            });

        $repository = $this->repositoryInstance();
        $root = $repository->retrieve($this->aggregateId);

        $this->cache->put($root)->shouldBeCalled();

        $this->assertInstanceOf(SomeAggregateRoot::class, $root);
        $this->assertTrue($root->exists());
        $this->assertEquals(2, $root->version());
    }

    /**
     * @test
     */
    public function it_store_aggregate_root(): void
    {
        // todo more events
        /** @var DomainEvent $event */
        $event = SomeEvent::fromPayload(['foo' => 'bar']);

        $expectedHeaders = [
            MessageHeader::AGGREGATE_ID => $this->aggregateId,
            MessageHeader::AGGREGATE_TYPE => $this->rootClass,
            MessageHeader::AGGREGATE_VERSION => 1
        ];

        $this->messageDecorator
            ->decorate(Argument::type(Message::class))
            ->will(function (array $events) use ($event, $expectedHeaders) {
                return new Message($event, $expectedHeaders);
            })
            ->shouldBeCalled();

        $this->chronicler->persist(
            Argument::that(function (Stream $stream) use ($event, $expectedHeaders) {
                $this->assertEquals($this->streamName, $stream->streamName());

                $streamEvent = $stream->events()->current();

                $this->assertEquals($event, $streamEvent->event());
                $this->assertEquals($expectedHeaders, $streamEvent->headers());

                return $stream;
            }));

        $this->cache->forget($this->aggregateId);

        $aggregateRoot = SomeAggregateRoot::create($this->aggregateId);

        $this->assertFalse($aggregateRoot->exists());
        $this->assertEquals(0, $aggregateRoot->version());

        $aggregateRoot->recordEvent($event);

        $this->assertTrue($aggregateRoot->exists());
        $this->assertEquals(1, $aggregateRoot->version());

        $repository = $this->repositoryInstance();
        $repository->persist($aggregateRoot);
    }

    /**
     * @test
     */
    public function it_can_flush_cache(): void
    {
        $repository = $this->repositoryInstance();

        $this->cache->flush()->shouldBeCalled();

        $repository->flushCache();
    }

    /**
     * @test
     */
    public function it_access_read_only_chronicler(): void
    {
        $repository = $this->repositoryInstance();

        $this->assertInstanceOf(ReadOnlyChronicler::class, $repository->chronicler());
    }

    private function repositoryInstance(): AggregateRepository
    {
        return new AggregateRepository(
            $this->rootClass,
            $this->chronicler->reveal(),
            $this->cache->reveal(),
            $this->streamName,
            $this->messageDecorator->reveal()
        );
    }

    private ObjectProphecy $aggregateRoot;
    private ObjectProphecy $chronicler;
    private ObjectProphecy $cache;
    private ObjectProphecy $messageDecorator;
    private StreamName $streamName;
    private AggregateId $aggregateId;
    private string $rootClass = SomeAggregateRoot::class;

    protected function setUp(): void
    {
        $this->rootClass = SomeAggregateRoot::class;
        $this->aggregateRoot = $this->prophesize(AggregateRoot::class);
        $this->chronicler = $this->prophesize(Chronicler::class);
        $this->cache = $this->prophesize(AggregateCache::class);
        $this->streamName = new StreamName('foo');
        $this->messageDecorator = $this->prophesize(MessageDecorator::class);
        $this->aggregateId = SomeAggregateId::create();
    }
}
