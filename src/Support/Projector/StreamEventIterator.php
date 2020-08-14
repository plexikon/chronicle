<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Support\Projector;

use Generator;
use Iterator;
use Plexikon\Chronicle\Exception\Assert;
use Plexikon\Chronicle\Exception\StreamNotFound;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;

final class StreamEventIterator implements Iterator
{
    /**
     * @var Generator<Message>
     */
    private Generator $eventStreams;
    private ?Message $currentMessage = null;
    private int $currentKey = 0;

    public function __construct(Generator $eventStreams)
    {
        $this->eventStreams = $eventStreams;
        $this->next();
    }

    public function current(): ?Message
    {
        return $this->currentMessage;
    }

    public function next(): void
    {
        try {
            $this->currentMessage = $this->eventStreams->current();

            if ($this->currentMessage) {
                $position = (int)$this->currentMessage->header(MessageHeader::INTERNAL_POSITION);

                Assert::that($position, 'Event stream position must be greater than 0')
                    ->integer()->greaterThan(0);

                $this->currentKey = $position;
            } else {
                $this->resetProperties();
            }

            $this->eventStreams->next();
        } catch (StreamNotFound $exception) {
            $this->resetProperties();
        }
    }

    public function key()
    {
        if (null === $this->currentMessage || 0 === $this->currentKey) {
            return false;
        }

        return $this->currentKey;
    }

    public function valid(): bool
    {
        return null !== $this->currentMessage;
    }

    public function rewind(): void
    {
        //
    }

    private function resetProperties(): void
    {
        $this->currentKey = 0;
        $this->currentMessage = null;
    }
}
