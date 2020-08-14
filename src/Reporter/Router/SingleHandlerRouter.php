<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Reporter\Router;

use Plexikon\Chronicle\Exception\ReporterFailure;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Reporter\Router;

final class SingleHandlerRouter implements Router
{
    private Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @param Message $message
     * @return array<callable>
     * @throws ReporterFailure
     */
    public function route(Message $message): array
    {
        $messageHandlers = $this->router->route($message);

        if (1 !== count($messageHandlers)) {
            throw ReporterFailure::routerSupportAndRequireOneHandlerOnly(static::class);
        }

        return $messageHandlers;
    }
}
