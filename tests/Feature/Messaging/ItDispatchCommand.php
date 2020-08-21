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
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Tests\Double\SomeCommand;
use Plexikon\Chronicle\Tests\Factory\MergeWithReporterConfig;
use Plexikon\Chronicle\Tests\Factory\RegisterDefaultReporterTrait;
use Plexikon\Chronicle\Tests\Feature\ITestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class ItDispatchCommand extends ITestCase
{
    use MergeWithReporterConfig, RegisterDefaultReporterTrait;

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
        $context = function (MessageContext $context): void {
            $message = $context->getMessage();

            $this->assertEquals(ReportCommand::class, $message->header(MessageHeader::MESSAGE_BUS_TYPE));
            $this->assertInstanceOf(UuidInterface::class, $message->header(MessageHeader::EVENT_ID));
            $this->assertInstanceOf(PointInTime::class, $message->header(MessageHeader::TIME_OF_RECORDING));
            $this->assertEquals(
                $this->messageAlias->classToType(SomeCommand::class),
                $message->header(MessageHeader::EVENT_TYPE)
            );
        };

        $this->setUpReporterWithContext($context);

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

        $context = function (MessageContext $context) use ($message): void {
            $contextMessage = $context->getMessage();

            $this->assertEquals(ReportCommand::class, $contextMessage->header(MessageHeader::MESSAGE_BUS_TYPE));
            $this->assertEquals($message->header(MessageHeader::EVENT_ID), $contextMessage->header(MessageHeader::EVENT_ID));
            $this->assertEquals($message->header(MessageHeader::EVENT_TYPE), $contextMessage->header(MessageHeader::EVENT_TYPE));
            $this->assertEquals($message->header(MessageHeader::TIME_OF_RECORDING), $contextMessage->header(MessageHeader::TIME_OF_RECORDING));
        };

        $this->setUpReporterWithContext($context);

        $this->publishCommand($message);

        $this->assertTrue($this->commandHandled);
    }

    protected function setUpReporterWithContext(callable $context): void
    {
        $factory = $this->messageSubscriberFactory($this->messageAlias);

        $sub = $factory->onDispatch($context, Reporter::PRIORITY_MESSAGE_DECORATOR - 1);
        $factory->addSubscribers($sub);

        $subscribers = array_merge($factory->messageSubscribers(), [$sub]);
        $this->mergeConfigSubscribers('command', ...$subscribers);
    }
}
