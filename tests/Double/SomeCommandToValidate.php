<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Double;

use Plexikon\Chronicle\Reporter\Command;
use Plexikon\Chronicle\Support\Contract\Messaging\ValidateMessage;

class SomeCommandToValidate extends Command implements ValidateMessage
{
    public function validationRules(): array
    {
        return ['foo' => 'bar'];
    }
}
