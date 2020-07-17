<?php

namespace Plexikon\Chronicle\Support\Contract\Messaging;

interface ValidateMessage extends Messaging
{
    /**
     * @return array
     */
    public function validationRules(): array;
}
