<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Stream;

use Plexikon\Chronicle\Exception\Assertion;

final class StreamName
{
    private string $name;

    public function __construct(string $name)
    {
        Assertion::notBlank($name, 'Stream name can not be empty');

        $this->name = $name;
    }

    public function toString(): string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
