<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Reporter;

use Plexikon\Chronicle\Support\Contract\Reporter\NamingReporter;
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Support\Contract\Reporter\TrackingReporter;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageSubscriber;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageTracker;
use Plexikon\Chronicle\Tracker\TrackingMessage;

abstract class ReportMessage implements Reporter, TrackingReporter, NamingReporter
{
    private ?string $reporterName;
    protected MessageTracker $tracker;

    public function __construct(?string $reporterName, ?MessageTracker $tracker = null)
    {
        $this->reporterName = $reporterName;
        $this->tracker = $tracker ?? new TrackingMessage();
    }

    public function subscribe(MessageSubscriber $messageSubscriber): void
    {
        $messageSubscriber->attachToTracker($this->tracker);
    }

    public function reporterName(): string
    {
        return $this->reporterName ?? $this->reporterName = get_called_class();
    }

    protected function publishMessage(MessageContext $messageContext)
    {
        $this->tracker->fire($messageContext);
    }

    protected function finalizeDispatching(MessageContext $messageContext): void
    {
        $messageContext->withEvent(self::FINALIZE_EVENT);

        $this->tracker->fire($messageContext);
    }
}
