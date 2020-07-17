<?php

namespace Plexikon\Chronicle\Support\Contract;

use DateTimeImmutable;
use DateTimeZone;
use Plexikon\Chronicle\Clock\PointInTime;

interface Clock
{
    /**
     * @return DateTimeImmutable
     */
    public function dateTime(): DateTimeImmutable;

    /**
     * @return PointInTime
     */
    public function pointInTime(): PointInTime;

    /**
     * @return DateTimeZone
     */
    public function timeZone(): DateTimeZone;
}
