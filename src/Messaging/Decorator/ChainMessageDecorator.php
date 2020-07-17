<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Messaging\Decorator;

use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageDecorator;

final class ChainMessageDecorator implements MessageDecorator
{
    private array $decorators;

    public function __construct(MessageDecorator ...$decorators)
    {
        $this->decorators = $decorators;
    }

    public function decorate(Message $message): Message
    {
        foreach ($this->decorators as $decorator) {
            $message = $decorator->decorate($message);
        }

        return $message;
    }
}
