<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Reporter\Router;

use Generator;
use Plexikon\Chronicle\Exception\ReporterFailure;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Reporter\Router\SingleHandlerRouter;
use Plexikon\Chronicle\Support\Contract\Reporter\Router;
use Plexikon\Chronicle\Tests\Double\SomeCommand;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use Prophecy\Argument;

final class SingleHandlerRouterTest extends TestCase
{
    /**
     * @test
     */
    public function it_route_message_to_his_handler(): void
    {
        $message = new Message(SomeCommand::withData(['foo' => 'bar']));

        $expectedHandler = ['some'];

        $defaultRouter = $this->prophesize(Router::class);
        $defaultRouter->route(Argument::type(Message::class))->willReturn($expectedHandler);

        $router = new SingleHandlerRouter($defaultRouter->reveal());
        $messageHandler = $router->route($message);

        $this->assertEquals($expectedHandler, $messageHandler);
    }

    /**
     * @test
     * @dataProvider provideInvalidNumberOfMessageHandler
     * @param array $messageHandlers
     */
    public function it_raise_exception_if_required_handler_is_not_single(array $messageHandlers): void
    {
        $this->expectException(ReporterFailure::class);
        $this->expectExceptionMessage("Router " . SingleHandlerRouter::class . " support and require one handler only");

        $message = new Message(SomeCommand::withData(['foo' => 'bar']));

        $defaultRouter = $this->prophesize(Router::class);
        $defaultRouter->route(Argument::type(Message::class))->willReturn($messageHandlers);

        $router = new SingleHandlerRouter($defaultRouter->reveal());
        $router->route($message);
    }

    public function provideInvalidNumberOfMessageHandler(): Generator
    {
        yield [[]];

        yield [['too', 'many', 'handlers']];
    }
}
