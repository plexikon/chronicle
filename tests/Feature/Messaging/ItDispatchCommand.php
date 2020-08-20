<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Feature\Messaging;

use Plexikon\Chronicle\Clock\PointInTime;
use Plexikon\Chronicle\Clock\SystemClock;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Providers\ReporterServiceProvider;
use Plexikon\Chronicle\Reporter\ReportCommand;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Tests\Double\SomeCommand;
use Plexikon\Chronicle\Tests\Feature\ITestCase;
use Plexikon\Chronicle\Tests\Feature\Util\RegisterDefaultReporterTrait;
use Plexikon\Chronicle\Tests\Feature\Util\SpyOnDispatchEventReporterTrait;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class ItDispatchCommand extends ITestCase
{
    use RegisterDefaultReporterTrait, SpyOnDispatchEventReporterTrait;

    private bool $commandHandled = false;
    private ?MessageAlias $messageAlias;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerReporters();
        $this->messageAlias = $this->app[MessageAlias::class];
    }

    protected function getPackageProviders($app)
    {
        return [ReporterServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('reporter.reporting.command.default.map', [
            'some-command' => function () {
                $this->commandHandled = true;
            }
        ]);
    }

    /**
     * @test
     */
    public function it_dispatch_command(): void
    {
        $this->spyAfterMessageDecorator(function (MessageContext $context): void {
            $message = $context->getMessage();

            $this->assertEquals(ReportCommand::class, $message->header(MessageHeader::MESSAGE_BUS_TYPE));
            $this->assertInstanceOf(UuidInterface::class, $message->header(MessageHeader::EVENT_ID));
            $this->assertInstanceOf(PointInTime::class, $message->header(MessageHeader::TIME_OF_RECORDING));
            $this->assertEquals(
                $this->messageAlias->classToType(SomeCommand::class),
                $message->header(MessageHeader::EVENT_TYPE)
            );

        }, 'reporter.reporting.command.default.messaging.subscribers');

        $this->publishCommand(SomeCommand::withData(['foo' => 'bar']));

        $this->assertTrue($this->commandHandled);
    }

    /**
     * @test
     */
    public function it_dispatch_command_with_default_headers(): void
    {
        $command = SomeCommand::withData(['foo' => 'bar']);

        $message = new Message($command, [
            MessageHeader::EVENT_ID => $eventId = Uuid::uuid4(),
            MessageHeader::EVENT_TYPE => $eventType = 'some.namespace.some.command',
            MessageHeader::TIME_OF_RECORDING => $timeOfRecording = (new SystemClock())->pointInTime(),
        ]);

        $this->spyAfterMessageDecorator(function (MessageContext $context) use ($eventId, $eventType, $timeOfRecording): void {
            $message = $context->getMessage();

            $this->assertEquals(ReportCommand::class, $message->header(MessageHeader::MESSAGE_BUS_TYPE));
            $this->assertEquals($eventId, $message->header(MessageHeader::EVENT_ID));
            $this->assertEquals($eventType, $message->header(MessageHeader::EVENT_TYPE));
            $this->assertEquals($timeOfRecording, $message->header(MessageHeader::TIME_OF_RECORDING));
        }, 'reporter.reporting.command.default.messaging.subscribers');

        $this->publishCommand($message);

        $this->assertTrue($this->commandHandled);
    }
}
