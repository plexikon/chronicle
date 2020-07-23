<?php
declare(strict_types=1);

namespace Plexikon\Chronicle;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\NullStore;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Plexikon\Chronicle\Chronicling\Aggregate\AggregateCache;
use Plexikon\Chronicle\Chronicling\Aggregate\AggregateRepository;
use Plexikon\Chronicle\Exception\Assertion;
use Plexikon\Chronicle\Exception\RuntimeException;
use Plexikon\Chronicle\Messaging\Decorator\ChainMessageDecorator;
use Plexikon\Chronicle\Stream\StreamName;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateCache as BaseAggregateCache;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateRepository as BaseAggregateRepository;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageDecorator;

class ChronicleRepositoryManager
{
    protected array $repositories = [];
    protected array $customRepositories = [];
    protected array $config;
    protected Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->config = $container->get(Repository::class)->get('chronicler');
    }

    public function create(string $streamName): BaseAggregateRepository
    {
        if ($repository = $this->repositories[$streamName] ?? null) {
            return $repository;
        }

        $config = $this->fromChronicler("repositories.$streamName");

        if (!is_array($config) || empty($config)) {
            throw new RuntimeException("Invalid repository config for stream name $streamName");
        }

        return $this->repositories[$streamName] = $this->resolveAggregateRepository($streamName, $config);
    }

    protected function resolveAggregateRepository(string $streamName, array $config): AggregateRepository
    {
        if ($customRepository = $this->customRepositories[$streamName] ?? null) {
            return $customRepository($this->container, $config);
        }

        $chroniclerId = $config['chronicler_id'] ?? Chronicler::class; // fixMe

        if (!is_string($chroniclerId) || !$this->container->bound($chroniclerId)) {
            throw new RuntimeException(
                "Chronicler service id $chroniclerId must be a string bound in the container"
            );
        }

        return new AggregateRepository(
            $config['aggregate_class_name'],
            $this->container->get($chroniclerId),
            $this->createAggregateCacheDriver($config['cache'] ?? 0),
            new StreamName($streamName),
            $this->createChainEventDecorator($streamName),
        );
    }

    protected function createAggregateCacheDriver(int $caching): BaseAggregateCache
    {
        Assertion::greaterOrEqualThan($caching, 0);

        $store = $caching === 0 ? new NullStore() : new ArrayStore();

        return new AggregateCache($store, $caching);
    }

    protected function createChainEventDecorator(string $streamName): MessageDecorator
    {
        $commons = $this->container->get(Repository::class)->get('reporter.messaging.decorators.commons') ?? [];

        $eventDecorators = array_map(
            fn(string $decorator) => $this->container->make($decorator),
            array_merge(
                $commons,
                $this->fromChronicler("event.decorators") ?? [],
                $this->fromChronicler("repositories.$streamName.event_decorators") ?? []
            )
        );

        return new ChainMessageDecorator(...$eventDecorators);
    }

    /**
     * @param string $key
     * @param null $default
     * @return mixed
     */
    protected function fromChronicler(string $key, $default = null)
    {
        return Arr::get($this->config, $key, $default);
    }
}
