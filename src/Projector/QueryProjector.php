<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector;

use Plexikon\Chronicle\Exception\Assertion;
use Plexikon\Chronicle\Projector\Concerns\HasProjectorFactory;
use Plexikon\Chronicle\Projector\Pipe\HandleStreamEvent;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorFactory;
use Plexikon\Chronicle\Support\Contract\Projector\QueryProjector as BaseQueryProjector;
use Plexikon\Chronicle\Support\Projector\Pipeline;

final class QueryProjector implements BaseQueryProjector, ProjectorFactory
{
    use HasProjectorFactory;

    protected ProjectorContext $projectorContext;
    private Chronicler $chronicler;
    private MessageAlias $messageAlias;

    public function __construct(ProjectorContext $projectorContext,
                                Chronicler $chronicler,
                                MessageAlias $messageAlias)
    {
        $this->projectorContext = $projectorContext;
        $this->chronicler = $chronicler;
        $this->messageAlias = $messageAlias;
    }

    public function run(bool $keepRunning = true): void
    {
        Assertion::false($keepRunning, 'Query projection can not run in background');

        $this->projectorContext->setUpProjection(
            $this->createEventHandlerContext(
                $this, $this->projectorContext->currentStreamName
            )
        );

        $this->projectorContext->position->make($this->projectorContext->streamNames());

        $this->sendThroughPipes();
    }

    private function sendThroughPipes(): void
    {
        (new Pipeline())
            ->through([
                new HandleStreamEvent($this->chronicler, $this->messageAlias, null),
            ])
            ->send($this->projectorContext)
            ->then(fn(ProjectorContext $context): bool => $context->isStopped);
    }

    public function stop(): void
    {
        $this->projectorContext->isStopped = true;
    }

    public function reset(): void
    {
        $this->projectorContext->position->reset();

        $callback = $this->projectorContext->initCallback();

        if (is_callable($callback)) {
            $callback = $callback();

            if (is_array($callback)) {
                $this->projectorContext->state->setState($callback);

                return;
            }
        }

        $this->projectorContext->state->resetState();
    }

    public function getState(): array
    {
        return $this->projectorContext->state->getState();
    }

    private function createEventHandlerContext(BaseQueryProjector $projector, ?string $streamName): object
    {
        return new class($projector, $streamName) {
            private BaseQueryProjector $query;
            private ?string $streamName;

            public function __construct(BaseQueryProjector $query, ?string &$streamName)
            {
                $this->query = $query;
                $this->streamName = &$streamName;
            }

            public function stop(): void
            {
                $this->query->stop();
            }

            public function streamName(): ?string
            {
                return $this->streamName;
            }
        };
    }
}
