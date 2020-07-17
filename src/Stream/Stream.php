<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Stream;

use Generator;

final class Stream
{
    private StreamName $streamName;
    private iterable $messages;

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
            return $this->messages->getReturn();
        }

        return count($this->messages);
    }
}
