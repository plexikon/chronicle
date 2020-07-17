<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Exception;

use Plexikon\Chronicle\Messaging\Message;

final class ReporterFailure extends RuntimeException
{
    public static function unsupportedMessageHandler($messageHandler): self
    {
        $message = 'Unable to resolve message handler, got type: ' . (gettype($messageHandler));

        return new self($message);
    }

    public static function missingContainerForMessageHandler(string $messageHandler): self
    {
        $message = "Unable to resolve string message handler $messageHandler without container";

        return new self($message);
    }

    public static function messageNameNotFoundInMap(string $messageName): self
    {
        $message = "Unable to find message name $messageName in map";

        return new self($message);
    }

    public static function routerSupportAndRequireOneHandlerOnly(string $routerClass): self
    {
        $message = "Router $routerClass support and require one handler only";

        return new self($message);
    }

    public static function routerDisallowEmptyHandler(string $routerClass): self
    {
        $message = "Router $routerClass disallow no message handler";

        return new self($message);
    }

    public static function messageNotHandled(Message $message): self
    {
        $eventClass = get_class($message->event());

        $message = "Message event {$eventClass} was not handled";

        return new self($message);
    }
}
