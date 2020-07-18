<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Reporter;

use Illuminate\Contracts\Bus\QueueingDispatcher;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Plexikon\Chronicle\Exception\RuntimeException;
use Plexikon\Chronicle\Messaging\Decorator\ChainMessageDecorator;
use Plexikon\Chronicle\Messaging\Producer\AsyncMessageProducer;
use Plexikon\Chronicle\Messaging\Producer\IlluminateProducer;
use Plexikon\Chronicle\Messaging\Producer\SyncMessageProducer;
use Plexikon\Chronicle\Reporter\Router\MultipleHandlerRouter;
use Plexikon\Chronicle\Reporter\Router\ReporterRouter;
use Plexikon\Chronicle\Reporter\Router\SingleHandlerRouter;
use Plexikon\Chronicle\Reporter\Subscriber\ChainMessageDecoratorSubscriber;
use Plexikon\Chronicle\Reporter\Subscriber\ReporterRouterSubscriber;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageProducer;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageSerializer;
use Plexikon\Chronicle\Support\Contract\Messaging\Messaging;
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageSubscriber;

class ReporterManager
{
    protected array $customReporters = [];
    protected array $config;
    protected Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->config = $container->get(Repository::class)->get('reporter');
    }

    public function reportCommand(?string $name = null): Reporter
    {
        return $this->create($name ?? 'default', Messaging::COMMAND);
    }

    public function reportQuery(?string $name = null): Reporter
    {
        return $this->create($name ?? 'default', Messaging::QUERY);
    }

    public function eventReporter(?string $name = null): Reporter
    {
        return $this->create($name ?? 'default', Messaging::EVENT);
    }

    public function create(string $name, string $type): Reporter
    {
        $this->assertReporterTypeExists($type);

        $key = $this->determineReporterKey($name, $type);

        if ($customReporter = $this->customReporters[$key] ?? null) {
            return $customReporter($this->container, $this->config);
        }

        $reporterConfig = $this->fromReporter("reporter.$type.$name");

        if (!is_array($reporterConfig) || empty($reporterConfig)) {
            throw new RuntimeException("No reporter configuration found with driver $name and type $type");
        }

        return $this->createReporterDriver($type, $reporterConfig);
    }

    public function extend(string $name, string $type, callable $reporter): void
    {
        $this->assertReporterTypeExists($type);

        $key = $this->determineReporterKey($name, $type);

        $this->customReporters[$key] = $reporter;
    }

    protected function createReporterDriver(string $type, array $config): Reporter
    {
        $reporterInstance = $this->newReporterInstance($config);

        $this->subscribeToReporter($reporterInstance, $type, $config);

        return $reporterInstance;
    }

    protected function newReporterInstance(array $config): Reporter
    {
        $reporterClassName = $config['concrete'];

        if (!class_exists($reporterClassName)) {
            throw new RuntimeException("Reporter class name $reporterClassName does not exists");
        }

        $tracker = $config['tracker_id'] ?? null;

        if (is_string($tracker)) {
            $tracker = $this->container->get($tracker);
        }

        return new $reporterClassName($config['name'] ?? $reporterClassName, $tracker);
    }

    protected function subscribeToReporter(Reporter $reporter, string $type, array $config): void
    {
        $subscribers = $this->resolveServices([
            $this->resolveMessageDecoratorSubscriber($config),
            $this->resolveReporterRouterSubscriber($type, $config),
            $this->fromReporter("tracking.subscribers") ?? [],
            $config['subscribers'] ?? []
        ]);

        foreach ($subscribers as $subscriber) {
            $reporter->subscribe($subscriber);
        }
    }

    protected function resolveMessageDecoratorSubscriber(array $config): MessageSubscriber
    {
        $messageDecorators = $this->resolveServices(
            $this->fromReporter('messaging.decorators' ?? []),
            $config['message']['decorators'] ?? []
        );

        return new ChainMessageDecoratorSubscriber(
            new ChainMessageDecorator(...$messageDecorators)
        );
    }

    protected function resolveReporterRouterSubscriber(string $type, array $config): MessageSubscriber
    {
        $defaultRouter = new ReporterRouter(
            $config['map'],
            $this->container->get(MessageAlias::class),
            $config['use_container'] ?? true,
            $config['handler_method'] ?? null
        );

        $reporterRouter = null;

        switch ($type) {
            case 'command':
            case 'query':
                $reporterRouter = new SingleHandlerRouter($defaultRouter);
                break;
            case 'event':
                $allowNoMessageHandler = $config['allow_no_message_handler'] ?? true;
                $reporterRouter = new MultipleHandlerRouter($defaultRouter, $allowNoMessageHandler);
                break;
        }

        if (!$reporterRouter) {
            throw new RuntimeException("Unable to configure reporter router for type $type");
        }

        return new ReporterRouterSubscriber(
            $reporterRouter,
            $this->createMessageProducer($type, $config['route_strategy'] ?? null)
        );
    }

    protected function createMessageProducer(string $type, ?string $driver): MessageProducer
    {
        if ($type === 'query' || $driver === 'sync') {
            return new SyncMessageProducer();
        }

        if(null === $driver){
            $driver = $this->fromReporter('messaging.producer.default');
        }

        if (!in_array($driver, ['per_message', 'async_all'])) {
            throw new RuntimeException("Invalid message producer driver $driver");
        }

        // todo extend

        $config = $this->fromReporter("messaging.producer.$driver");

        if (!$config || empty($config)) {
            throw new RuntimeException("Invalid message producer config for driver $driver");
        }

        $illuminateProducer = new IlluminateProducer(
            $this->container->get(QueueingDispatcher::class),
            $this->container->get(MessageSerializer::class),
            $config['connection'] ?? null,
            $config['queue'] ?? null
        );

        $routeStrategy = 'per_message' === $driver
            ? MessageProducer::ROUTE_PER_MESSAGE : MessageProducer::ROUTE_ALL_ASYNC;

        return new AsyncMessageProducer($illuminateProducer, $routeStrategy);
    }

    /**
     * @param string $driver
     * @param string $type
     * @return string
     */
    protected function determineReporterKey(string $driver, string $type): string
    {
        return $type . '-' . $driver;
    }

    /**
     * @param string $type
     */
    protected function assertReporterTypeExists(string $type): void
    {
        if (!in_array($type, Messaging::TYPES)) {
            throw new RuntimeException("Reporter type $type does not exists");
        }
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function fromReporter(string $key, $default = null)
    {
        return Arr::get($this->config, $key, $default);
    }

    protected function resolveServices(array ...$services): array
    {
        return array_map(function ($service) {
            return is_string($service) ? $this->container->make($service) : $service;
        }, Arr::flatten($services));
    }
}
