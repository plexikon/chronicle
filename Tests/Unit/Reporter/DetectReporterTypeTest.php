<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Reporter;

use Plexikon\Chronicle\Exception\RuntimeException;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Reporter\DetectReporterType;
use Plexikon\Chronicle\Reporter\DomainMessage;
use Plexikon\Chronicle\Reporter\ReportCommand;
use Plexikon\Chronicle\Reporter\ReportEvent;
use Plexikon\Chronicle\Reporter\ReportQuery;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Tests\Double\SomeCommand;
use Plexikon\Chronicle\Tests\Double\SomeEvent;
use Plexikon\Chronicle\Tests\Double\SomeQuery;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use stdClass;

final class DetectReporterTypeTest extends TestCase
{
    /**
     * @test
     */
    public function it_detect_bus_from_message_header(): void
    {
        $message = new Message(SomeCommand::fromPayload([]), [
            MessageHeader::MESSAGE_BUS_TYPE => 'foo'
        ]);

        $detector = $this->detectReporterTypeInstance();
        $busType = $detector->fromMessage($message);

        $this->assertEquals('foo', $busType);
    }

    /**
     * @test
     * @dataProvider provideDefaultMessage
     * @param string $event
     * @param string $reporterClass
     */
    public function it_fallback_and_detect_bus_from_event_type(string $event, string $reporterClass)
    {
        /** @var DomainMessage $event */
        $message = new Message($event::fromPayload([]), [
            MessageHeader::MESSAGE_BUS_TYPE => null
        ]);

        $detector = $this->detectReporterTypeInstance();
        $busType = $detector->fromMessage($message);

        $this->assertEquals($reporterClass, $busType);
    }

    /**
     * @test
     */
    public function it_raise_exception_with_unsupported_event(): void
    {
        $this->expectException(RuntimeException::class);

        $this->detectReporterTypeInstance()->fromMessage(new Message(new stdClass()));
    }

    public function detectReporterTypeInstance(): object
    {
        return new class() {
            use DetectReporterType;

            public function fromMessage(Message $message): string
            {
                return $this->detectBusTypeFromMessage($message);
            }
        };
    }

    public function provideDefaultMessage(): \Generator
    {
        yield[SomeCommand::class, ReportCommand::class];
        yield[SomeEvent::class, ReportEvent::class];
        yield[SomeQuery::class, ReportQuery::class];
    }
}
