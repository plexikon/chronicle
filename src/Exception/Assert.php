<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Exception;

use Assert\Assert as BaseAlias;

class Assert extends BaseAlias
{
    /**
     * @var string
     */
    protected static $assertionClass = Assertion::class;
}
