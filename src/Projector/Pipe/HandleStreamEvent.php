<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\Pipe;

use Generator;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Projector\ProjectionStatus;
use Plexikon\Chronicle\Projector\ProjectorContext;
use Plexikon\Chronicle\Stream\StreamName;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Projector\Pipe;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorRepository;
use Plexikon\Chronicle\Support\Projector\StreamEventIterator;

final class HandleStreamEvent implements Pipe
{
    private Chronicler $chronicler;
    private MessageAlias $messageAlias;
    private ?ProjectorRepository $projectorRepository;

    public function __construct(Chronicler $chronicler,
                                MessageAlias $messageAlias,
                                ?ProjectorRepository $projectorRepository)
    {
        $this->chronicler = $chronicler;
        $this->messageAlias = $messageAlias;
        $this->projectorRepository = $projectorRepository;
    }

    public function __invoke(ProjectorContext $context, callable $next)
    {
        $streams = $this->retrieveStreams($context);

        foreach ($streams as $streamName => $events) {
            $context->currentStreamName = $streamName;

            $this->handleStreamEvents($events, $context);
        }

        return $next($context);
    }

    private function retrieveStreams(ProjectorContext $context): Generator
    {
        $queryFilter = $context->queryFilter();

        foreach ($context->position->all() as $streamName => $position) {
            $queryFilter->setCurrentPosition($position + 1);

            $events = $this->chronicler->retrieveWithQueryFilter(
                new StreamName($streamName), $queryFilter
            );

            yield from [$streamName => new StreamEventIterator($events)];
        }
    }

    private function handleStreamEvents(StreamEventIterator $streamEvents, ProjectorContext $context): void
    {
        $eventHandlers = $context->eventHandlers();

        foreach ($streamEvents as $key => $streamEvent) {
            $context->dispatchSignal();

            $context->position->setStreamNameAt($context->currentStreamName, $key);

            if ($this->projectorRepository) {
                $context->counter->increment();
            }

            $messageHandler = $eventHandlers;

            if (is_array($eventHandlers)) {
                if (!$messageHandler = $this->determineEventHandler($streamEvent, $eventHandlers)) {
                    if ($this->projectorRepository) {
                        $this->persistOnReachedCounter($context);
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

            if ($this->projectorRepository) {
                $this->persistOnReachedCounter($context);
            }

            if ($context->isStopped) {
                break;
            }
        }
    }

    private function persistOnReachedCounter(ProjectorContext $context): void
    {
        $persistBlockSize = $context->option->persistBlockSize();

        if ($context->counter->equals($persistBlockSize)) {
            $this->projectorRepository->persist();

            $context->counter->reset();

            $context->status = $this->projectorRepository->loadStatus();

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
