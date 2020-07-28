<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Support\Contract\Projector;

use Plexikon\Chronicle\Support\Contract\Chronicling\QueryScope;

interface ReadProjectorManager
{
    /**
     * @param string $name
     * @return string
     */
    public function statusOf(string $name): string;

    /**
     * @param string $name
     * @return array
     */
    public function streamPositionsOf(string $name): array;

    /**
     * @param string $name
     * @return array
     */
    public function stateOf(string $name): array;

    /**
     * @param string ...$name
     * @return array
     */
    public function filterNamesOf(string ...$name): array;

    /**
     * @param string $name
     * @return bool
     */
    public function projectionExists(string $name): bool;

    /**
     * @return QueryScope
     */
    public function projectionQueryScope(): QueryScope;
}
