<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Double;

use Plexikon\Chronicle\Reporter\DomainMessage;

final class SomeDomainMessage extends DomainMessage
{
    public function messageType(): string
    {
        return 'foo';
    }
}
