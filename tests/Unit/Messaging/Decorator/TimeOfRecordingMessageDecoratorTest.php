<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Messaging\Decorator;

use Plexikon\Chronicle\Clock\PointInTime;
use Plexikon\Chronicle\Clock\SystemClock;
use Plexikon\Chronicle\Messaging\Decorator\TimeOfRecordingMessageDecorator;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Clock;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Tests\Double\SomeCommand;
use Plexikon\Chronicle\Tests\Unit\TestCase;

final class TimeOfRecordingMessageDecoratorTest extends TestCase
{
    /**
     * @test
     */
    public function it_decorate_message_with_recorded_time_header(): void
    {
        $command = SomeCommand::withData(['foo' => 'bar']);
        $message = new Message($command);
        $this->assertNull($message->header(MessageHeader::TIME_OF_RECORDING));

        $pointInTime = $this->somePointTime();
        $clock = $this->prophesize(Clock::class);
        $clock->pointInTime()->willReturn($pointInTime)->shouldBeCalled();

        $decorator = new TimeOfRecordingMessageDecorator($clock->reveal());
        $decoratedMessage = $decorator->decorate($message);

        $this->assertEquals($pointInTime, $decoratedMessage->header(MessageHeader::TIME_OF_RECORDING));
    }

    /**
     * @test
     */
    public function it_does_not_override_time_of_recording_header_if_already_exists(): void
    {
        $command = SomeCommand::withData(['foo' => 'bar']);
        $message = new Message($command);

        $pointInTime = $this->somePointTime();
        $decoratedMessage = $message->withHeader(MessageHeader::TIME_OF_RECORDING, $pointInTime);

        $clock = $this->prophesize(Clock::class);
        $clock->pointInTime()->shouldNotBeCalled();

        $decorator = new TimeOfRecordingMessageDecorator($clock->reveal());
        $decoratedMessage = $decorator->decorate($decoratedMessage);

        $this->assertEquals($pointInTime, $decoratedMessage->header(MessageHeader::TIME_OF_RECORDING));
    }

    private function somePointTime(): PointInTime
    {
        return (new SystemClock())->pointInTime();
    }
}
