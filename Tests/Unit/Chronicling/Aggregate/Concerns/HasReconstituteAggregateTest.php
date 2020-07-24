<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Chronicling\Aggregate\Concerns;

use Generator;
use Illuminate\Support\LazyCollection;
use Plexikon\Chronicle\Chronicling\Aggregate\Concerns\HasReconstituteAggregate;
use Plexikon\Chronicle\Exception\StreamNotFound;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Stream\StreamName;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateId;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateRoot;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Tests\Double\SomeAggregateChanged;
use Plexikon\Chronicle\Tests\Double\SomeAggregateId;
use Plexikon\Chronicle\Tests\Double\SomeAggregateRoot;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use Prophecy\Prophecy\ObjectProphecy;

final class HasReconstituteAggregateTest extends TestCase
{
    /**
     * @test
     */
    public function it_reconstitute_aggregate_from_chronicler(): void
    {
        $rootId = SomeAggregateId::create();
        $events = $this->generateMessages($rootId->toString());

        $this->chronicler
            ->retrieveAll($rootId, $this->streamName)
            ->willReturn($events)
            ->shouldBeCalled();

        $instance = $this->reconstituteAggregateInstance();
        $reconstituteRoot = $instance->reconstitute($rootId);

        $this->assertEquals($rootId, $reconstituteRoot->aggregateId());
        $this->assertEmpty($reconstituteRoot->getRecordedEvents());
        $this->assertCount(2, $reconstituteRoot->getAppliedEvents());
        $this->assertEquals(2, $reconstituteRoot->version());
    }

    /**
     * @test
     */
    public function it_catch_stream_not_found_and_return_zero_version(): void
    {
        $rootId = SomeAggregateId::create();

        $this->chronicler
            ->retrieveAll($rootId, $this->streamName)
            ->willReturn($this->generateEmptyEvents())
            ->shouldBeCalled();

        $instance = $this->reconstituteAggregateInstance();
        $reconstituteRoot = $instance->reconstitute($rootId);

        $this->assertEquals(0, $reconstituteRoot->version());
        $this->assertFalse($reconstituteRoot->exists());
    }

    private function generateMessages(string $rootId): Generator
    {
        yield new Message(SomeAggregateChanged::withData($rootId, ['foo' => 'bar']));
        yield new Message(SomeAggregateChanged::withData($rootId, ['baz' => 'foo_bar']));

        return 2;
    }

    private function generateEmptyEvents(): Generator
    {
       $coll = new LazyCollection();
       $coll->whenEmpty(function(){
           throw new StreamNotFound('foo');
       });

       yield from $coll;
    }

    private string $aggregateRootClass = SomeAggregateRoot::class;
    private ObjectProphecy $chronicler;
    private StreamName $streamName;

    protected function setUp(): void
    {
        $this->chronicler = $this->prophesize(Chronicler::class);
        $this->streamName = new StreamName('foo');
    }

    private function reconstituteAggregateInstance(): object
    {
        $rootClass = $this->aggregateRootClass;
        $chronicler = $this->chronicler->reveal();
        $streamName = $this->streamName;
        return new class($rootClass, $chronicler, $streamName) {

            use HasReconstituteAggregate;

            public function __construct(string $rootClass, Chronicler $chronicler, StreamName $streamName)
            {
                $this->aggregateRoot = $rootClass;
                $this->chronicler = $chronicler;
                $this->streamName = $streamName;
            }

            public function reconstitute(AggregateId $aggregateId): AggregateRoot
            {
                return $this->reconstituteAggregateRoot($aggregateId);
            }
        };
    }
}
