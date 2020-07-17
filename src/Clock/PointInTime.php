<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Clock;

use Assert\AssertionFailedException;
use DateTimeImmutable;
use Plexikon\Chronicle\Exception\Assertion;

final class PointInTime
{
    /**
     * @private
     */
    const DATE_TIME_FORMAT = 'Y-m-d\TH:i:s.u';

    private DateTimeImmutable $pointInTime;

    private function __construct(DateTimeImmutable $pointInTime)
    {
        $this->pointInTime = $pointInTime;
    }

    public function dateTime(): DateTimeImmutable
    {
        return $this->pointInTime;
    }

    public function __toString(): string
    {
        return $this->pointInTime->format(self::DATE_TIME_FORMAT);
    }

    public function toString(): string
    {
        return $this->pointInTime->format(self::DATE_TIME_FORMAT);
    }

    /**
     * @param string $pointInTime
     * @return static
     * @throws AssertionFailedException
     */
    public static function fromString(string $pointInTime): self
    {
        $dateTime = DateTimeImmutable::createFromFormat(self::DATE_TIME_FORMAT, $pointInTime);

        Assertion::isInstanceOf($dateTime, DateTimeImmutable::class, 'Invalid date time');

        return new PointInTime($dateTime);
    }

    public static function fromDateTime(DateTimeImmutable $dateTime): PointInTime
    {
        return new PointInTime($dateTime);
    }
}
