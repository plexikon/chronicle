<?php

namespace Plexikon\Chronicle\Support\Contract\Tracker;

interface Tracker
{
    /**
     * @param string $eventName
     * @return Context|MessageContext|EventContext|TransactionalEventContext
     */
    public function newContext(string $eventName): Context;

    /**
     * @param string $eventName
     * @param callable $eventContext
     * @param int $priority
     * @return Listener
     */
    public function listen(string $eventName, callable $eventContext, int $priority = 0): Listener;

    /**
     * @param Context $contextEvent
     */
    public function fire(Context $contextEvent): void;

    /**
     * @param Context $contextEvent
     * @param callable $callback
     */
    public function fireUntil(Context $contextEvent, callable $callback): void;

    /**
     * @param Listener $eventSubscriber
     */
    public function forget(Listener $eventSubscriber): void;
}
