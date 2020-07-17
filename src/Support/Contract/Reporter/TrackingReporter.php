<?php

namespace Plexikon\Chronicle\Support\Contract\Reporter;

use Plexikon\Chronicle\Support\Contract\Tracker\MessageSubscriber;

interface TrackingReporter extends Reporter
{
    /**
     * @param MessageSubscriber $messageSubscriber
     */
    public function subscribe(MessageSubscriber $messageSubscriber): void;
}
