<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Clock;

use Assert\AssertionFailedException;
use DateInterval;
use DateTimeImmutable;
use Plexikon\Chronicle\Exception\Assertion;

final class PointInTime
{
    /**
     * @private
     */
    const DATE_TIME_FORMAT = 'Y-m-d\TH:i:s.u';

    private DateTimeImmutable $pointInTime;

    /**
     * PointInTime constructor.
     * @param $dateTime
     * @throws AssertionFailedException
     */
    private function __construct($dateTime)
    {
        Assertion::isInstanceOf($dateTime, DateTimeImmutable::class, 'Invalid date time');

        $this->pointInTime = $dateTime;
    }

    public function equals(self $pointInTime): bool
    {
        return $this->toString() === $pointInTime->toString();
    }

    public function after(self $pointInTime): bool
    {
        return $this->pointInTime > $pointInTime->dateTime();
    }

    public function add(string $interval): self
    {
        $datetime = $this->pointInTime->add(new DateInterval($interval));

        return new self($datetime);
    }

    public function sub(string $interval): self
    {
        $datetime = $this->pointInTime->sub(new DateInterval($interval));

        return new self($datetime);
    }

    public function diff(self $pointInTime): DateInterval
    {
        return $this->pointInTime->diff($pointInTime->dateTime());
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
