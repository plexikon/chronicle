<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Chronicling;

use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Reporter\ReportEvent;
use Plexikon\Chronicle\Support\Contract\Chronicling\EventDispatcher as BaseEventDispatcher;

final class DefaultEventDispatcher implements BaseEventDispatcher
{
    private ReportEvent $reporter;

    public function __construct(ReportEvent $reporter)
    {
        $this->reporter = $reporter;
    }

    public function dispatch(Message ...$messages): void
    {
        foreach ($messages as $message) {
            $this->reporter->publish($message);
        }
    }
}
