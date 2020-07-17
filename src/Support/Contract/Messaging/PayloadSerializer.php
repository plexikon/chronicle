<?php

namespace Plexikon\Chronicle\Support\Contract\Messaging;

interface PayloadSerializer
{
    /**
     * @param object $event
     * @return array
     */
    public function serializePayload(object $event): array;

    /**
     * @param string $className
     * @param array $payload
     * @return object
     */
    public function unserializePayload(string $className, array $payload): object;
}
