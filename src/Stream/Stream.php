<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Stream;

use Generator;
use Plexikon\Chronicle\Messaging\Message;

final class Stream
{
    /**
     * @var iterable|Message[]
     */
    private iterable $messages;

    private StreamName $streamName;

    /**
     * Stream constructor.
     * @param StreamName $streamName
     * @param iterable<Message> $messages
     */
    public function __construct(StreamName $streamName, iterable $messages = [])
    {
        $this->streamName = $streamName;
        $this->messages = $messages;
    }

    public function streamName(): StreamName
    {
        return $this->streamName;
    }

    public function events(): Generator
    {
        yield from $this->messages;

        if ($this->messages instanceof Generator) {
            return (int)$this->messages->getReturn();
        }

        return count($this->messages);
    }
}
