<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector;

use Plexikon\Chronicle\Exception\Assertion;
use Plexikon\Chronicle\Projector\Concerns\HasProjectorFactory;
use Plexikon\Chronicle\Projector\Pipe\StreamEventHandler;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Projector\Projector;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorFactory;
use Plexikon\Chronicle\Support\Contract\Projector\QueryProjector as BaseQueryProjector;
use Plexikon\Chronicle\Support\Projector\Pipeline;

final class QueryProjector implements BaseQueryProjector, ProjectorFactory
{
    use HasProjectorFactory;

    protected ProjectorContext $context;
    private Chronicler $chronicler;
    private MessageAlias $messageAlias;

    public function __construct(ProjectorContext $projectorContext,
                                Chronicler $chronicler,
                                MessageAlias $messageAlias)
    {
        $this->context = $projectorContext;
        $this->chronicler = $chronicler;
        $this->messageAlias = $messageAlias;
    }

    public function run(bool $keepRunning = true): void
    {
        Assertion::false($keepRunning, 'Query projection can not run in background');

        $this->context->setUpProjection(
            $this->createEventHandlerContext(
                $this, $this->context->currentStreamName
            )
        );

        $this->context->position->make($this->context->streamNames());

        $this->sendThroughPipes();
    }

    private function sendThroughPipes(): void
    {
        (new Pipeline())
            ->through([
                new StreamEventHandler($this->chronicler, $this->messageAlias, null),
            ])
            ->send($this->context)
            ->then(fn(ProjectorContext $context): bool => $context->isStopped);
    }

    public function stop(): void
    {
        $this->context->isStopped = true;
    }

    public function reset(): void
    {
        $this->context->position->reset();

        $callback = $this->context->initCallback();

        if (is_callable($callback)) {
            $callback = $callback();

            if (is_array($callback)) {
                $this->context->state->setState($callback);

                return;
            }
        }

        $this->context->state->resetState();
    }

    public function getState(): array
    {
        return $this->context->state->getState();
    }

    private function createEventHandlerContext(Projector $projector, ?string $streamName): object
    {
        return new class($projector, $streamName) {
            private QueryProjector $query;
            private ?string $streamName;

            public function __construct(QueryProjector $query, ?string &$streamName)
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
