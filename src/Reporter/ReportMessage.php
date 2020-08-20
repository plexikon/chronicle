<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Reporter;

use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Support\Contract\Reporter\NamingReporter;
use Plexikon\Chronicle\Support\Contract\Reporter\TrackingReporter;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageSubscriber;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageTracker;
use Plexikon\Chronicle\Tracker\TrackingMessage;
use Throwable;

abstract class ReportMessage implements TrackingReporter, NamingReporter
{
    private ?string $reporterName;
    protected MessageTracker $tracker;

    public function __construct(?string $reporterName, ?MessageTracker $tracker = null)
    {
        $this->reporterName = $reporterName;
        $this->tracker = $tracker ?? new TrackingMessage();
    }

    public function subscribe(MessageSubscriber $subscriber): void
    {
        $subscriber->attachToTracker($this->tracker);
    }

    public function reporterName(): string
    {
        return $this->reporterName ?? $this->reporterName = get_called_class();
    }

    protected function publishMessage(MessageContext $context): void
    {
        $context->withMessage(
            $this->addMessageBusTypeHeader($context->getMessage())
        );

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

    private function addMessageBusTypeHeader($message)
    {
        if (is_array($message)) {
            return $message;
        }

        if (!$message instanceof Message) {
            $message = new Message($message);
        }

        if (null !== $message->header(MessageHeader::MESSAGE_BUS_TYPE)) {
            return $message;
        }

        return $message->withHeader(MessageHeader::MESSAGE_BUS_TYPE, $this->reporterName());
    }
}
