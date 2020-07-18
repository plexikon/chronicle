<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Providers;

use Illuminate\Support\ServiceProvider;
use Plexikon\Chronicle\Reporter\ReporterManager;
use Plexikon\Chronicle\Support\Contract\Clock;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageFactory;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageSerializer;
use Plexikon\Chronicle\Support\Contract\Messaging\PayloadSerializer;

final class ReporterServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes(
                [$this->getConfigPath() => config_path('reporter.php')],
                'config'
            );
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->getConfigPath(), 'reporter');

        $this->app->bindIf(Clock::class, config('reporter.clock'));

        $this->registerMessageFactories();

        //$this->registerDefaultTracker();

        $this->app->bind(ReporterManager::class);
    }

    private function registerMessageFactories(): void
    {
        $message = config('reporter.messaging');

        $this->app->bindIf(MessageFactory::class, $message['factory']);
        $this->app->bindIf(MessageSerializer::class, $message['serializer']);
        $this->app->bindIf(PayloadSerializer::class, $message['payload_serializer']);
        $this->app->bindIf(MessageAlias::class, $message['alias']);
    }

    private function registerDefaultTracker(): void
    {
        $tracker = config('reporter.tracking.tracker.default');

        if(is_array($tracker)){
            $this->app->bind($tracker['abstract'], $tracker['concrete']);
        }
    }

    private function getConfigPath(): string
    {
        return __DIR__ . '/../../config/reporter.php';
    }
}
