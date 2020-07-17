<?php

namespace Plexikon\Chronicle\Support\Contract\Chronicling;

use Plexikon\Chronicle\Support\Contract\Tracker\Listener;

interface EventChronicler extends ChroniclerDecorator
{
    /**
     * @param string $eventName
     * @param callable $eventContext
     * @param int $priority
     * @return Listener
     */
    public function subscribe(string $eventName, callable $eventContext, int $priority = 0): Listener;

    /**
     * @param Listener ...$eventSubscribers
     */
    public function unsubscribe(Listener ...$eventSubscribers): void;
}
