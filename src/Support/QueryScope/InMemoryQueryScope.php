<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Support\QueryScope;

use Plexikon\Chronicle\Exception\Assertion;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Chronicling\QueryScope;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;

class InMemoryQueryScope implements QueryScope
{
    public function matchAggregateIdAndTypeGreaterThanVersion(string $aggregateId,
                                                              string $aggregateType,
                                                              int $aggregateVersion): callable
    {
        Assertion::greaterThan($aggregateVersion, 0, 'Aggregate version must be greater than zero');

        return function (Message $message, int $key) use ($aggregateId, $aggregateType, $aggregateVersion): ?Message {
            $currentAggregateId = (string)$message->header(MessageHeader::AGGREGATE_ID);

            if ($currentAggregateId !== $aggregateId) {
                return null;
            }

            $type = (string)$message->header(MessageHeader::AGGREGATE_TYPE);

            if ($type !== $aggregateType) {
                return null;
            }

            if (($key + 1) > $aggregateVersion) {
                return $message;
            }

            return null;
        };
    }

    public function fromToPosition(int $from, int $to, ?string $direction): callable
    {
        Assertion::greaterOrEqualThan($from, 0, 'From position must be greater or equal than 0');
        Assertion::greaterThan($to, $from, 'To position must be greater than from position');

        return function (Message $message, int $key) use ($from, $to): ?Message {

            if (($key + 1) >= $from && ($key + 1) <= $to) {
                return $message;
            }

            return null;
        };
    }

    public function fromIncludedPosition(int $position): callable
    {
        Assertion::greaterThan($position, 0, 'Position must be greater than 0');

        return function (Message $message, int $key) use ($position): ?Message {

            if (($key + 1) >= $position) {
                return $message;
            }

            return null;
        };
    }
}
