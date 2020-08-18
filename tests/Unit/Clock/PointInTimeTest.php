<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Clock;

use DateInterval;
use Plexikon\Chronicle\Clock\PointInTime;
use Plexikon\Chronicle\Clock\SystemClock;
use Plexikon\Chronicle\Exception\InvalidArgumentException;
use Plexikon\Chronicle\Tests\Unit\TestCase;

final class PointInTimeTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_instantiated_from_string(): void
    {
        $clock = (new SystemClock())->dateTime();
        $clockString = $clock->format(PointInTime::DATE_TIME_FORMAT);

        $pointInTime = PointInTime::fromString($clockString);

        $this->assertEquals($pointInTime->toString(), $clockString);
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_from_date_time(): void
    {
        $dateTime = (new SystemClock())->dateTime();

        $pointInTime = PointInTime::fromDateTime($dateTime);

        $this->assertEquals($pointInTime->dateTime(), $dateTime);
    }

    /**
     * @test
     */
    public function it_can_be_compared_and_assert_false(): void
    {
        $pointInTime = (new SystemClock())->pointInTime();
        $pointInTime2 = (new SystemClock())->pointInTime();

        $this->assertFalse($pointInTime->equals($pointInTime2));
    }

    /**
     * @test
     */
    public function it_can_be_compared_and_assert_true(): void
    {
        $pointInTime = (new SystemClock())->pointInTime();

        $this->assertTrue($pointInTime->equals($pointInTime));
    }

    /**
     * @test
     */
    public function it_can_add_interval(): void
    {
        $pointInTime = (new SystemClock())->pointInTime();

        $interval = new DateInterval('PT10S');

        $next = $pointInTime->add('PT10S');

        $this->assertNotEquals($pointInTime, $next);
        $this->assertEquals($interval->format('s'), $pointInTime->diff($next)->format('s'));
        $this->assertGreaterThan($pointInTime, $next);
    }

    /**
     * @test
     */
    public function it_can_sub_interval(): void
    {
        $pointInTime = (new SystemClock())->pointInTime();

        $interval = new DateInterval('PT10S');

        $previous = $pointInTime->sub('PT10S');

        $this->assertNotEquals($pointInTime, $previous);
        $this->assertEquals($interval->format('s'), $pointInTime->diff($previous)->format('s'));
        $this->assertLessThan($pointInTime, $previous);
    }

    /**
     * @test
     */
    public function it_can_be_diff_and_return_interval(): void
    {
        $pointInTime = (new SystemClock())->pointInTime();

        $interval = new DateInterval('PT10S');

        $next = $pointInTime->add('PT10S');

        $this->assertEquals($interval, $pointInTime->diff($next));
    }

    /**
     * @test
     */
    public function it_assert_is_greater_than(): void
    {
        $pointInTime = (new SystemClock())->pointInTime();

        $next = $pointInTime->add('PT10S');
        $this->assertTrue($next->after($pointInTime));

        $previous = $pointInTime->sub('PT10S');
        $this->assertFalse($previous->after($pointInTime));
    }

    /**
     * @test
     */
    public function it_can_be_serialized(): void
    {
        $dateTimeString = '2020-07-24T16:01:07.612585';

        $pointInTime = PointInTime::fromString($dateTimeString);

        $this->assertEquals($dateTimeString, $pointInTime->toString());
    }

    /**
     * @test
     */
    public function it_can_be_serialized_with_magic_method(): void
    {
        $dateTimeString = '2020-07-24T16:01:07.612585';

        $pointInTime = PointInTime::fromString($dateTimeString);

        $this->assertEquals($dateTimeString, (string)$pointInTime);
    }
}
