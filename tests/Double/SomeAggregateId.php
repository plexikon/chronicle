<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Double;

use Plexikon\Chronicle\Chronicling\Aggregate\Concerns\HasUuidAggregateId;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateId;

final class SomeAggregateId implements AggregateId
{
    use HasUuidAggregateId;
}
