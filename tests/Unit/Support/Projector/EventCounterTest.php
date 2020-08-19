<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Support\Projector;

use Plexikon\Chronicle\Support\Projector\EventCounter;
use Plexikon\Chronicle\Tests\Unit\TestCase;

final class EventCounterTest extends TestCase
{

    /**
     * @test
     */
    public function it_start_counter_at_zero(): void
    {
        $counter = new EventCounter();

        $this->assertEquals(0, $counter->current());
    }

    /**
     * @test
     */
    public function it_increment_counter(): void
    {
        $counter = new EventCounter();

        $this->assertEquals(0, $counter->current());

        $i = 5;
        while ($i !== 0){
            $counter->increment();
            $i--;
        }

        $this->assertEquals(5, $counter->current());
    }

    /**
     * @test
     */
    public function it_reset_counter(): void
    {
        $counter = new EventCounter();

        $this->assertEquals(0, $counter->current());

        $counter->increment();

        $this->assertFalse($counter->isReset());

        $counter->reset();

        $this->assertTrue($counter->isReset());
    }

    /**
     * @test
     */
    public function it_check_equality_count(): void
    {
        $counter = new EventCounter();

        $counter->increment();

        $this->assertFalse($counter->equals(2));

        $counter->increment();

        $this->assertTrue($counter->equals(2));
    }
}
