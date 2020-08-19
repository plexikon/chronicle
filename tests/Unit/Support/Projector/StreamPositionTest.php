<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Support\Projector;

use Plexikon\Chronicle\Support\Contract\Chronicling\Model\EventStreamProvider;
use Plexikon\Chronicle\Support\Projector\StreamPosition;
use Plexikon\Chronicle\Tests\Unit\TestCase;

final class StreamPositionTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_constructed(): void
    {
        $this->eventStreamProvider->allStreamWithoutInternal()->shouldNotBeCalled();
        $streamPosition = new StreamPosition($this->eventStreamProvider->reveal());

        $this->assertEmpty($streamPosition->all());
    }

    /**
     * @test
     */
    public function it_prepare_defined_stream_positions(): void
    {
        $this->eventStreamProvider->allStreamWithoutInternal()->shouldNotBeCalled();
        $streamPosition = new StreamPosition($this->eventStreamProvider->reveal());

        $streams = ['foo', 'bar'];

        $streamPosition->make($streams);

        $this->assertEquals([
            'foo' => 0, 'bar' => 0
        ], $streamPosition->all());
    }

    /**
     * @test
     */
    public function it_prepare_all_stream_positions(): void
    {
        $this->eventStreamProvider->allStreamWithoutInternal()
            ->shouldBeCalled()->willReturn([
                'baz', 'foo', 'bar'
            ]);
        $streamPosition = new StreamPosition($this->eventStreamProvider->reveal());

        $streams = ['all'];
        $streamPosition->make($streams);

        $this->assertEquals([
            'baz' => 0, 'foo' => 0, 'bar' => 0
        ], $streamPosition->all());
    }

    /**
     * @test
     */
    public function it_merge_streams_with_current_streams(): void
    {
        $this->eventStreamProvider->allStreamWithoutInternal()->shouldNotBeCalled();
        $streamPosition = new StreamPosition($this->eventStreamProvider->reveal());

        $streams = ['foo', 'bar'];

        $streamPosition->make($streams);

        $this->assertEquals([
            'foo' => 0, 'bar' => 0
        ], $streamPosition->all());

        $streamPosition->make(['baz']);

        $this->assertEquals([
            'foo' => 0, 'bar' => 0, 'baz' => 0
        ], $streamPosition->all());
    }

    /**
     * @test
     */
    public function it_merge_reverse_from_remote_streams(): void
    {
        $this->eventStreamProvider->allStreamWithoutInternal()->shouldNotBeCalled();
        $streamPosition = new StreamPosition($this->eventStreamProvider->reveal());

        $streams = ['foo', 'bar'];

        $streamPosition->make($streams);

        $this->assertEquals([
            'foo' => 0, 'bar' => 0
        ], $streamPosition->all());

        $streamPosition->mergeStreamsFromRemote(['baz' => 0]);

        $this->assertEquals([
            'baz' => 0, 'foo' => 0, 'bar' => 0,
        ], $streamPosition->all());
    }

    /**
     * @test
     */
    public function it_set_position_of_stream(): void
    {
        $this->eventStreamProvider->allStreamWithoutInternal()->shouldNotBeCalled();
        $streamPosition = new StreamPosition($this->eventStreamProvider->reveal());

        $streams = ['foo', 'bar'];

        $streamPosition->make($streams);

        $this->assertEquals([
            'foo' => 0, 'bar' => 0
        ], $streamPosition->all());

        $streamPosition->setStreamNameAt('foo', 25);
        $streamPosition->setStreamNameAt('bar', 5);

        $this->assertEquals([
            'foo' => 25, 'bar' => 5
        ], $streamPosition->all());
    }

    /**
     * @test
     */
    public function it_reset_stream_positions(): void
    {
        $this->eventStreamProvider->allStreamWithoutInternal()->shouldNotBeCalled();
        $streamPosition = new StreamPosition($this->eventStreamProvider->reveal());

        $streams = ['foo', 'bar'];

        $streamPosition->make($streams);

        $this->assertEquals([
            'foo' => 0, 'bar' => 0
        ], $streamPosition->all());

        $streamPosition->reset();

        $this->assertEmpty($streamPosition->all());
    }

    private $eventStreamProvider;

    protected function setUp(): void
    {
        $this->eventStreamProvider = $this->prophesize(EventStreamProvider::class);
    }
}
