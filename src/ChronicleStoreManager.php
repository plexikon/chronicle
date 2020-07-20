<?php
declare(strict_types=1);

namespace Plexikon\Chronicle;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Plexikon\Chronicle\Chronicling\TransactionalEventChronicler;
use Plexikon\Chronicle\Chronicling\WriteLock\MysqlWriteLock;
use Plexikon\Chronicle\Chronicling\WriteLock\NoWriteLock;
use Plexikon\Chronicle\Chronicling\WriteLock\PgsqlWriteLock;
use Plexikon\Chronicle\Exception\Assertion;
use Plexikon\Chronicle\Exception\RuntimeException;
use Plexikon\Chronicle\Support\Connection\StreamEventLoader;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Chronicling\EventChronicler;
use Plexikon\Chronicle\Support\Contract\Chronicling\Model\EventStreamProvider;
use Plexikon\Chronicle\Support\Contract\Chronicling\TransactionalChronicler;
use Plexikon\Chronicle\Support\Contract\Chronicling\WriteLockStrategy;
use Plexikon\Chronicle\Support\Contract\Tracker\TransactionalEventTracker;

class ChronicleStoreManager
{
    protected array $customChroniclers = [];
    protected array $config;
    protected Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->config = $container->get(Repository::class)->get('chronicler');
    }

    public function create(string $driver): Chronicler
    {
        if ($customChronicler = $this->customChroniclers[$driver] ?? null) {
            return $customChronicler($this->container, $this->config);
        };

        $config = $this->fromChronicler("connections.$driver");

        if (!is_array($config)) {
            throw new RuntimeException("Chronicle store driver $driver not found");
        }

        $chronicler = $this->resolveChronicleStore($driver, $config);

        if ($chronicler instanceof EventChronicler) {
            $this->attachSubscribers($chronicler, $config);
        }

        return $chronicler;
    }

    public function extend(string $driver, callable $chronicler): void
    {
        $this->customChroniclers[$driver] = $chronicler;
    }

    protected function resolveChronicleStore(string $driver, array $config): Chronicler
    {
        $method = 'create' . Str::studly($driver . 'ChronicleDriver');

        if (!method_exists($this, $method)) {
            throw new RuntimeException("Unable to resolve chronicle store with driver $driver");
        }

        $chronicler = $this->$method($config);

        if ('in_memory' === $driver) {
            return $chronicler;
        }

        return $this->resolveChronicleDecorator($chronicler, $config);
    }

    protected function createPgsqlChronicleDriver(array $config): Chronicler
    {
        $driver = $config['driver'];

        return new PgsqlChronicler(
            $this->container['db']->connection($driver),
            $this->container->get(EventStreamProvider::class),
            $this->container->make($config['strategy']),
            $this->createDatabaseWriteLockDriver($driver, $config['use_write_lock'] ?? false),
            $this->container->make(StreamEventLoader::class),
            $config['options']['disable_transaction'] ?? false
        );
    }

    protected function createInMemoryChronicleDriver(array $config): Chronicler
    {
        throw new RuntimeException("todo");
    }

    protected function resolveChronicleDecorator(Chronicler $chronicler, array $config): Chronicler
    {
        $useEventDecorator = $config['options']['use_event_decorator'] ?? false;

        if (!$useEventDecorator) {
            return $chronicler;
        }

        $tracker = $this->container->get($config['tracking']['tracker_id']);

        if ($chronicler instanceof TransactionalChronicler) {
            Assertion::isInstanceOf(TransactionalEventTracker::class, $tracker);

            return new TransactionalEventChronicler($chronicler, $tracker);
        }

        return new Chronicling\EventChronicler($chronicler, $tracker);
    }

    protected function createDatabaseWriteLockDriver(string $driver, bool $useWriteLock): WriteLockStrategy
    {
        if (!$useWriteLock) {
            return new NoWriteLock();
        }

        switch ($driver) {
            case 'pgsql' :
                return $this->container->make(PgsqlWriteLock::class);
            case 'mysql':
                return $this->container->make(MysqlWriteLock::class);
            default:
                throw new RuntimeException("Unavailable write lock strategy for driver $driver");
        }
    }

    protected function attachSubscribers(Chronicler $chronicler, array $config): void
    {
        $subscribers = $config['tracking']['subscribers'] ?? [];

        array_walk($subscribers, function (string $subscriber) use ($chronicler): void {
            $this->container->make($subscriber)->attachToChronicler($chronicler);
        });
    }

    protected function fromChronicler(string $key, $default = null)
    {
        return Arr::get($this->config, $key, $default);
    }
}
