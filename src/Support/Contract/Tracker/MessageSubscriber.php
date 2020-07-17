<?php

namespace Plexikon\Chronicle\Support\Contract\Tracker;

interface MessageSubscriber extends Subscriber
{
    /**
     * @param Tracker $tracker
     */
    public function attachToTracker(Tracker $tracker): void;
}
