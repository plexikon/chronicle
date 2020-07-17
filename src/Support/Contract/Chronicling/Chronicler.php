<?php

namespace Plexikon\Chronicle\Support\Contract\Chronicling;

use Plexikon\Chronicle\Exception\StreamAlreadyExists;
use Plexikon\Chronicle\Exception\StreamNotFound;
use Plexikon\Chronicle\Stream\Stream;
use Plexikon\Chronicle\Stream\StreamName;

interface Chronicler extends ReadOnlySubscriber
{
    /**
     * @param Stream $stream
     * @throws StreamAlreadyExists
     */
    public function persistFirstCommit(Stream $stream): void;

    /**
     * @param Stream $stream
     * @throws StreamNotFound
     */
    public function persist(Stream $stream): void;

    /**
     * @param StreamName $streamName
     * @throws StreamNotFound
     */
    public function delete(StreamName $streamName): void;
}
