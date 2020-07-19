<?php

namespace Plexikon\Chronicle\Support\Contract\Tracker;

interface MessageSubscriber extends Subscriber
{
    /**
     * @param MessageTracker $tracker
     */
    public function attachToTracker(MessageTracker $tracker): void;
}
