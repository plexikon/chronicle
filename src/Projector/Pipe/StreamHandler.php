<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\Pipe;

use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Projector\ProjectorContext;
use Plexikon\Chronicle\Stream\StreamName;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Projector\Pipe;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorLock;
use Plexikon\Chronicle\Support\Projector\StreamEventIterator;

final class StreamHandler implements Pipe
{
    private Chronicler $chronicler;
    private MessageAlias $messageAlias;
    private ?ProjectorLock $projectorLock;

    public function __construct(Chronicler $chronicler, MessageAlias $messageAlias, ?ProjectorLock $lock)
    {
        $this->chronicler = $chronicler;
        $this->messageAlias = $messageAlias;
        $this->projectorLock = $lock;
    }

    public function __invoke(ProjectorContext $context, callable $next)
    {
        $streams = $this->retrieveStreams($context);

        foreach ($streams as $streamName => $events) {
            $context->currentStreamName = $streamName;

            $this->handleStreamEvents($context, $events, $context->eventHandlers());
        }

        return $next($context);
    }

    private function retrieveStreams(ProjectorContext $context): array
    {
        $eventStreams = [];
        $queryFilter = $context->queryFilter();

        foreach ($context->position->all() as $streamName => $position) {
            $queryFilter->setCurrentPosition($position + 1);

            $events = $this->chronicler->retrieveWithQueryFilter(
                new StreamName($streamName), $queryFilter
            );

            $eventStreams[$streamName] = new StreamEventIterator($events);
        }

        return $eventStreams;
    }

    private function handleStreamEvents(ProjectorContext $context, StreamEventIterator $streamEvents, $eventHandlers): void
    {
        foreach ($streamEvents as $key => $streamEvent) {
            $context->dispatchSignal();

            $context->position->setStreamNameAt($context->currentStreamName, $key);

            if ($this->projectorLock) {
                $context->counter->increment();
            }

            $messageHandler = $eventHandlers;

            if (is_array($eventHandlers)) {
                if (!$messageHandler = $this->determineEventHandler($streamEvent, $eventHandlers)) {
                    if ($this->projectorLock) {
                        $this->projectorLock->updateProjectionOnCounter();
                    }

                    if ($context->isStopped) {
                        break;
                    }

                    continue;
                }
            }

            $projectionState = $messageHandler(
                $context->state->getState(), $streamEvent->eventWithHeaders()
            );

            $context->state->setState($projectionState);

            if ($this->projectorLock) {
                $this->projectorLock->updateProjectionOnCounter();
            }

            if ($context->isStopped) {
                break;
            }
        }
    }

    private function determineEventHandler(Message $message, array $eventHandlers): ?callable
    {
        $eventAlias = $this->messageAlias->instanceToAlias($message);

        return $eventHandlers[$eventAlias] ?? null;
    }
}
