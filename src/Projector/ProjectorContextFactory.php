<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector;

use Assert\AssertionFailedException;
use Closure;
use Illuminate\Support\Collection;
use Plexikon\Chronicle\Exception\Assertion;
use Plexikon\Chronicle\Support\Contract\Chronicling\QueryFilter;
use Plexikon\Chronicle\Support\Contract\ProjectionQueryFilter;

class ProjectorContextFactory
{
    public const INIT_KEY = 'init_callback';
    public const KEEP_RUNNING_KEY = 'keep_running';
    public const EVENT_HANDLERS_KEY = 'event_handlers';
    public const STREAM_NAMES_KEY = 'stream_names';
    public const QUERY_FILTER_KEY = 'query_filter';

    private Collection $factory;

    public function __construct()
    {
        $this->factory = $this->prepareCollection();
    }

    /**
     * @param object $eventHandlerContext
     */
    public function bindHandlers(object $eventHandlerContext): void
    {
        if ($this->getEventHandlers() instanceof Closure) {
            $this->factory->put(self::EVENT_HANDLERS_KEY,
                Closure::bind($this->getEventHandlers(), $eventHandlerContext)
            );
        } else {
            $bindings = [];
            foreach ($this->getEventHandlers() as $eventName => $eventHandler) {
                $bindings[$eventName] = Closure::bind($eventHandler, $eventHandlerContext);
            }

            $this->factory->put(self::EVENT_HANDLERS_KEY, $bindings);
        }
    }

    /**
     * @param object $eventHandlerContext
     * @return array
     */
    public function bindInit(object $eventHandlerContext): array
    {
        if (is_callable($this->getInit())) {
            $callback = Closure::bind($this->getInit(), $eventHandlerContext);

            $result = $callback();

            $this->factory->put(self::INIT_KEY, $callback);

            return $result;
        }

        return [];
    }

    /**
     * @param callable $initCallback
     * @throws AssertionFailedException checkMe
     */
    public function withCallback(callable $initCallback): void
    {
        Assertion::null($this->getInit(), 'Callback already initialized');

        $this->factory->put(self::INIT_KEY, $initCallback);
    }

    /**
     * @param QueryFilter|ProjectionQueryFilter $queryFilter
     * @throws AssertionFailedException checkMe
     */
    public function withQueryFilter(QueryFilter $queryFilter): void
    {
        Assertion::null($this->getQueryFilter(), 'Query filter has already been set');

        $this->factory->put(self::QUERY_FILTER_KEY, $queryFilter);
    }

    /**
     * @param string ...$streamNames
     * @throws AssertionFailedException
     */
    public function withStreams(string ...$streamNames): void
    {
        Assertion::notEmpty($streamNames, 'Stream names can not be empty');

        Assertion::count($this->getStreamNames(), 0, 'With All|Streams? already called');

        $this->factory->put(self::STREAM_NAMES_KEY, $streamNames);
    }

    /**
     * @throws AssertionFailedException
     */
    public function withAllStreams(): void
    {
        Assertion::count($this->getStreamNames(), 0, 'With stream names already called');

        $this->factory->put(self::STREAM_NAMES_KEY, ['all']);
    }

    /**
     * @param bool $keepRunning
     */
    public function withKeepRunning(bool $keepRunning): void
    {
        $this->factory->put(self::KEEP_RUNNING_KEY, $keepRunning);
    }

    /**
     * @param array $eventHandlers
     * @throws AssertionFailedException checkMe
     */
    public function when(array $eventHandlers): void
    {
        Assertion::null($this->getEventHandlers(), 'Event handlers already set');

        $this->factory->put(self::EVENT_HANDLERS_KEY, $eventHandlers);
    }

    /**
     * @param callable $eventHandler
     * @throws AssertionFailedException checkMe
     */
    public function whenAny(callable $eventHandler): void
    {
        Assertion::null($this->getEventHandlers(), 'Event handlers already set');

        $this->factory->put(self::EVENT_HANDLERS_KEY, $eventHandler);
    }

    public function getInit(): ?Closure
    {
        return $this->factory->get(self::INIT_KEY);
    }

    /**
     * @return null|array|callable
     */
    public function getEventHandlers()
    {
        return $this->factory->get(self::EVENT_HANDLERS_KEY);
    }

    /**
     * @return string[]
     */
    public function getStreamNames(): array
    {
        return $this->factory->get(self::STREAM_NAMES_KEY);
    }

    public function getKeepRunning(): bool
    {
        return $this->factory->get(self::KEEP_RUNNING_KEY);
    }

    public function getQueryFilter(): ?QueryFilter
    {
        return $this->factory->get(self::QUERY_FILTER_KEY);
    }

    private function prepareCollection(): Collection
    {
        return new Collection([
            'init' => null,
            'stream_names' => [],
            'event_handlers' => null,
            'query_filter' => null,
            'keep_running' => false
        ]);
    }
}
