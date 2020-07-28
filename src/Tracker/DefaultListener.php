<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tracker;

use Plexikon\Chronicle\Exception\Assertion;
use Plexikon\Chronicle\Support\Contract\Tracker\Listener;

final class DefaultListener implements Listener
{
    /**
     * @var callable
     */
    private $context;
    private string $eventName;
    private int $priority;

    public function __construct(string $eventName, callable $context, int $priority)
    {
        $this->eventName = $eventName;
        $this->context = $context;
        $this->priority = $priority;
    }

    public function eventName(): string
    {
        return $this->eventName;
    }

    public function context(): callable
    {
        return $this->context;
    }

    public function priority(): int
    {
        return $this->priority;
    }
}
