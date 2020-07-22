<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tracker;

use Plexikon\Chronicle\Support\Contract\Tracker\TransactionalEventContext as BaseTransactionalEventContext;
use Plexikon\Chronicle\Support\Contract\Tracker\TransactionalEventTracker;

final class TransactionalTrackingEvent extends TrackingEvent implements TransactionalEventTracker
{
    public function newContext(string $eventName): BaseTransactionalEventContext
    {
        return new TransactionalEventContext($eventName);
    }
}
