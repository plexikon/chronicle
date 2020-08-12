<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector;

use Closure;
use Illuminate\Support\Collection;
use Plexikon\Chronicle\Exception\Assertion;
use Plexikon\Chronicle\Support\Contract\Chronicling\QueryFilter;
use Plexikon\Chronicle\Support\Contract\ProjectionQueryFilter;

final class ProjectorContextFactory
{
    private Collection $factory;

    public function __construct()
    {
        $this->factory = $this->prepareCollection();
    }

    public function bindHandlers(object $eventContext): void
    {
        if ($this->getEventHandlers() instanceof Closure) {
            $this->factory['event_handlers'] = Closure::bind($this->factory['event_handlers'], $eventContext);
        } else {
            foreach ($this->factory['event_handlers'] as $eventName => $eventHandler) {
                $this->factory['event_handlers'][$eventName] = Closure::bind($eventHandler, $eventContext);
            }
        }
    }

    public function bindInit(object $eventContext): array
    {
        if (is_callable($this->getInit())) {
            $callback = Closure::bind($this->factory['init'], $eventContext);

            $result = $callback();

            $this->factory['init'] = $callback;

            return $result;
        }

        return [];
    }

    public function get(string $key)
    {
        Assertion::keyExists($this->factory->toArray(), $key);

        return $this->factory->get($key);
    }

    public function withCallback(callable $initCallback): void
    {
        Assertion::null($this->getInit(), 'Callback already initialized');

        $this->factory->put('init', $initCallback);
    }

    /**
     * @param QueryFilter|ProjectionQueryFilter $queryFilter
     */
    public function withQueryFilter(QueryFilter $queryFilter): void
    {
        Assertion::null($this->getQueryFilter(), 'Query filter has already been set');

        $this->factory->put('query_filter', $queryFilter);
    }

    public function withStreams(string ...$streamNames): void
    {
        Assertion::notEmpty($streamNames, 'Stream names can not be empty');

        Assertion::count($this->getStreamNames(), 0, 'With All|Streams? already called');

        $this->factory->put('stream_names', $streamNames);
    }

    public function withAllStreams(): void
    {
        Assertion::count($this->getStreamNames(), 0, 'With stream names already called');

        $this->factory->put('stream_names', ['all']);
    }

    public function withKeepRunning(bool $keepRunning): void
    {
        $this->factory->put('keep_running', $keepRunning);
    }

    public function when(array $eventHandlers): void
    {
        Assertion::null($this->factory->get('event_handlers'), 'Event handlers already set');

        $this->factory->put('event_handlers', $eventHandlers);
    }

    public function whenAny(callable $eventHandler): void
    {
        Assertion::null($this->getEventHandlers(), 'Event handlers already set');

        $this->factory->put('event_handlers', $eventHandler);
    }

    public function getInit(): ?Closure
    {
        return $this->factory->get('init');
    }

    /**
     * @return array|callable
     */
    public function getEventHandlers()
    {
        return $this->factory->get('event_handlers');
    }

    public function getStreamNames(): array
    {
        return $this->factory->get('stream_names');
    }

    public function getKeepRunning(): bool
    {
        return $this->factory->get('keep_running');
    }

    public function getQueryFilter(): QueryFilter
    {
        return $this->factory->get('query_filter');
    }

    private function prepareCollection(): Collection
    {
        return new Collection([
            'init' => null,
            'stream_names' => [],
            'event_handlers' => [],
            'query_filter' => null,
            'keep_running' => false
        ]);
    }
}
