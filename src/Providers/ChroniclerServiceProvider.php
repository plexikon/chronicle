<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Providers;

use Illuminate\Support\ServiceProvider;
use Plexikon\Chronicle\ChronicleStoreManager;
use Plexikon\Chronicle\Support\Contract\Chronicling\Model\EventStreamProvider;
use Plexikon\Chronicle\Support\Contract\Chronicling\Model\ProjectionProvider;
use Plexikon\Chronicle\Support\Contract\Messaging\EventSerializer;

final class ChroniclerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes(
                [$this->getConfigPath() => config_path('chronicler.php')],
                'config'
            );

            $console = config('chronicler.console') ?? [];

            if (true === $console['load_migrations']) {
                $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
            }

            if (true === $console['load_commands']) {
                $this->commands($console['commands']);
            }
        }
    }

    public function register(): void
    {
        $config = config('chronicler');

        $this->registerModels($config['models']);
        $this->registerEventFactories($config['event']);

        $this->app->singleton(ChronicleStoreManager::class);
    }

    private function registerModels(array $config): void
    {
        $this->app->bindIf(EventStreamProvider::class, $config['event_stream']);
        $this->app->bindIf(ProjectionProvider::class, $config['projection']);
    }

    private function registerEventFactories(array $config): void
    {
        $this->app->bindIf(EventSerializer::class, $config['serializer']);
    }

    private function getConfigPath(): string
    {
        return __DIR__ . '/../../config/chronicler.php';
    }
}
