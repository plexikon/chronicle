<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Double;

use React\Promise\Deferred;

final class SomeQueryHandler
{
    public function __invoke(SomeQuery $query, Deferred $promise)
    {
        $promise->resolve($query->toPayload());
    }
}
