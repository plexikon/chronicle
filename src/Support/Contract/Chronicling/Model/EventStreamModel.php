<?php

namespace Plexikon\Chronicle\Support\Contract\Chronicling\Model;

interface EventStreamModel
{
    public const TABLE = 'event_streams';

    /**
     * @return string
     */
    public function realStreamName(): string;

    /**
     * @return string
     */
    public function tableName(): string;
}
