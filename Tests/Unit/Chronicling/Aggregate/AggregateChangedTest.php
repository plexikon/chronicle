<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Chronicling\Aggregate;

use Plexikon\Chronicle\Tests\Double\SomeAggregateChanged;
use Plexikon\Chronicle\Tests\Unit\TestCase;

final class AggregateChangedTest extends TestCase
{
    /**
     * @test
     */
    public function it_access_aggregate_root_id(): void
    {
        $aggregateChanged = SomeAggregateChanged::withData('baz', []);

        $this->assertEquals('baz', $aggregateChanged->aggregateRootId());
    }

    /**
     * @test
     */
    public function it_access_payload(): void
    {
        $aggregateChanged = SomeAggregateChanged::withData('baz', ['foo' =>'bar']);

        $this->assertEquals(['foo' => 'bar'], $aggregateChanged->toPayload());
    }
}
