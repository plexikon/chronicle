<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Support\QueryScope;

use Illuminate\Database\Query\Builder;
use Plexikon\Chronicle\Exception\Assertion;
use Plexikon\Chronicle\Support\Contract\Chronicling\QueryScope;

abstract class ConnectionQueryScope implements QueryScope
{
    public function fromIncludedPosition(int $position): callable
    {
        Assertion::greaterThan($position, 0, 'Position must be greater than 0');

        return function (Builder $query) use ($position): void {
            $query
                ->where('no', '>=', $position)
                ->orderBy('no');
        };
    }

    public function fromToPosition(int $from, int $to, ?string $direction): callable
    {
        Assertion::greaterOrEqualThan($from, 0, 'From position must be greater or equal than 0');
        Assertion::greaterThan($to, $from, 'To position must be greater than from position');

        return function (Builder $builder) use ($from, $to, $direction): void {
            $builder->whereBetween('no', [$from, $to]);
            $builder->orderBy('no', $direction ?? 'ASC');
        };
    }
}
