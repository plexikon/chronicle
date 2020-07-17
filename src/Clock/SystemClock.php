<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Clock;

use DateTimeImmutable;
use DateTimeZone;
use Plexikon\Chronicle\Support\Contract\Clock;

final class SystemClock implements Clock
{
    private DateTimeZone $timeZone;

    public function __construct(DateTimeZone $timeZone = null)
    {
        $this->timeZone = $timeZone ?? new DateTimeZone('UTC');
    }

    public function dateTime(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', $this->timeZone);
    }

    public function pointInTime(): PointInTime
    {
        return PointInTime::fromDateTime($this->dateTime());
    }

    public function timeZone(): DateTimeZone
    {
        return $this->timeZone;
    }
}
