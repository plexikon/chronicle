<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Double;

use Plexikon\Chronicle\Reporter\Command;
use Plexikon\Chronicle\Support\Contract\Messaging\AsyncMessage;

final class SomeAsyncCommand extends Command implements AsyncMessage
{
    //
}
