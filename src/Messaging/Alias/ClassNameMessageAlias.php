<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Messaging\Alias;

use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use function get_class;

final class ClassNameMessageAlias implements MessageAlias
{
    public function classToType(string $eventClass): string
    {
        return $eventClass;
    }

    public function typeToClass(string $eventType): string
    {
        return $eventType;
    }

    public function classToAlias(string $eventClass): string
    {
        return $eventClass;
    }

    public function typeToAlias(string $eventType): string
    {
        return $eventType;
    }

    public function instanceToType(object $instance): string
    {
        if ($instance instanceof Message) {
            $instance = $instance->event();
        }

        return get_class($instance);
    }

    public function instanceToAlias(object $instance): string
    {
        if ($instance instanceof Message) {
            $instance = $instance->event();
        }

        return get_class($instance);
    }
}
