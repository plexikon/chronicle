<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Clock;

use DateTimeZone;
use Generator;
use Plexikon\Chronicle\Clock\SystemClock;
use Plexikon\Chronicle\Tests\Unit\TestCase;

final class SystemClockTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideTimeZone
     * @param DateTimeZone $timezone
     */
    public function it_can_be_constructed(?DateTimeZone $timezone): void
    {
        $timezone = $timezone ?? new DateTimeZone('UTC');

        $clock = new SystemClock($timezone);

        $this->assertEquals($timezone, $clock->timeZone());

        $this->assertEquals($timezone, $clock->dateTime()->getTimezone());

        $this->assertEquals($timezone, $clock->pointInTime()->dateTime()->getTimezone());
    }

    public function provideTimeZone(): Generator
    {
        yield [null];
        yield [new DateTimeZone('UTC')];
        yield [new DateTimeZone('Europe/Paris')];
    }
}
