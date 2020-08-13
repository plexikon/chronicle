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

    public function withCallback(callable $initCallback): void
    {
        Assertion::null($this->getInit(), 'Callback already initialized');

        $this->factory->put(self::INIT_KEY, $initCallback);
    }

    public function withQueryFilter(QueryFilter $queryFilter): void
    {
        Assertion::null($this->getQueryFilter(), 'Query filter has already been set');

        $this->factory->put(self::QUERY_FILTER_KEY, $queryFilter);
    }

    public function withStreams(string ...$streamNames): void
    {
        Assertion::notEmpty($streamNames, 'Stream names can not be empty');

        Assertion::count($this->getStreamNames(), 0, 'With All|Streams? already called');

        $this->factory->put(self::STREAM_NAMES_KEY, $streamNames);
    }

    public function withAllStreams(): void
    {
        Assertion::count($this->getStreamNames(), 0, 'With stream names already called');

        $this->factory->put(self::STREAM_NAMES_KEY, ['all']);
    }

    public function withKeepRunning(bool $keepRunning): void
    {
        $this->factory->put(self::KEEP_RUNNING_KEY, $keepRunning);
    }

    public function when(array $eventHandlers): void
    {
        Assertion::null($this->getEventHandlers(), 'Event handlers already set');

        $this->factory->put(self::EVENT_HANDLERS_KEY, $eventHandlers);
    }

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

    /**
     * @return QueryFilter|ProjectionQueryFilter|null
     */
    public function getQueryFilter(): ?QueryFilter
    {
        return $this->factory->get(self::QUERY_FILTER_KEY);
    }


    /**
     * @throws AssertionFailedException
     */
    public function validate(): void
    {
        Assertion::notNull($this->getQueryFilter(), 'Query filter not set');

        Assertion::notEmpty($this->getStreamNames(), 'Stream names not set');

        Assertion::notNull($this->getEventHandlers(), 'Event handlers not set');
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
