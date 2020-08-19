<?php

namespace Plexikon\Chronicle\Support\Contract\Reporter;

use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Messaging\Messaging;
use React\Promise\PromiseInterface;

interface Reporter
{
    public const DISPATCH_EVENT = 'dispatch_event';
    public const FINALIZE_EVENT = 'finalize_event';

    public const PRIORITY_MESSAGE_FACTORY = 100000;
    public const PRIORITY_MESSAGE_DECORATOR = 90000;
    public const PRIORITY_MESSAGE_VALIDATION = 30000;
    public const PRIORITY_ROUTE = 20000;
    public const PRIORITY_INVOKE_HANDLER = 0;

    /**
     * @param Message|Messaging|object|array $message
     * @return void|PromiseInterface
     */
    public function publish($message);
}
