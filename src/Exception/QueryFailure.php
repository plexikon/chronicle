<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Exception;

use Illuminate\Database\QueryException;
use PDOException;
use Throwable;

final class QueryFailure extends RuntimeException
{
    public static function fromQueryException(QueryException $queryException): self
    {
        return new self(
            self::getPreviousExceptionMessage($queryException->getPrevious()),
            (int)$queryException->getCode(),
            $queryException
        );
    }

    private static function getPreviousExceptionMessage(Throwable $previousException): string
    {
        if ($previousException instanceof PDOException) {
            $errorInfo = $previousException->errorInfo;

            sprintf("Error %s. \nError-Info: %s", $errorInfo[0], $errorInfo[2]);
        }

        return $previousException->getMessage();
    }
}
