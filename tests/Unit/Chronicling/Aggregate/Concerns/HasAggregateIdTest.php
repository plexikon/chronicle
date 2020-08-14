<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Chronicling\Aggregate\Concerns;

use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateId;
use Plexikon\Chronicle\Tests\Double\AnotherAggregateId;
use Plexikon\Chronicle\Tests\Double\SomeAggregateId;
use Plexikon\Chronicle\Tests\Unit\TestCase;

final class HasAggregateIdTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideAggregateIdInstance
     * @param AggregateId $anotherAggregateId
     * @throws \Exception
     */
    public function it_can_be_compared_with_aggregate_class_and_identifier(AggregateId $anotherAggregateId): void
    {
        $aggregateId = SomeAggregateId::create();

        $this->assertTrue($aggregateId->equalsTo($aggregateId));
        $this->assertFalse($aggregateId->equalsTo($anotherAggregateId));
    }

    /**
     * @test
     */
    public function it_can_be_constructed_from_string(): void
    {
        $aggregateIdString = '585b32a0-cb40-4403-84c4-deaea5706c3c';

        $aggregateId = SomeAggregateId::fromString($aggregateIdString);

        $this->assertEquals($aggregateIdString, $aggregateId->toString());
    }

    public function provideAggregateIdInstance(): \Generator
    {
        yield [SomeAggregateId::create()];

        yield [AnotherAggregateId::create()];
    }
}
