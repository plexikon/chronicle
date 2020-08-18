<?php

namespace Plexikon\Chronicle\Support\Contract\Reporter;

use Plexikon\Chronicle\Messaging\Message;

interface AuthorizationService
{
    /**
     * @param string             $event
     * @param null|Message|mixed $context
     * @return mixed
     */
    public function isGranted(string $event, $context = null);
}
