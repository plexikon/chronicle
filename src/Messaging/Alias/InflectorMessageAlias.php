<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Messaging\Alias;

use Illuminate\Support\Str;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;

final class InflectorMessageAlias implements MessageAlias
{
    public function classToType(string $eventClass): string
    {
        return str_replace('\\_', '.', Str::snake($eventClass));
    }

    public function typeToClass(string $eventType): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', str_replace('.', '\\ ', $eventType))));
    }

    public function classToAlias(string $eventClass): string
    {
        $eventType = explode('.', $this->classToType($eventClass));

        return str_replace('_', '-', end($eventType));
    }

    public function typeToAlias(string $eventType): string
    {
        $eventClass = $this->typeToClass($eventType);

        return $this->classToAlias($eventClass);
    }


    public function instanceToType(object $instance): string
    {
        if ($instance instanceof Message) {
            $instance = $instance->event();
        }

        return $this->classToType(get_class($instance));
    }

    public function instanceToAlias(object $instance): string
    {
        if ($instance instanceof Message) {
            $instance = $instance->event();
        }

        return $this->classToAlias(get_class($instance));
    }
}
