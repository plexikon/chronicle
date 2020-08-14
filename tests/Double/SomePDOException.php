<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Double;

use PDOException;
use Throwable;

final class SomePDOException extends PDOException
{
    public function __construct(string $code, string $message = "foo", Throwable $previous = null)
    {
        parent::__construct($message,0, $previous);

        $this->code = $code;
        $this->errorInfo = ['foo','bar','foo_bar'];// fixMe
    }
}
