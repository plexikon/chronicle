<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Support\Projector;

use Generator;
use Plexikon\Chronicle\Exception\InvalidArgumentException;
use Plexikon\Chronicle\Stream\StreamName;
use Plexikon\Chronicle\Support\Projector\CachedStream;
use Plexikon\Chronicle\Tests\Unit\TestCase;

final class CachedStreamTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_constructed_with_size(): void
    {
        $cache = new CachedStream(2);

        $this->assertEquals([
            0 => null,
            1 => null
        ], $cache->all());
    }

    /**
     * @test
     */
    public function it_set_stream_to_the_next_position(): void
    {
        $cache = new CachedStream(2);

        $cache->toNextPosition(new StreamName('foo'));

        $this->assertEquals([
            0 => 'foo',
            1 => null
        ], $cache->all());

        $cache->toNextPosition(new StreamName('bar'));

        $this->assertEquals([
            0 => 'foo',
            1 => 'bar'
        ], $cache->all());

        $cache->toNextPosition(new StreamName('baz'));

        $this->assertEquals([
            0 => 'baz',
            1 => 'bar'
        ], $cache->all());
    }

    /**
     * @test
     */
    public function it_access_stream_by_position(): void
    {
        $cache = new CachedStream(2);

        $this->assertNull($cache->getStreamAt(0));
        $this->assertNull($cache->getStreamAt(1));

        $cache->toNextPosition(new StreamName('foo'));

        $this->assertEquals([
            0 => 'foo',
            1 => null
        ], $cache->all());

        $this->assertEquals(new StreamName('foo'), $cache->getStreamAt(0));
    }

    /**
     * @test
     */
    public function it_access_cache_size(): void
    {
        $cache = new CachedStream(10);

        $this->assertEquals(10, $cache->size());
    }

    /**
     * @test
     * @dataProvider provideInvalidPosition
     * @param int $invalidPosition
     */
    public function it_raise_exception_accessing_stream_position_with_invalid_position(int $invalidPosition): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectErrorMessage('Position must be between 0 and 1');

        $cache = new CachedStream(2);

        $cache->getStreamAt($invalidPosition);
    }

    /**
     * @test
     */
    public function it_assert_stream_exists(): void
    {
        $barStreamName = new StreamName('bar');

        $cache = new CachedStream(2);

        $this->assertFalse($cache->has(new StreamName('bar')));
        $this->assertFalse($cache->has($barStreamName));

        $cache->toNextPosition($barStreamName);

        $this->assertTrue($cache->has($barStreamName));
    }

    /**
     * @test
     * @dataProvider provideInvalidSize
     * @param int $invalidSize
     */
    public function it_raise_exception_with_invalid_size(int $invalidSize): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectErrorMessage('Size must be greater than 0');

        new CachedStream($invalidSize);
    }

    public function provideInvalidSize(): Generator
    {
        yield [-5];
        yield [0];
    }

    public function provideInvalidPosition(): Generator
    {
        yield [-1];
        yield [2];
        yield [3];
    }
}
