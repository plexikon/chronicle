<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Chronicling\Aggregate\Concerns;

use Generator;
use Plexikon\Chronicle\Tests\Double\SomeAggregateChanged;
use Plexikon\Chronicle\Tests\Double\SomeAggregateId;
use Plexikon\Chronicle\Tests\Double\SomeAggregateRoot;
use Plexikon\Chronicle\Tests\Unit\TestCase;

final class HasAggregateRootTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_constructed_from_aggregate_id(): void
    {
        $rootId = SomeAggregateId::create();

        $root = SomeAggregateRoot::create($rootId);

        $this->assertEquals($rootId, $root->aggregateId());
        $this->assertEquals(0, $root->version());
        $this->assertFalse($root->exists());
        $this->assertEmpty($root->getRecordedEvents());
        $this->assertEmpty($root->getAppliedEvents());
    }

    /**
     * @test
     */
    public function it_record_events(): void
    {
        $rootId = SomeAggregateId::create();
        $root = SomeAggregateRoot::create($rootId);

        $num = 5;
        $events = $this->generateMessage($num, $rootId->toString());
        $events = iterator_to_array($events);

        $root->recordEvent(...$events);

        $this->assertEquals($events, $root->getAppliedEvents());
        $this->assertEquals($events, $root->getRecordedEvents());
        $this->assertEquals($num, $root->version());
        $this->assertTrue($root->exists());
    }

    /**
     * @test
     */
    public function it_release_events(): void
    {
        $rootId = SomeAggregateId::create();

        $root = SomeAggregateRoot::create($rootId);

        $event = SomeAggregateChanged::withData($rootId->toString(), ['foo' => 'bar']);
        $root->recordEvent($event);

        $this->assertEquals([$event], $root->getAppliedEvents());
        $this->assertEquals([$event], $root->getRecordedEvents());
        $this->assertEquals(1, $root->version());

        $releasedEvents = $root->releaseEvents();
        $this->assertEquals([$event], $releasedEvents);
        $this->assertEmpty($root->getRecordedEvents());
        $this->assertEquals(1, $root->version());
        $this->assertTrue($root->exists());
    }

    private function generateMessage(int $num, string $rootId): Generator
    {
        while($num !== 0){
            yield SomeAggregateChanged::withData($rootId, ['foo' => 'bar']);
            --$num;
        }

        return $num;
    }
}
