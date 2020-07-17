<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Reporter\Router;

use Plexikon\Chronicle\Exception\ReporterFailure;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Reporter\Router\MultipleHandlerRouter;
use Plexikon\Chronicle\Support\Contract\Reporter\Router;
use Plexikon\Chronicle\Tests\Double\SomeCommand;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use Prophecy\Argument;

final class MultipleHandlerRouterTest extends TestCase
{
    /**
     * @test
     */
    public function it_route_message_to_handlers(): void
    {
        $message = new Message(SomeCommand::withData(['foo' => 'bar']));

        $expectedHandlers = ['some', ' handler'];

        $defaultRouter = $this->prophesize(Router::class);
        $defaultRouter->route(Argument::type(Message::class))->willReturn($expectedHandlers);

        $router = new MultipleHandlerRouter($defaultRouter->reveal(), true);
        $messageHandlers = $router->route($message);

        $this->assertEquals($expectedHandlers, $messageHandlers);
    }

    /**
     * @test
     */
    public function it_allow_empty_handler(): void
    {
        $message = new Message(SomeCommand::withData(['foo' => 'bar']));

        $emptyHandler = [];

        $defaultRouter = $this->prophesize(Router::class);
        $defaultRouter->route(Argument::type(Message::class))->willReturn($emptyHandler);

        $router = new MultipleHandlerRouter($defaultRouter->reveal(), true);
        $messageHandlers = $router->route($message);

        $this->assertEmpty($messageHandlers);
    }

    /**
     * @test
     */
    public function it_raise_exception_if_no_handler_is_not_allowed(): void
    {
        $this->expectException(ReporterFailure::class);
        $this->expectExceptionMessage("Router " . MultipleHandlerRouter::class . " disallow no message handler");

        $message = new Message(SomeCommand::withData(['foo' => 'bar']));

        $emptyHandler = [];

        $defaultRouter = $this->prophesize(Router::class);
        $defaultRouter->route(Argument::type(Message::class))->willReturn($emptyHandler);

        $router = new MultipleHandlerRouter($defaultRouter->reveal(), false);
        $router->route($message);
    }
}
