<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Reporter;

use Generator;
use Illuminate\Container\Container;
use Plexikon\Chronicle\Exception\ReporterFailure;
use Plexikon\Chronicle\Messaging\Alias\InflectorMessageAlias;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Reporter\Router\SingleHandlerRouter;
use Plexikon\Chronicle\Reporter\Subscriber\TrackingCommandSubscriber;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageProducer;
use Plexikon\Chronicle\Tests\Factory\ReporterFactory;
use Plexikon\Chronicle\Tests\Double\SomeCommand;
use Plexikon\Chronicle\Tests\Double\SomeCommandHandler;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use Plexikon\Chronicle\Tests\Util\HasMessageSubscribersAssertion;
use Prophecy\Argument;

final class ReportCommandTest extends TestCase
{
    use HasMessageSubscribersAssertion;

    /**
     * @test
     */
    public function it_dispatch_command_message(): void
    {
        $map = [SomeCommand::class => function (SomeCommand $command): void {}];

        ReporterFactory::withRouter($map, null, null)
            ->withSubscribers(
                new TrackingCommandSubscriber(),
                $this->assertMessageHandledSubscriber(),
            )
            ->reportCommand(null)
            ->dispatch($this->defaultMessageCommand());
    }

    /**
     * @test
     */
    public function it_dispatch_async_command_message(): void
    {
        $map = [SomeCommand::class => function (SomeCommand $command): void {}];

        $message = $this->defaultMessageCommand();

        $messageProducer = $this->prophesize(MessageProducer::class);
        $messageProducer->mustBeHandledSync(Argument::type(Message::class))->willReturn(false);
        $messageProducer->produce(Argument::type(Message::class))->willReturn($message)->shouldBeCalled();

        ReporterFactory::withRouter($map, null, $messageProducer->reveal())
            ->withSubscribers(
                new TrackingCommandSubscriber(),
                $this->assertMessageHandledSubscriber(),
            )
            ->reportCommand(null)
            ->dispatch($message);
    }

    /**
     * @test
     */
    public function it_dispatch_command_message_and_locate_alias_name_in_map(): void
    {
        $map = ['some-command' => function (SomeCommand $command): void {}];

        ReporterFactory::withRouter($map, null, null)
            ->withMessageAlias(new InflectorMessageAlias())
            ->withSubscribers(
                new TrackingCommandSubscriber(),
                $this->assertMessageHandledSubscriber(),
            )
            ->reportCommand(null)
            ->dispatch($this->defaultMessageCommand());
    }

    /**
     * @test
     */
    public function it_dispatch_command_message_and_handle_message_with_callable_method(): void
    {
        $commandHandler = new class() {
            private bool $commandHandled = false;

            public function command(SomeCommand $command): void
            {
                $this->commandHandled = true;
            }

            public function isCommandHandled(): bool
            {
                return $this->commandHandled;
            }
        };

        $map = ['some-command' => $commandHandler];

        $this->assertFalse($commandHandler->isCommandHandled());

        ReporterFactory::withRouter($map, null, null)
            ->withCallableMethod('command')
            ->withMessageAlias(new InflectorMessageAlias())
            ->withSubscribers(
                new TrackingCommandSubscriber(),
                $this->assertMessageHandledSubscriber(),
            )
            ->reportCommand(null)
            ->dispatch($this->defaultMessageCommand());

        $this->assertTrue($commandHandler->isCommandHandled());
    }

    /**
     * @test
     */
    public function it_resolve_string_message_handler_through_container(): void
    {
        $messageHandlerString = SomeCommandHandler::class;
        $messageHandler = new SomeCommandHandler();

        $map = [SomeCommand::class => $messageHandlerString];

        $container = $this->prophesize(Container::class);
        $container->make($messageHandlerString)->willReturn($messageHandler)->shouldBeCalled();

        ReporterFactory::withRouter($map, $container->reveal(), null)
            ->withSubscribers(
                new TrackingCommandSubscriber(),
                $this->assertMessageHandledSubscriber(),
            )
            ->reportCommand(null)
            ->dispatch($this->defaultMessageCommand());

        $this->assertTrue($messageHandler->isCommandHandled());
    }

    /**
     * @test
     */
    public function it_raise_exception_if_message_name_not_found_in_map(): void
    {
        $this->expectException(ReporterFailure::class);
        $this->expectExceptionMessage("Unable to find message name " . SomeCommand::class . " in map");

        ReporterFactory::withRouter([], null, null)
            ->withSubscribers(
                new TrackingCommandSubscriber(),
                $this->assertExceptionExistsSubscriber()
            )
            ->reportCommand(null)
            ->dispatch($this->defaultMessageCommand());
    }

    /**
     * @test
     */
    public function it_raise_exception_if_message_handler_is_empty(): void
    {
        $this->expectException(ReporterFailure::class);
        $this->expectExceptionMessage("Router " . SingleHandlerRouter::class . " support and require one handler only");

        $map = [SomeCommand::class => []];

        ReporterFactory::withRouter($map, null, null)
            ->withSubscribers(
                new TrackingCommandSubscriber(),
                $this->assertExceptionExistsSubscriber()
            )
            ->reportCommand(null)
            ->dispatch($this->defaultMessageCommand());
    }

    /**
     * @test
     */
    public function it_raise_exception_if_message_handler_is_not_single(): void
    {
        $this->expectException(ReporterFailure::class);
        $this->expectExceptionMessage("Router " . SingleHandlerRouter::class . " support and require one handler only");

        $map = [SomeCommand::class => [new SomeCommandHandler(), new SomeCommandHandler()]];

        ReporterFactory::withRouter($map, null, null)
            ->withSubscribers(
                new TrackingCommandSubscriber(),
                $this->assertExceptionExistsSubscriber()
            )
            ->reportCommand(null)
            ->dispatch($this->defaultMessageCommand());
    }

    /**
     * @test
     */
    public function it_raise_exception_if_string_message_handler_can_not_be_resolved(): void
    {
        $this->expectException(ReporterFailure::class);
        $this->expectExceptionMessage("Unable to resolve string message handler " . SomeCommandHandler::class . " without container");

        $messageHandlerString = SomeCommandHandler::class;
        $messageHandler = new SomeCommandHandler();

        $map = [SomeCommand::class => $messageHandlerString];

        ReporterFactory::withRouter($map, null, null)
            ->withSubscribers(
                new TrackingCommandSubscriber(),
                $this->assertExceptionExistsSubscriber()
            )
            ->reportCommand(null)
            ->dispatch($this->defaultMessageCommand());

        $this->assertTrue($messageHandler->isCommandHandled());
    }

    /**
     * @test
     * @dataProvider provideInvalidMessageHandler
     * @param object $messageHandler
     */
    public function it_raise_exception_if_object_message_handler_is_not_callable(object $messageHandler): void
    {
        $this->expectException(ReporterFailure::class);
        $this->expectExceptionMessage("Unable to resolve message handler, got type: " . (gettype($messageHandler)));

        $map = [SomeCommand::class => $messageHandler];

        ReporterFactory::withRouter($map, null, null)
            ->withSubscribers(
                new TrackingCommandSubscriber(),
                $this->assertExceptionExistsSubscriber()
            )
            ->reportCommand(null)
            ->dispatch($this->defaultMessageCommand());
    }

    private function defaultMessageCommand(): Message
    {
        $command = SomeCommand::withData(['foo' => 'bar']);

        return new Message($command);
    }

    public function provideInvalidMessageHandler(): Generator
    {
        yield [new \stdClass()];

        yield [new class() {}];
    }
}
