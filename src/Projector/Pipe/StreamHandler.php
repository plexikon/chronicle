<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\Pipe;

use Plexikon\Chronicle\Exception\Assertion;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Projector\ProjectionStatus;
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
    private ?ProjectorLock $lock;

    public function __construct(Chronicler $chronicler, MessageAlias $messageAlias, ?ProjectorLock $lock)
    {
        $this->chronicler = $chronicler;
        $this->messageAlias = $messageAlias;
        $this->lock = $lock;
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

            if ($this->lock) {
                $context->counter->increment();
            }

            $messageHandler = $eventHandlers;

            if (is_array($eventHandlers)) {
                if (!$messageHandler = $this->determineEventHandler($streamEvent, $eventHandlers)) {
                    if ($this->lock) {
                        $this->updatePersistentProjection($context);
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

            if ($this->lock) {
                $this->updatePersistentProjection($context);
            }

            if ($context->isStopped) {
                break;
            }
        }
    }

    private function updatePersistentProjection(ProjectorContext $context): void
    {
        Assertion::notNull($this->lock);

        $persistBlockSize = $context->option->persistBlockSize();

        if ($context->counter->equals($persistBlockSize)) {
            $this->lock->persistProjection();

            $context->counter->reset();

            $context->status = $this->lock->fetchProjectionStatus();

            $keepProjectionRunning = [ProjectionStatus::RUNNING(), ProjectionStatus::IDLE()];

            if (!in_array($context->status, $keepProjectionRunning)) {
                $context->isStopped = true;
            }
        }
    }

    private function determineEventHandler(Message $message, array $eventHandlers): ?callable
    {
        $eventAlias = $this->messageAlias->instanceToAlias($message);

        return $eventHandlers[$eventAlias] ?? null;
    }
}
