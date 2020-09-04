<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Feature\Messaging;

use Generator;
use Plexikon\Chronicle\Clock\SystemClock;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Providers\ReporterServiceProvider;
use Plexikon\Chronicle\Reporter\Command;
use Plexikon\Chronicle\Reporter\ReportCommand;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Tests\Double\SomeCommand;
use Plexikon\Chronicle\Tests\Factory\RegisterDefaultReporterTrait;
use Plexikon\Chronicle\Tests\Feature\ITestCase;
use Ramsey\Uuid\Uuid;

final class ItDispatchCommand extends ITestCase
{
    use RegisterDefaultReporterTrait;

    private bool $commandHandled;
    private array $payload = ['foo' => 'bar'];
    private ?MessageAlias $messageAlias;
    private ?ReportCommand $reportCommand;

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandHandled = false;

        $this->messageAlias = $this->app[MessageAlias::class];

        $this->registerReporters();

        $this->reportCommand = $this->app[ReportCommand::class];
    }

    /**
     * @test
     * @dataProvider provideMessage
     * @param Command|Message $message
     */
    public function it_dispatch_message($message): void
    {
        $factory = $this->messageSubscriberFactory($this->messageAlias);

        $factory
            ->withDefaultMessageHeaderAssertion($this, ReportCommand::class, SomeCommand::class)
            ->withMessagePayloadAssertion($this, $this->payload)
            ->withMessageHandledAssertion($this);

        $factory->subscribeToReporter($this->reportCommand);

        $this->reportCommand->publish($message);

        $this->assertTrue($this->commandHandled);
    }

    /**
     * @test
     */
    public function it_dispatch_command_with_defined_headers(): void
    {
        $factory = $this->messageSubscriberFactory($this->messageAlias);
        $factory
            ->withMessagePayloadAssertion($this, $this->payload)
            ->withMessageHandledAssertion($this);

        $command = SomeCommand::withData($this->payload);

        $message = new Message($command, [
            MessageHeader::EVENT_ID => $eventId = Uuid::uuid4(),
            MessageHeader::EVENT_TYPE => $eventType = 'some.namespace.some.command',
            MessageHeader::TIME_OF_RECORDING => $timeOfRecording = (new SystemClock())->pointInTime(),
        ]);

        $context = function (MessageContext $context) use ($message): void {
            $contextMessage = $context->getMessage();

            $this->assertEquals(ReportCommand::class, $contextMessage->header(MessageHeader::MESSAGE_BUS_NAME));
            $this->assertEquals($message->header(MessageHeader::EVENT_ID), $contextMessage->header(MessageHeader::EVENT_ID));
            $this->assertEquals($message->header(MessageHeader::EVENT_TYPE), $contextMessage->header(MessageHeader::EVENT_TYPE));
            $this->assertEquals($message->header(MessageHeader::TIME_OF_RECORDING), $contextMessage->header(MessageHeader::TIME_OF_RECORDING));
        };

        $factory->addSubscribers(
            $factory->onEvent(Reporter::DISPATCH_EVENT, $context, Reporter::PRIORITY_MESSAGE_DECORATOR - 1)
        );

        $factory->subscribeToReporter($this->reportCommand);

        $this->reportCommand->publish($message);

        $this->assertTrue($this->commandHandled);
    }

    /**
     * @test
     */
    public function it_dispatch_command_as_array(): void
    {
        $factory = $this->messageSubscriberFactory($this->messageAlias);
        $factory
            ->withMessagePayloadAssertion($this, $this->payload)
            ->withMessageHandledAssertion($this);

        $command = [
            'headers' => [
                MessageHeader::EVENT_ID => $eventId = Uuid::uuid4(),
                MessageHeader::EVENT_TYPE => $eventType = $this->messageAlias->classToType(SomeCommand::class),
                MessageHeader::TIME_OF_RECORDING => $timeOfRecording = (new SystemClock())->pointInTime(),
            ],
            'payload' => $this->payload
        ];

        $commandHeaders = $command['headers'];
        $commandPayload = $command['payload'];

        $context = function (MessageContext $context) use ($commandHeaders, $commandPayload): void {
            $contextMessage = $context->getMessage();

            $this->assertInstanceOf(Message::class, $contextMessage);
            $this->assertEquals(ReportCommand::class, $contextMessage->header(MessageHeader::MESSAGE_BUS_NAME));
            $this->assertEquals($commandHeaders[MessageHeader::EVENT_ID], $contextMessage->header(MessageHeader::EVENT_ID));
            $this->assertEquals($commandHeaders[MessageHeader::EVENT_TYPE], $contextMessage->header(MessageHeader::EVENT_TYPE));
            $this->assertEquals($commandHeaders[MessageHeader::TIME_OF_RECORDING], $contextMessage->header(MessageHeader::TIME_OF_RECORDING));
            $this->assertEquals($commandPayload, $contextMessage->event()->toPayload());
        };

        $factory->addSubscribers(
            $factory->onEvent(Reporter::DISPATCH_EVENT, $context, Reporter::PRIORITY_MESSAGE_DECORATOR - 1)
        );

        $factory->subscribeToReporter($this->reportCommand);

        $this->reportCommand->publish($command);

        $this->assertTrue($this->commandHandled);
    }

    public function provideMessage(): Generator
    {
        $command = SomeCommand::withData($this->payload);

        yield [$command];

        yield [new Message($command)];
    }
}
