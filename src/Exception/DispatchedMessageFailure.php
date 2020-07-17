<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Exception;

use Plexikon\Chronicle\Messaging\Message;
use Throwable;

final class DispatchedMessageFailure extends RuntimeException
{
    private ?Message $currentMessage = null;

    public static function withException(Throwable $exception): self
    {
        $message = "An error occurred while dispatching message. See previous exceptions";

        return new self($message, 422, $exception);
    }

    public function currentMessage(): ?Message
    {
        return $this->currentMessage;
    }

    public function setCurrentMessage(?Message $currentMessage): void
    {
        $this->currentMessage = $currentMessage;
    }
}
