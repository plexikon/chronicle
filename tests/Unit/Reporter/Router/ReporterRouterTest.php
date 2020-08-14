<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Reporter\Router;

use Closure;
use Generator;
use Illuminate\Contracts\Container\Container;
use Plexikon\Chronicle\Exception\ReporterFailure;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Reporter\Router\ReporterRouter;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Tests\Double\SomeCommand;
use Plexikon\Chronicle\Tests\Double\SomeCommandHandler;
use Plexikon\Chronicle\Tests\Unit\TestCase;

final class ReporterRouterTest extends TestCase
{
    /**
     * @test
     */
    public function it_route_message_to_one_handler(): void
    {
        $commandString = SomeCommand::class;
        $command = SomeCommand::withData(['foo' => 'bar']);
        $message = new Message($command);
        $commandHandler = function (SomeCommand $command): void {};

        $map = [$commandString => $commandHandler];

        $messageAlias = $this->prophesize(MessageAlias::class);
        $messageAlias->instanceToAlias($command)->willReturn(SomeCommand::class)->shouldBeCalled();

        $router = new ReporterRouter($map, $messageAlias->reveal(), null, null);

        $handlers = $router->route($message);

        $this->assertEquals($commandHandler, array_shift($handlers));
    }

    /**
     * @test
     */
    public function it_route_message_to_many_handlers(): void
    {
        $commandString = SomeCommand::class;
        $command = SomeCommand::withData(['foo' => 'bar']);
        $message = new Message($command);
        $commandHandlers = [
            function (SomeCommand $command): void {},
            function (SomeCommand $command): void {},
        ];

        $map = [$commandString => $commandHandlers];

        $messageAlias = $this->prophesize(MessageAlias::class);
        $messageAlias->instanceToAlias($command)->willReturn(SomeCommand::class)->shouldBeCalled();

        $router = new ReporterRouter($map, $messageAlias->reveal(), null, null);

        $handlers = $router->route($message);

        $this->assertEquals($commandHandlers, $handlers);
    }

    /**
     * @test
     */
    public function it_route_message_to_no_handler(): void
    {
        $commandString = SomeCommand::class;
        $command = SomeCommand::withData(['foo' => 'bar']);
        $message = new Message($command);
        $commandHandlers = [];

        $map = [$commandString => $commandHandlers];

        $messageAlias = $this->prophesize(MessageAlias::class);
        $messageAlias->instanceToAlias($command)->willReturn(SomeCommand::class)->shouldBeCalled();

        $router = new ReporterRouter($map, $messageAlias->reveal(), null, null);

        $handlers = $router->route($message);

        $this->assertEmpty($handlers);
    }

    /**
     * @test
     */
    public function it_route_message_to_a_non_callable_handler(): void
    {
        $commandString = SomeCommand::class;
        $command = SomeCommand::withData(['foo' => 'bar']);
        $message = new Message($command);
        $commandHandler = new class() {
            public function command(SomeCommand $command)
            {
                //
            }
        };

        $this->assertIsNotCallable($commandHandler);

        $map = [$commandString => $commandHandler];

        $messageAlias = $this->prophesize(MessageAlias::class);
        $messageAlias->instanceToAlias($command)->willReturn(SomeCommand::class)->shouldBeCalled();

        $router = new ReporterRouter($map, $messageAlias->reveal(), null, 'command');

        $handlers = $router->route($message);
        $handler = array_shift($handlers);

        $this->assertNotEquals($commandHandler, $handler);
        $this->assertIsCallable($handler);
        $this->assertInstanceOf(Closure::class, $handler);
    }

    /**
     * @test
     */
    public function it_route_message_to_string_handler_resolved_through_container(): void
    {
        $commandString = SomeCommand::class;
        $command = SomeCommand::withData(['foo' => 'bar']);
        $message = new Message($command);
        $commandHandlerString = SomeCommandHandler::class;
        $expectedHandler = new SomeCommandHandler();

        $map = [$commandString => $commandHandlerString];

        $messageAlias = $this->prophesize(MessageAlias::class);
        $messageAlias->instanceToAlias($command)->willReturn(SomeCommand::class)->shouldBeCalled();

        $container = $this->prophesize(Container::class);
        $container->make(SomeCommandHandler::class)->willReturn($expectedHandler)->shouldBeCalled();

        $router = new ReporterRouter($map, $messageAlias->reveal(), $container->reveal(), null);

        $handlers = $router->route($message);

        $this->assertEquals($expectedHandler, array_shift($handlers));
    }

    /**
     * @test
     */
    public function it_raise_exception_if_message_name_not_found_in_map(): void
    {
        $this->expectException(ReporterFailure::class);
        $this->expectExceptionMessage("Unable to find message name " . SomeCommand::class . " in map");

        $command = SomeCommand::withData(['foo' => 'bar']);
        $message = new Message($command);
        $map = ['message_name' => 'event_handler'];

        $messageAlias = $this->prophesize(MessageAlias::class);
        $messageAlias->instanceToAlias($command)->willReturn(SomeCommand::class)->shouldBeCalled();

        $router = new ReporterRouter($map, $messageAlias->reveal(), null, null);

        $router->route($message);
    }

    /**
     * @test
     */
    public function it_raise_exception_if_string_message_handler_is_not_resolved_through_container(): void
    {
        $this->expectException(ReporterFailure::class);
        $this->expectExceptionMessage("Unable to resolve string message handler " . SomeCommandHandler::class . " without container");

        $command = SomeCommand::withData(['foo' => 'bar']);
        $message = new Message($command);
        $map = [SomeCommand::class => SomeCommandHandler::class];

        $messageAlias = $this->prophesize(MessageAlias::class);
        $messageAlias->instanceToAlias($command)->willReturn(SomeCommand::class)->shouldBeCalled();

        $router = new ReporterRouter($map, $messageAlias->reveal(), null, null);

        $router->route($message);
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

        $command = SomeCommand::withData(['foo' => 'bar']);
        $message = new Message($command);
        $map = [SomeCommand::class => $messageHandler];

        $messageAlias = $this->prophesize(MessageAlias::class);
        $messageAlias->instanceToAlias($command)->willReturn(SomeCommand::class)->shouldBeCalled();

        $router = new ReporterRouter($map, $messageAlias->reveal(), null, null);

        $router->route($message);
    }

    public function provideInvalidMessageHandler(): Generator
    {
        yield [new \stdClass()];

        yield [new class() {}];
    }
}
