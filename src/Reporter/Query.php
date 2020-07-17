<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Reporter;

abstract class Query extends DomainMessage
{
    public function messageType(): string
    {
        return self::QUERY;
    }
}
