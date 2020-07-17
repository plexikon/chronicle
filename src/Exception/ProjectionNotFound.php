<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Exception;

use Plexikon\Chronicle\Stream\StreamName;

final class ProjectionNotFound extends RuntimeException
{
    public static function withStreamName(StreamName $streamName): self
    {
        return new self("Projection with stream name {$streamName} not found");
    }

    public static function withName(string $projectionName): self
    {
        return new self("Projection name {$projectionName} not found");
    }
}
