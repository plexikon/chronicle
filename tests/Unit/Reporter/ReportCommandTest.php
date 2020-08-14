<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Reporter;

use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Reporter\ReportCommand;
use Plexikon\Chronicle\Reporter\ReportEvent;
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Tests\Double\SomeEvent;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use Plexikon\Chronicle\Tracker\TrackingMessage;
use RuntimeException;

final class ReportCommandTest extends TestCase
{
    /**
     * @test
     */
    public function it_publish_event(): void
    {
        $message = new Message(SomeEvent::fromPayload(['foo' => 'bar']));

        $tracker = new TrackingMessage();
        $reporter = new ReportCommand(null, $tracker);

        $tracker->listen($reporter::DISPATCH_EVENT, function (MessageContext $context) use ($message): void {
            $this->assertEquals(Reporter::DISPATCH_EVENT, $context->getCurrentEvent());
            $this->assertEquals($message, $context->getMessage());
        });

        $tracker->listen($reporter::FINALIZE_EVENT, function (MessageContext $context) use ($message): void {
            $this->assertEquals(Reporter::FINALIZE_EVENT, $context->getCurrentEvent());
            $this->assertEquals($message, $context->getMessage());
            $this->assertFalse($context->hasException());
        });

        $reporter->publish($message);
    }

    /**
     * @@test
     */
    public function it_set_exception_caught_during_dispatching_on_context(): void
    {
        $message = new Message(SomeEvent::fromPayload(['foo' => 'bar']));

        $tracker = new TrackingMessage();
        $reporter = new ReportCommand(null, $tracker);

        $tracker->listen($reporter::DISPATCH_EVENT, function (MessageContext $context) use ($message): void {
            throw new RuntimeException('foo');
        });

        $tracker->listen($reporter::FINALIZE_EVENT, function (MessageContext $context) use ($message): void {
            $this->assertEquals(Reporter::FINALIZE_EVENT, $context->getCurrentEvent());
            $this->assertEquals($message, $context->getMessage());
            $this->assertTrue($context->hasException());
            $this->assertInstanceOf(RuntimeException::class, $context->getException());
            $this->assertEquals('foo', $context->getException()->getMessage());
        });

        $reporter->publish($message);
    }
}
