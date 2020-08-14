<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Double;

use Plexikon\Chronicle\Chronicling\Aggregate\AggregateChanged;

final class SomeAggregateChanged extends AggregateChanged
{
    public static function withData(string $id, array $data): self
    {
        return self::occur($id, $data);
    }
}
