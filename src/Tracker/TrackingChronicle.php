<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tracker;

use Plexikon\Chronicle\Support\Contract\Tracker\EventContext;
use Plexikon\Chronicle\Support\Contract\Tracker\EventTracker;
use Plexikon\Chronicle\Tracker\Concerns\HasTracking;

class TrackingChronicle implements EventTracker
{
    use HasTracking;

    public function newContext(string $eventName): EventContext
    {
        return new DefaultEventContext($eventName);
    }
}
