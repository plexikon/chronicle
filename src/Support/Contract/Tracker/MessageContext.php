<?php

namespace Plexikon\Chronicle\Support\Contract\Tracker;

use Generator;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Messaging\Messaging;
use React\Promise\PromiseInterface;

interface MessageContext extends Context
{
    /**
     * @param array|object|Messaging|Message $message
     */
    public function withMessage($message): void;

    /**
     * @param iterable $messageHandlers
     */
    public function withMessageHandlers(iterable $messageHandlers): void;

    /**
     * @param PromiseInterface $promise
     */
    public function withPromise(PromiseInterface $promise): void;

    /**
     * @param bool $isMessageHandled
     */
    public function setMessageHandled(bool $isMessageHandled): void;

    /**
     * @return bool
     */
    public function isMessageHandled(): bool;

    /**
     * @return Generator
     */
    public function messageHandlers(): Generator;

    /**
     * @return array|object|Messaging|Message
     */
    public function getMessage();

    /**
     * @return PromiseInterface
     */
    public function getPromise(): ?PromiseInterface;
}
