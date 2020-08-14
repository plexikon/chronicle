<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Reporter\Router;

use Closure;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Plexikon\Chronicle\Exception\ReporterFailure;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Reporter\Router;

final class ReporterRouter implements Router
{
    /**
     * @var array<string,mixed>
     */
    private array $map;

    private MessageAlias $messageAlias;

    private ?Container $container;

    private ?string $callableMethod;

    /**
     * ReporterRouter constructor.
     * @param array<string,mixed> $map
     * @param MessageAlias $messageAlias
     * @param Container|null $container
     * @param string|null $callableMethod
     */
    public function __construct(array $map,
                                MessageAlias $messageAlias,
                                ?Container $container,
                                ?string $callableMethod)
    {
        $this->map = $map;
        $this->messageAlias = $messageAlias;
        $this->container = $container;
        $this->callableMethod = $callableMethod;
    }

    public function route(Message $message): array
    {
        $messageHandlers = $this->determineMessageHandler($message);

        foreach ($messageHandlers as &$messageHandler) {
            $messageHandler = $this->messageHandlerToCallable($messageHandler);
        }

        return $messageHandlers;
    }

    /**
     * @param string|object|callable $messageHandler
     * @return callable
     * @throws BindingResolutionException
     */
    private function messageHandlerToCallable($messageHandler): callable
    {
        if (is_string($messageHandler)) {
            if (!$this->container) {
                throw ReporterFailure::missingContainerForMessageHandler($messageHandler);
            }

            $messageHandler = $this->container->make($messageHandler);
        }

        if (is_callable($messageHandler)) {
            return $messageHandler;
        }

        if ($this->callableMethod && (is_object($messageHandler) && method_exists($messageHandler, $this->callableMethod))) {
            return Closure::fromCallable([$messageHandler, $this->callableMethod]);
        }

        throw ReporterFailure::unsupportedMessageHandler($messageHandler);
    }

    /**
     * @param Message $message
     * @return array<mixed>
     */
    private function determineMessageHandler(Message $message): array
    {
        $messageAlias = $this->messageAlias->instanceToAlias($message->event());

        if (null === $messageHandlers = $this->map[$messageAlias] ?? null) {
            throw ReporterFailure::messageNameNotFoundInMap($messageAlias);
        }

        return is_array($messageHandlers) ? $messageHandlers : [$messageHandlers];
    }
}
