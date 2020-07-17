<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Exception;

use Illuminate\Database\QueryException;

final class ConcurrencyException extends RuntimeException
{
    public static function fromQueryException(QueryException $exception): self
    {
        $errorInfo = $exception->errorInfo;

        return new self(sprintf("Error %s. \nError-Info: %s", $errorInfo[0], $errorInfo[2]));
    }

    public static function fromUnlockStreamFailure(QueryException $exception): self
    {
        $errorInfo = $exception->errorInfo;

        $message = "Events or Aggregates ids have already been used in the same stream";
        $message .= sprintf("Error %s. \nError-Info: %s", $errorInfo[0], $errorInfo[2]);

        return new self($message);
    }

    public static function failedToAcquireLock(): self
    {
        return new self('Failed to acquire lock for writing to stream');
    }
}
