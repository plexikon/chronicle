<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Chronicling\Aggregate;

use Illuminate\Contracts\Cache\Store;
use Plexikon\Chronicle\Chronicling\Aggregate\AggregateCache;
use Plexikon\Chronicle\Exception\InvalidArgumentException;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateId;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateRoot;
use Plexikon\Chronicle\Tests\Unit\TestCase;

final class AggregateCacheTest extends TestCase
{
    /**
     * @test
     */
    public function it_cache_aggregate(): void
    {
        $id = $this->prophesize(AggregateId::class);
        $id->toString()->willReturn('foo')->shouldBeCalled();

        $root = $this->prophesize(AggregateRoot::class);
        $root->exists()->willReturn(true)->shouldBeCalled();
        $root->aggregateId()->willReturn($id)->shouldBeCalled();

        $cache = $this->prophesize(Store::class);
        $cache->put('foo', $root->reveal(), 0)->shouldBeCalled();
        $cache->get('foo')->willReturn(null)->shouldBeCalled();

        $store = new AggregateCache($cache->reveal());
        $store->put($root->reveal());

        $this->assertCount(1, $store);
    }

    /**
     * @test
     */
    public function it_override_aggregate_cached_if_already_exists(): void
    {
        $id = $this->prophesize(AggregateId::class);
        $id->toString()->willReturn('foo')->shouldBeCalled();

        $root = $this->prophesize(AggregateRoot::class);
        $root->exists()->willReturn(true)->shouldBeCalled();
        $root->aggregateId()->willReturn($id)->shouldBeCalled();

        $cache = $this->prophesize(Store::class);
        $cache->get('foo')->willReturn(null, 'bar');
        $cache->put('foo', $root->reveal(), 0)->shouldBeCalledTimes(2);

        $store = new AggregateCache($cache->reveal());
        $store->put($root->reveal());
        $store->put($root->reveal());

        $this->assertCount(1, $store);
    }

    /**
     * @test
     */
    public function it_raise_exception_if_caching_an_aggregate_which_does_not_exists(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $root = $this->prophesize(AggregateRoot::class);
        $root->exists()->willReturn(false)->shouldBeCalled();

        $store = $this->prophesize(Store::class);
        $store->put($this->prophesize(AggregateId::class)->reveal(), $root, 0);

        (new AggregateCache($store->reveal()))->put($root->reveal());
    }

    /**
     * @test
     */
    public function it_flush_aggregate_cache_when_limit_is_hit(): void
    {
        $id = $this->prophesize(AggregateId::class);
        $id->toString()->willReturn('foo')->shouldBeCalled();

        $root = $this->prophesize(AggregateRoot::class);
        $root->exists()->willReturn(true)->shouldBeCalled();
        $root->aggregateId()->willReturn($id)->shouldBeCalled();

        $cache = $this->prophesize(Store::class);
        $cache->get('foo')->willReturn(null, null);
        $cache->put('foo', $root->reveal(), 0)->shouldBeCalledTimes(2);
        $cache->flush()->willReturn(true)->shouldBeCalled();

        $store = new AggregateCache($cache->reveal(), 1);
        $this->assertCount(0, $store);

        $store->put($root->reveal());
        $this->assertCount(1, $store);

        $store->put($root->reveal());
        $this->assertCount(1, $store);
    }

    /**
     * @test
     */
    public function it_forget_aggregate_root(): void
    {
        $id = $this->prophesize(AggregateId::class);
        $id->toString()->willReturn('foo')->shouldBeCalled();

        $root = $this->prophesize(AggregateRoot::class);
        $root->exists()->willReturn(true)->shouldBeCalled();
        $root->aggregateId()->willReturn($id)->shouldBeCalled();

        $cache = $this->prophesize(Store::class);
        $cache->get('foo')->willReturn(null, $root, null);
        $cache->put('foo', $root->reveal(), 0)->shouldBeCalled();
        $cache->forget('foo')->willReturn(true)->shouldBeCalled();

        $store = new AggregateCache($cache->reveal());
        $store->put($root->reveal());
        $store->forget($id->reveal());

        $this->assertNull($store->get($id->reveal()));
    }
}
