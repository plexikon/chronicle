<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\Concerns;


use Plexikon\Chronicle\Exception\ProjectionNotFound;
use Plexikon\Chronicle\Support\Contract\Chronicling\Model\ProjectionProvider;
use Plexikon\Chronicle\Support\Json;

trait HasReadProjectorManager
{
    protected ProjectionProvider $projectionProvider;

    public function statusOf(string $projectionName): string
    {
        $result = $this->projectionProvider->findByName($projectionName);

        if (!$result) {
            throw ProjectionNotFound::withName($projectionName);
        }

        return $result->status();
    }

    public function streamPositionsOf(string $projectionName): array
    {
        $result = $this->projectionProvider->findByName($projectionName);

        if (!$result) {
            throw ProjectionNotFound::withName($projectionName);
        }

        return Json::decode($result->position());
    }

    public function stateOf(string $projectionName): array
    {
        $result = $this->projectionProvider->findByName($projectionName);

        if (!$result) {
            throw ProjectionNotFound::withName($projectionName);
        }

        return Json::decode($result->state());
    }

    public function filterNamesOf(string ...$projectionNames): array
    {
        return $this->projectionProvider->findByNames(...$projectionNames);
    }

    public function projectionExists(string $projectionName): bool
    {
        return $this->projectionProvider->projectionExists($projectionName);
    }

    protected function assertProjectionNameExists(string $projectionName): void
    {
        if (!$this->projectionExists($projectionName)) {
            throw ProjectionNotFound::withName($projectionName);
        }
    }
}
