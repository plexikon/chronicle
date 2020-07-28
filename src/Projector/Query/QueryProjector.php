<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\Query;
use Plexikon\Chronicle\Exception\Assertion;
use Plexikon\Chronicle\Projector\ProjectorContext;
use Plexikon\Chronicle\Projector\StreamHandler;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Projector\Projector;
use Plexikon\Chronicle\Support\Contract\Projector\QueryProjector as BaseQueryProjector;

final class QueryProjector implements BaseQueryProjector
{
    private ProjectorContext $projectorContext;
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

        $this->projectorContext->setupStreamPosition();

        $streamHandler = new StreamHandler(
            $this->projectorContext, $this->chronicler, $this->messageAlias, null
        );

        $streamHandler->handleStreams($this->projectorContext->streamPosition->all());
    }

    public function stop(): void
    {
        $this->projectorContext->isProjectionStopped = true;
    }

    public function reset(): void
    {
        $this->projectorContext->streamPosition->reset();

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
