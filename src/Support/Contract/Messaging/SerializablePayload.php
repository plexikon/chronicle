<?php

namespace Plexikon\Chronicle\Support\Contract\Messaging;

interface SerializablePayload
{
    /**
     * @return array
     */
    public function toPayload(): array;

    /**
     * @param array $payload
     * @return SerializablePayload
     */
    public static function fromPayload(array $payload): SerializablePayload;
}
