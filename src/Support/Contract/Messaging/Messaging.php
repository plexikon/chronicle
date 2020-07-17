<?php

namespace Plexikon\Chronicle\Support\Contract\Messaging;

interface Messaging extends SerializablePayload
{
    public const COMMAND = 'command';
    public const QUERY = 'query';
    public const EVENT = 'event';
    public const TYPES = [self::COMMAND, self::QUERY, self::EVENT];

    /**
     * @return string
     */
    public function messageType(): string;

    /**
     * @param array $headers
     * @return Messaging
     */
    public function withHeaders(array $headers): Messaging;

    /**
     * @return array
     */
    public function headers(): array;

    /**
     * @param string $header
     * @return mixed
     */
    public function header(string $header);
}
