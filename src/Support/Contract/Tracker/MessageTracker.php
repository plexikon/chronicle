<?php

namespace Plexikon\Chronicle\Support\Contract\Tracker;

interface MessageTracker extends Tracker
{
    /**
     * @param string $eventName
     * @return MessageContext
     */
    public function newContext(string $eventName): MessageContext;
}
