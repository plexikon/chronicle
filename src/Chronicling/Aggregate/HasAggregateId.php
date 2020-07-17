<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Chronicling\Aggregate;

use Exception;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateId;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

trait HasAggregateId
{
    private UuidInterface $identifier;

    protected function __construct(UuidInterface $identifier)
    {
        $this->identifier = $identifier;
    }

    public function toString(): string
    {
        return $this->identifier->toString();
    }

    /**
     * Should be overridden in class
     *
     * @param AggregateId $rootId
     * @return bool
     */
    public function equalsTo(AggregateId $rootId): bool
    {
        return static::class === get_class($rootId)
            && $this->toString() === $rootId->toString();
    }

    /**
     * @return static|AggregateId
     * @throws Exception
     */
    public static function create(): self
    {
        return new static(Uuid::uuid4());
    }

    /**
     * @param string $aggregateId
     * @return static|AggregateId
     */
    public static function fromString(string $aggregateId): AggregateId
    {
        return new static(Uuid::fromString($aggregateId));
    }
}
