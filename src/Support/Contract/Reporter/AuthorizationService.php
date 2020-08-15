<?php

namespace Plexikon\Chronicle\Support\Contract\Reporter;

use Plexikon\Chronicle\Messaging\Message;

interface AuthorizationService
{
    /**
     * @param string $eventType
     * @param null|Message|mixed $context
     * @return mixed
     */
    public function isGranted(string $eventType, $context = null);
}
