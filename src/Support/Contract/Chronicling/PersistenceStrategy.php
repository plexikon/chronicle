<?php

namespace Plexikon\Chronicle\Support\Contract\Chronicling;

use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Stream\StreamName;

interface PersistenceStrategy
{
    /**
     * @param StreamName $streamName
     * @return string
     */
    public function tableName(StreamName $streamName): string;

    /**
     * @param string $tableName
     * @return callable|null
     */
    public function up(string $tableName): ?callable;

    /**
     * @param Message $message
     * @return array
     */
    public function serializeMessage(Message $message): array;
}
