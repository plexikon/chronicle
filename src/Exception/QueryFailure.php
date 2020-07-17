<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Exception;

use Illuminate\Database\QueryException;

final class QueryFailure extends RuntimeException
{
    public static function fromQueryException(QueryException $queryException): self
    {
        $errorInfo = $queryException->errorInfo;

        return new self(
            sprintf("Error %s. \nError-Info: %s", $errorInfo[0], $errorInfo[2]),
            (int)$queryException->getCode(),
            $queryException
        );
    }
}
