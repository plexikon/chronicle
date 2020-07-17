<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Reporter\Router;

use Plexikon\Chronicle\Exception\ReporterFailure;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Reporter\Router;

final class MultipleHandlerRouter implements Router
{
    private Router $router;
    private bool $allowNoMessageHandler;

    public function __construct(Router $router, bool $allowNoMessageHandler)
    {
        $this->router = $router;
        $this->allowNoMessageHandler = $allowNoMessageHandler;
    }

    public function route(Message $message): array
    {
        $messageHandlers = $this->router->route($message);

        if (!$this->allowNoMessageHandler && 0 === count($messageHandlers)) {
            throw ReporterFailure::routerDisallowEmptyHandler(static::class);
        }

        return $messageHandlers;
    }
}
