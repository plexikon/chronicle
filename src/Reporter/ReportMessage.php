<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Reporter;

use Plexikon\Chronicle\Support\Contract\Reporter\NamingReporter;
use Plexikon\Chronicle\Support\Contract\Reporter\TrackingReporter;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageSubscriber;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageTracker;
use Plexikon\Chronicle\Tracker\TrackingMessage;
use Throwable;

abstract class ReportMessage implements TrackingReporter, NamingReporter
{
    private ?string $name;
    protected MessageTracker $tracker;

    public function __construct(?string $name, ?MessageTracker $tracker = null)
    {
        $this->name = $name;
        $this->tracker = $tracker ?? new TrackingMessage();
    }

    public function subscribe(MessageSubscriber $subscriber): void
    {
        $subscriber->attachToTracker($this->tracker);
    }

    public function reporterName(): string
    {
        return $this->name ?? $this->name = get_called_class();
    }

    protected function publishMessage(MessageContext $context): void
    {
        try {
            $this->tracker->fire($context);
        } catch (Throwable $exception) {
            $context->withRaisedException($exception);
        } finally {
            $this->finalizeDispatching($context);
        }
    }

    protected function finalizeDispatching(MessageContext $context): void
    {
        $context->withEvent(self::FINALIZE_EVENT);

        $this->tracker->fire($context);
    }
}
