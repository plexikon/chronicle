<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tracker\Concerns;

use Illuminate\Support\Collection;
use Plexikon\Chronicle\Support\Contract\Tracker\Context;
use Plexikon\Chronicle\Support\Contract\Tracker\Listener;
use Plexikon\Chronicle\Tracker\DefaultListener;

trait HasTracking
{
    private Collection $listeners;

    public function __construct()
    {
        $this->listeners = new Collection();
    }

    public function listen(string $eventName, callable $eventContext, int $priority = 0): Listener
    {
        $eventSubscriber = new DefaultListener($eventName, $eventContext, $priority);

        $this->listeners->push($eventSubscriber);

        return $eventSubscriber;
    }

    public function fire(Context $contextEvent): void
    {
        $this->fireEvent($contextEvent, null);
    }

    public function fireUntil(Context $contextEvent, callable $callback): void
    {
        $this->fireEvent($contextEvent, $callback);
    }

    public function forget(Listener $eventSubscriber): void
    {
        $this->listeners = $this->listeners->reject(
            fn(Listener $subscriber): bool => $eventSubscriber === $subscriber
        );
    }

    private function fireEvent(Context $eventContext, ?callable $callback): void
    {
        $currentEvent = $eventContext->getCurrentEvent();

        $this->listeners
            ->filter(fn(Listener $subscriber) => $currentEvent === $subscriber->eventName())
            ->sortByDesc(fn(Listener $subscriber): int => $subscriber->priority(), SORT_NUMERIC)
            ->each(function (Listener $subscriber) use ($eventContext, $callback) {
                $subscriber->context()($eventContext);

                if ($eventContext->isPropagationStopped()) {
                    return false;
                }

                if ($callback && true === $callback($eventContext)) {
                    return false;
                }
            });
    }
}
