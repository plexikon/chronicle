<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Factory;

use Plexikon\Chronicle\Support\Contract\Tracker\MessageSubscriber;

trait MergeWithReporterConfig
{
    public function mergeConfigSubscribers(string $busType, MessageSubscriber ...$messageSubscribers): void
    {
        $configKey = "reporter.reporting.$busType.default.messaging.subscribers";

        $subscribers = array_merge($this->app['config']->get($configKey) ?? [], $messageSubscribers);

        $this->app['config']->set($configKey, $subscribers);
    }
}
