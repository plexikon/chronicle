<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Chronicling\Aggregate\Concerns;

use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateId;
use Plexikon\Chronicle\Support\Contract\Chronicling\Aggregate\AggregateRoot;

trait HasSnapshotStore
{
    protected string $aggregateRoot;
    protected $snapshotStore = null;

    protected function loadFromSnapshotStore(AggregateId $aggregateRootId): ?AggregateRoot
    {
        if (!$this->snapshotStore) {
            return null;
        }

        if ($snapshot = $this->snapshotStore->get($this->aggregateRoot, $aggregateRootId->toString())) {
            $aggregateRoot = $snapshot->aggregateRoot();

            $queryFilter = $this->snapshotStore->queryFilter(
                $this->aggregateRoot,
                $aggregateRootId->toString(),
                $snapshot->lastVersion()
            );

            $streamEvents = $this->retrieveAllEvents($aggregateRootId, $queryFilter);

            $aggregateRoot = $aggregateRoot::reconstituteFromEvents($aggregateRootId, $streamEvents);

            return $aggregateRoot->exists() ? $aggregateRoot : null;
        }

        return null;
    }
}
