<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\Concerns;

use Plexikon\Chronicle\Exception\Assertion;
use Plexikon\Chronicle\Support\Contract\Chronicling\QueryFilter;
use Plexikon\Chronicle\Support\Contract\ProjectionQueryFilter;

trait HasContextFactory
{
    /**
     * @var callable|null
     */
    protected $initCallback = null;

    /**
     * @var string[]
     */
    protected array $streamNames = [];

    /**
     * @var callable|array
     */
    protected $eventHandlers = null;
    protected ?QueryFilter $queryFilter = null;

    public function withCallback(callable $initCallback): void
    {
        Assertion::null($this->initCallback, 'Callback already initialized');

        $this->initCallback = $initCallback;
    }

    /**
     * @param QueryFilter|ProjectionQueryFilter $queryFilter
     */
    public function withQueryFilter(QueryFilter $queryFilter): void
    {
        Assertion::null($this->queryFilter, 'Query filter has already been set');

        $this->queryFilter = $queryFilter;
    }

    public function withStreams(string ...$streamNames): void
    {
        Assertion::notEmpty($streamNames, 'Stream names can not be empty');

        Assertion::count($this->streamNames, 0, 'With All|Streams? already called');

        $this->streamNames = $streamNames;
    }

    public function withAllStreams(): void
    {
        Assertion::count($this->streamNames, 0, 'From stream names already called');

        $this->streamNames = ['all'];
    }

    public function when(array $eventHandlers): void
    {
        Assertion::null($this->eventHandlers, 'Event handlers already set');

        $this->eventHandlers = $eventHandlers;
    }

    public function whenAny(callable $eventHandler): void
    {
        Assertion::null($this->eventHandlers, 'Event handlers already set');

        $this->eventHandlers = $eventHandler;
    }

    public function hasSingleHandler(): bool
    {
        return !is_array($this->eventHandlers);
    }

    public function initCallback(): ?callable
    {
        return $this->initCallback;
    }

    /**
     * @return QueryFilter|ProjectionQueryFilter
     */
    public function queryFilter(): QueryFilter
    {
        return $this->queryFilter;
    }

    /**
     * @return array|callable
     */
    public function eventHandlers()
    {
        return $this->eventHandlers;
    }

    public function validateFactory(): void
    {
        Assertion::notNull($this->queryFilter, 'Query filter not set');

        Assertion::notEmpty($this->streamNames, 'Stream names not set');

        Assertion::notNull($this->eventHandlers, 'Event handlers not set');
    }
}
