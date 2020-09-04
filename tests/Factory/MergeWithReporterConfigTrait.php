<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Factory;

use Illuminate\Contracts\Foundation\Application;
use Plexikon\Chronicle\Support\Contract\Messaging\Messaging;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageSubscriber;

trait MergeWithReporterConfigTrait
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @param string            $busType
     * @param MessageSubscriber ...$messageSubscribers
     */
    public function mergeReporterConfigSubscribers(string $busType, MessageSubscriber ...$messageSubscribers): void
    {
        assert(in_array($busType, [Messaging::TYPES]));

        $configKey = "reporter.reporting.$busType.default.messaging.subscribers";

        $subscribers = array_merge($this->app['config']->get($configKey) ?? [], $messageSubscribers);

        $this->app['config']->set($configKey, $subscribers);
    }
}
