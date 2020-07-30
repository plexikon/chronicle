<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Plexikon\Chronicle\Exception\RuntimeException;
use Plexikon\Chronicle\ProjectorManager;
use Plexikon\Chronicle\Support\Contract\Chronicling\Model\EventStreamProvider;
use Plexikon\Chronicle\Support\Contract\Chronicling\Model\ProjectionProvider;
use Plexikon\Chronicle\Support\Contract\Chronicling\QueryScope;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorManager as BaseProjectorManager;
use Plexikon\Chronicle\Support\QueryScope\QueryScopeFactory;

class ProjectorServiceManager
{
    protected array $customProjectors = [];
    protected array $projectors = [];
    protected array $config;
    protected Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->config = $container->get(Repository::class)->get('chronicler', []);
    }

    public function create(string $driver = 'default'): BaseProjectorManager
    {
        if ($projector = $this->projectors[$driver] ?? null) {
            return $projector;
        }

        $config = $this->fromChronicle("projectors.projector.$driver");

        if (!is_array($config) || empty($config)) {
            throw new RuntimeException("Invalid config for projector manager driver $driver");
        }

        return $this->projectors[$driver] = $this->resolveProjectorManager($driver, $config);
    }

    public function extend(string $driver, callable $projectorManager): void
    {
        $this->customProjectors[$driver] = $projectorManager;
    }

    protected function resolveProjectorManager(string $driver, array $config): BaseProjectorManager
    {
        if ($customProjector = $this->customProjectors[$driver] ?? null) {
            return $customProjector($this->container, $config);
        }

        $connection = $config['connection'];

        $method = 'create' . Str::studly($connection . 'ProjectorManager') . 'Driver';

        if (method_exists($this, $method)) {
            return $this->$method($config);
        }

        throw new RuntimeException("Unable to resolve projector manager with driver $driver");
    }

    protected function createPgsqlProjectorManagerDriver(array $config): BaseProjectorManager
    {
        $queryScope = $this->determineScopeConnection($config['connection']);
        $options = $this->determineProjectorOptions($config['options']);

        return new ProjectorManager(
            $this->container->get($config['chronicler_id']),
            $this->container->get(EventStreamProvider::class),
            $this->container->get(ProjectionProvider::class),
            $this->container->get(MessageAlias::class),
            $queryScope,
            $options
        );
    }

    protected function determineProjectorOptions(?string $optionKey): array
    {
        return $this->fromChronicle("projectors.options.$optionKey") ?? [];
    }

    protected function determineScopeConnection(string $connection): QueryScope
    {
        $queryScope = new QueryScopeFactory($this->container);

        return $queryScope->fromDriver($connection);
    }

    /**
     * @param string $key
     * @return mixed
     */
    protected function fromChronicle(string $key)
    {
        return Arr::get($this->config, $key);
    }
}
