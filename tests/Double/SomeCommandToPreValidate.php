<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Double;

use Plexikon\Chronicle\Support\Contract\Messaging\PreValidateMessage;

final class SomeCommandToPreValidate extends SomeCommandToValidate implements PreValidateMessage
{
    //
}
