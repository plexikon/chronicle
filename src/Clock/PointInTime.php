<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Clock;

use DateInterval;
use DateTimeImmutable;
use Plexikon\Chronicle\Exception\Assertion;

final class PointInTime
{
    /**
     * @private
     */
    const DATE_TIME_FORMAT = 'Y-m-d\TH:i:s.u';

    private DateTimeImmutable $dateTime;

    private function __construct($dateTime)
    {
        Assertion::isInstanceOf($dateTime, DateTimeImmutable::class, 'Invalid date time');

        $this->dateTime = $dateTime;
    }

    public function equals(self $pointInTime): bool
    {
        return $this->toString() === $pointInTime->toString();
    }

    public function after(self $pointInTime): bool
    {
        return $this->dateTime > $pointInTime->dateTime();
    }

    public function add(string $interval): self
    {
        $datetime = $this->dateTime->add(new DateInterval($interval));

        return new self($datetime);
    }

    public function sub(string $interval): self
    {
        $datetime = $this->dateTime->sub(new DateInterval($interval));

        return new self($datetime);
    }

    public function diff(self $pointInTime): DateInterval
    {
        return $this->dateTime->diff($pointInTime->dateTime());
    }

    public function dateTime(): DateTimeImmutable
    {
        return $this->dateTime;
    }

    public function __toString(): string
    {
        return $this->dateTime->format(self::DATE_TIME_FORMAT);
    }

    public function toString(): string
    {
        return $this->dateTime->format(self::DATE_TIME_FORMAT);
    }

    public static function fromString(string $pointInTime): self
    {
        $dateTime = DateTimeImmutable::createFromFormat(self::DATE_TIME_FORMAT, $pointInTime);

        return new self($dateTime);
    }

    public static function fromDateTime(DateTimeImmutable $dateTime): PointInTime
    {
        return new self($dateTime);
    }
}
