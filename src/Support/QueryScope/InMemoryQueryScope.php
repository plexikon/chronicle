<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Support\QueryScope;

use Plexikon\Chronicle\Exception\Assertion;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Chronicling\QueryFilter;
use Plexikon\Chronicle\Support\Contract\Chronicling\QueryScope;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;

class InMemoryQueryScope implements QueryScope
{
    public function matchAggregateIdAndTypeGreaterThanVersion(string $aggregateId,
                                                              string $aggregateType,
                                                              int $aggregateVersion): QueryFilter
    {
        Assertion::greaterThan($aggregateVersion, 0, 'Aggregate version must be greater than zero');

        $callback = function (Message $message, int $key) use ($aggregateId, $aggregateType, $aggregateVersion): ?Message {
            $currentAggregateId = (string)$message->header(MessageHeader::AGGREGATE_ID);

            if ($currentAggregateId !== $aggregateId) {
                return null;
            }

            if ((string)$message->header(MessageHeader::AGGREGATE_TYPE) !== $aggregateType) {
                return null;
            }

            return (($key + 1) > $aggregateVersion) ? $message : null;
        };

        return $this->wrap($callback);
    }

    public function fromToPosition(int $from, int $to, ?string $direction): QueryFilter
    {
        Assertion::greaterOrEqualThan($from, 0, 'From position must be greater or equal than 0');
        Assertion::greaterThan($to, $from, 'To position must be greater than from position');

        $callback = fn(Message $message, int $key): ?Message => (($key + 1) >= $from && ($key + 1) <= $to) ? $message : null;

        return $this->wrap($callback);
    }

    public function fromIncludedPosition(int $position): QueryFilter
    {
        Assertion::greaterThan($position, 0, 'Position must be greater than 0');

        $callback = fn(Message $message, int $key): ?Message => (($key + 1) >= $position) ? $message : null;

        return $this->wrap($callback);
    }

    public function wrap(callable $query): QueryFilter
    {
        return new class($query) implements QueryFilter {
            /**
             * @var callable
             */
            private $query;

            public function __construct($query)
            {
                $this->query = $query;
            }

            public function filterQuery(): callable
            {
                return $this->query;
            }
        };
    }
}
