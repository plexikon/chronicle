<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Exception;

use Assert\Assertion as BaseAssertion;

class Assertion extends BaseAssertion
{
    protected static $exceptionClass = InvalidArgumentException::class;
}
