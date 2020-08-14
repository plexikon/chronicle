<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Support\Projector;

use Plexikon\Chronicle\Support\Contract\Chronicling\Model\EventStreamProvider;

final class StreamPosition
{
    /**
     * @var array<string,int>
     */
    private array $streamsPosition = [];
    private EventStreamProvider $eventStreamProvider;

    public function __construct(EventStreamProvider $eventStreamProvider)
    {
        $this->eventStreamProvider = $eventStreamProvider;
    }

    /**
     * @param string[] $streamNames
     */
    public function make(array $streamNames): void
    {
        $streamsPosition = [];

        foreach ($this->gatherStreamNames($streamNames) as $realStreamName) {
            $streamsPosition[$realStreamName] = 0;
        }

        $this->streamsPosition = array_merge($streamsPosition, $this->streamsPosition);
    }

    /**
     * @param array<string,int> $streamsPosition
     */
    public function mergeStreamsFromRemote(array $streamsPosition): void
    {
        $this->streamsPosition = array_merge($this->streamsPosition, $streamsPosition);
    }

    public function setStreamNameAt(string $streamName, int $position): void
    {
        $this->streamsPosition[$streamName] = $position;
    }

    public function reset(): void
    {
        $this->streamsPosition = [];
    }

    /**
     * @return array<string, int>
     */
    public function all(): array
    {
        return $this->streamsPosition;
    }

    /**
     * @param array<string> $streamNames
     * @return string[]
     */
    private function gatherStreamNames(array $streamNames): array
    {
        return 'all' === $streamNames[0]
            ? $this->eventStreamProvider->allStreamWithoutInternal()
            : $streamNames;
    }
}
