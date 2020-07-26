<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Support\Projector;


use Plexikon\Chronicle\Support\Contract\Chronicling\Model\EventStreamProvider;

final class StreamPosition
{
    private array $streamPositions = [];
    private EventStreamProvider $eventStreamProvider;

    public function __construct(EventStreamProvider $eventStreamProvider)
    {
        $this->eventStreamProvider = $eventStreamProvider;
    }

    public function make(array $streamNames): void
    {
        $streamPositions = [];

        foreach ($this->gatherStreamNames($streamNames) as $realStreamName) {
            $streamPositions[$realStreamName] = 0;
        }

        $this->streamPositions = array_merge($streamPositions, $this->streamPositions);
    }

    public function mergeStreamsFromRemote(array $streamPositions): void
    {
        $this->streamPositions = array_merge($this->streamPositions, $streamPositions);
    }

    public function setStreamNameAt(string $streamName, int $position): void
    {
        $this->streamPositions[$streamName] = $position;
    }

    public function reset(): void
    {
        $this->streamPositions = [];
    }

    public function all(): array
    {
        return $this->streamPositions;
    }

    /**
     * @param array $streamNames
     * @return string[]
     */
    private function gatherStreamNames(array $streamNames): array
    {
        return 'all' === $streamNames[0]
            ? $this->eventStreamProvider->allStreamNamesWithoutInternal()
            : $streamNames;
    }
}
