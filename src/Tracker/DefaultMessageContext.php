<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tracker;

use Generator;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Messaging\Messaging;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Tracker\Concerns\HasContext;
use React\Promise\PromiseInterface;

final class DefaultMessageContext implements MessageContext
{
    use HasContext;

    /**
     * @var array|object|Message|Messaging
     */
    private $message;
    private ?PromiseInterface $promise = null;
    private iterable $messageHandlers = [];
    private bool $isMessageHandled = false;

    public function withMessage($message): void
    {
        $this->message = $message;
    }

    public function withMessageHandlers(iterable $messageHandlers): void
    {
        $this->messageHandlers = $messageHandlers;
    }

    public function withPromise(PromiseInterface $promise): void
    {
        $this->promise = $promise;
    }

    public function setMessageHandled(bool $isMessageHandled): void
    {
        $this->isMessageHandled = $isMessageHandled;
    }

    public function isMessageHandled(): bool
    {
        return $this->isMessageHandled;
    }

    public function messageHandlers(): Generator
    {
        yield from $this->messageHandlers;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getPromise(): ?PromiseInterface
    {
        return $this->promise;
    }
}
