<?php

namespace Plexikon\Chronicle\Support\Contract\Tracker;

interface EventTracker extends Tracker
{
    /**
     * @param string $eventName
     * @return EventContext|TransactionalEventContext
     */
    public function newContext(string $eventName): EventContext;
}
