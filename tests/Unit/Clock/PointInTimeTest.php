<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Clock;

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
    public function it_raise_exception_with_invalid_time(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PointInTime::fromString('invalid');
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
