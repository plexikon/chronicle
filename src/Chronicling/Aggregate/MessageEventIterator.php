<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Chronicling\Aggregate;

use Countable;
use Generator;
use Iterator;
use Plexikon\Chronicle\Messaging\Message;

final class MessageEventIterator implements Iterator, Countable
{
    private int $messageKey = 0;
    private ?Message $currentMessage = null;
    private Generator $messages;

    public function __construct(Generator $messages)
    {
        $this->messages = $messages;
        $this->next();
    }

    /**
     * @return object|AggregateChanged
     */
    public function current(): object
    {
        return $this->currentMessage->event();
    }

    public function next(): void
    {
        $this->currentMessage = $this->messages->current();

        $this->messageKey = $this->currentMessage ? (int)$this->messages->key() : 0;

        $this->messages->next();
    }

    public function key()
    {
        return $this->currentMessage ? $this->messageKey : false;
    }

    public function valid(): bool
    {
        return null !== $this->currentMessage;
    }

    public function rewind()
    {
        //
    }

    public function count(): int
    {
        return (int)$this->messages->getReturn() ?: 0;
    }
}
