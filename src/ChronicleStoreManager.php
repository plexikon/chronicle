<?php
declare(strict_types=1);

namespace Plexikon\Chronicle;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Plexikon\Chronicle\Chronicling\TransactionalEventChronicler;
use Plexikon\Chronicle\Chronicling\WriteLock\NoWriteLock;
use Plexikon\Chronicle\Exception\RuntimeException;
use Plexikon\Chronicle\Support\Connection\StreamEventLoader;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Chronicling\EventChronicler;
use Plexikon\Chronicle\Support\Contract\Chronicling\Model\EventStreamProvider;
use Plexikon\Chronicle\Support\Contract\Chronicling\TransactionalChronicler;
use Plexikon\Chronicle\Tracker\TrackingChronicle;
use Plexikon\Chronicle\Tracker\TransactionalTrackingChronicle;

class ChronicleStoreManager
{
    protected array $config;
    protected Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->config = $container->get(Repository::class)->get('chronicler');
    }

    public function create(string $driver): Chronicler
    {
        $config = $this->fromChronicler("connections.$driver");

        if (!is_array($config)) {
            throw new RuntimeException("Chronicle store driver $driver not found");
        }

        return $this->resolveChronicleStore($driver, $config);
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

    protected function pgsqlChronicleDriver(array $config): Chronicler
    {
        return new PgsqlChronicler(
            $this->container['db']->connection($config['connection']),
            $this->container->get(EventStreamProvider::class),
            $this->container->make($config['strategy']),
            new NoWriteLock(),
            $this->container->make(StreamEventLoader::class),
            $config['options']['disable_transaction'] ?? false
        );
    }

    protected function inMemoryChronicleDriver(array $config): Chronicler
    {
        throw new RuntimeException("todo");
    }

    protected function resolveChronicleDecorator(Chronicler $chronicler, array $config): Chronicler
    {
        $useEventDecorator = $config['options']['use_event_decorator'] ?? false;

        if (!$useEventDecorator || $chronicler instanceof EventChronicler) {
            return $chronicler;
        }

        $tracker = $config['options']['tracker_id'] ?? null;

        if (is_string($tracker)) {
            $tracker = $this->container->get($tracker);
        }

        return $chronicler instanceof TransactionalChronicler
            ? new TransactionalEventChronicler($chronicler, $tracker ?? new TransactionalTrackingChronicle())
            : new Chronicling\EventChronicler($chronicler, $tracker ?? new TrackingChronicle());
    }

    protected function fromChronicler(string $key, $default = null)
    {
        return Arr::get($this->config, $key, $default);
    }
}
