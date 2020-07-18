<?php

namespace Plexikon\Chronicle\Support\Contract\Reporter;

use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Messaging\Messaging;
use React\Promise\PromiseInterface;

interface Reporter
{
    public const DISPATCH_EVENT = 'dispatch_event';
    public const FINALIZE_EVENT = 'finalize_event';

    /**
     * @param Message|Messaging|object|array $message
     * @return void|PromiseInterface
     */
    public function publish($message);
}
