<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector;

use Plexikon\Chronicle\Exception\Assertion;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Stream\StreamName;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorLock as BaseProjectorLock;
use Plexikon\Chronicle\Support\Projector\StreamEventIterator;

final class StreamHandler
{
    private bool $isProjectorPersistent;
    private ProjectorContext $projectorContext;
    private Chronicler $chronicler;
    private MessageAlias $messageAlias;
    private ?BaseProjectorLock $projectorLock;

    public function __construct(ProjectorContext $projectorContext,
                                Chronicler $chronicler,
                                MessageAlias $chronicleAlias,
                                ?BaseProjectorLock $projectorLock)
    {
        $this->projectorContext = $projectorContext;
        $this->chronicler = $chronicler;
        $this->messageAlias = $chronicleAlias;
        $this->projectorLock = $projectorLock;
        $this->isProjectorPersistent = $projectorLock instanceof BaseProjectorLock;
    }

    public function handleStreams(array $streamPositions): void
    {
        foreach ($this->retrieveStreams($streamPositions) as $streamName => $events) {
            $this->projectorContext->currentStreamName = $streamName;

            $this->projectStreamEventsWithHandlers(
                $events,
                $this->projectorContext->eventHandlers()
            );
        }
    }

    private function retrieveStreams(array $streamPositions): array
    {
        $eventStreams = [];
        $queryFilter = $this->projectorContext->queryFilter();

        foreach ($streamPositions as $streamName => $position) {
            $queryFilter->setCurrentPosition($position + 1);

            $events = $this->chronicler->retrieveWithQueryFilter(
                new StreamName($streamName), $queryFilter
            );

            $eventStreams[$streamName] = new StreamEventIterator($events);
        }

        return $eventStreams;
    }

    /**
     * @param StreamEventIterator $streamEvents
     * @param array|callable $eventHandlers
     */
    private function projectStreamEventsWithHandlers(StreamEventIterator $streamEvents, $eventHandlers): void
    {
        foreach ($streamEvents as $key => $streamEvent) {
            $this->projectorContext->dispatchPCNTLSignal();

            $this->projectorContext->streamPosition->setStreamNameAt(
                $this->projectorContext->currentStreamName,
                $key
            );

            if ($this->isProjectorPersistent) {
                $this->projectorContext->eventCounter->increment();
            }

            $messageHandler = $eventHandlers;

            if (is_array($eventHandlers)) {
                if (!$messageHandler = $this->determineEventHandler($streamEvent, $eventHandlers)) {
                    if ($this->isProjectorPersistent) {
                        $this->updatePersistentProjection();
                    }

                    if ($this->projectorContext->isProjectionStopped) {
                        break;
                    }

                    continue;
                }
            }

            $projectionState = $messageHandler(
                $this->projectorContext->state->getState(), $streamEvent->eventWithHeaders()
            );

            $this->projectorContext->state->setState($projectionState);

            if ($this->isProjectorPersistent) {
                $this->updatePersistentProjection();
            }

            if ($this->projectorContext->isProjectionStopped) {
                break;
            }
        }
    }

    private function updatePersistentProjection(): void
    {
        Assertion::true($this->isProjectorPersistent);

        $persistBlockSize = $this->projectorContext->options->persistBlockSize();

        if ($this->projectorContext->eventCounter->equals($persistBlockSize)) {
            $this->projectorLock->persistProjection();

            $this->projectorContext->eventCounter->reset();

            $this->projectorContext->status = $this->projectorLock->fetchProjectionStatus();

            $keepProjectionRunning = [ProjectionStatus::RUNNING(), ProjectionStatus::IDLE()];

            if (!in_array($this->projectorContext->status, $keepProjectionRunning)) {
                $this->projectorContext->isProjectionStopped = true;
            }
        }
    }

    /**
     * @param Message $message
     * @param array $eventHandlers
     * @return callable|null
     */
    private function determineEventHandler(Message $message, array $eventHandlers): ?callable
    {
        $eventAlias = $this->messageAlias->instanceToAlias($message);

        return $eventHandlers[$eventAlias] ?? null;
    }
}
