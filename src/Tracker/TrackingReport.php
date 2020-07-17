<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tracker;

use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Support\Contract\Tracker\Tracker;
use Plexikon\Chronicle\Tracker\Concerns\HasTracking;

final class TrackingReport implements Tracker
{
    use HasTracking;

    public function newContext(string $eventName): MessageContext
    {
        return new DefaultMessageContext($eventName);
    }
}
