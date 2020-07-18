<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Chronicling\Aggregate;

use Illuminate\Contracts\Cache\Store;
use Plexikon\Chronicle\Exception\Assertion;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateCache as BaseAggregateCache;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateId;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateRoot;

final class AggregateCache implements BaseAggregateCache
{
    private int $count = 0;
    private int $beforeFlushing;
    private Store $store;

    public function __construct(Store $store, int $beforeFlushing = 10000)
    {
        $this->store = $store;
        $this->beforeFlushing = $beforeFlushing;
    }

    public function put(AggregateRoot $aggregateRoot): void
    {
        Assertion::true($aggregateRoot->exists(), 'Aggregate root does not exists and can not be cached');

        $this->flushCacheIfNeeded();

        $aggregateId = $aggregateRoot->aggregateId();

        if (!$this->has($aggregateId)) {
            $this->count++;
        }

        $this->store->put($aggregateId->toString(), $aggregateRoot, 0);
    }

    public function get(AggregateId $aggregateId): ?AggregateRoot
    {
        return $this->store->get($aggregateId->toString());
    }

    public function forget(AggregateId $aggregateId): void
    {
        if ($this->has($aggregateId)) {
            $this->store->forget($aggregateId->toString());

            $this->count--;
        }
    }

    public function flush(): bool
    {
        $this->count = 0;

        return $this->store->flush();
    }

    public function has(AggregateId $aggregateId): bool
    {
        return null !== $this->store->get($aggregateId->toString());
    }

    public function count()
    {
        return $this->count;
    }

    private function flushCacheIfNeeded(): void
    {
        if ($this->count === $this->beforeFlushing) {
            $this->flush();
        }
    }
}
