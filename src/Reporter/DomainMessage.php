<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Reporter;

use Plexikon\Chronicle\Support\Contract\Messaging\Messaging;
use Plexikon\Chronicle\Support\Contract\Messaging\SerializablePayload;

abstract class DomainMessage implements Messaging
{
    protected array $headers = [];
    protected array $payload;

    protected function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function toPayload(): array
    {
        return $this->payload;
    }

    public static function fromPayload(array $payload): SerializablePayload
    {
        return new static($payload);
    }

    public function withHeaders(array $headers): Messaging
    {
        $self = clone $this;
        $self->headers = $headers;

        return $self;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function header(string $header)
    {
        if (isset($this->headers[$header])) {
            return $this->headers[$header];
        }

        return null;
    }
}
