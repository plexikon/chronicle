<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\Concerns;

use Plexikon\Chronicle\Projector\Pipe\PersistentRunner;
use Plexikon\Chronicle\Projector\Pipe\ProjectionReset;
use Plexikon\Chronicle\Projector\Pipe\ProjectionUpdater;
use Plexikon\Chronicle\Projector\Pipe\SignalDispatcher;
use Plexikon\Chronicle\Projector\Pipe\StreamHandler;
use Plexikon\Chronicle\Projector\ProjectorContext;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Projector\PersistentProjector;
use Plexikon\Chronicle\Support\Contract\Projector\Pipe;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorRepository;
use Plexikon\Chronicle\Support\Projector\Pipeline;

trait HasPersistentProjector
{
    protected ProjectorContext $projectorContext;
    protected ProjectorRepository $projectorRepository;
    protected Chronicler $chronicler;
    protected MessageAlias $messageAlias;

    public function run(bool $keepRunning = true): void
    {
        $this->projectorContext->factory->withKeepRunning($keepRunning);

        /** @var PersistentProjector&static $this */
        $this->projectorContext->setUpProjection(
            $this->createEventHandlerContext($this, $this->projectorContext->currentStreamName)
        );

        try {
            $pipeline = new Pipeline();
            $pipeline->through($this->getPipes());

            do {
                $isStopped = $pipeline
                    ->send($this->projectorContext)
                    ->then(fn(ProjectorContext $context): bool => $context->isStopped);
            } while ($this->projectorContext->keepRunning() && !$isStopped);
        } finally {
            $this->projectorRepository->releaseLock();
        }
    }

    public function stop(): void
    {
        $this->projectorRepository->stop();
    }

    public function reset(): void
    {
        $this->projectorRepository->reset();
    }

    public function delete(bool $deleteEmittedEvents): void
    {
        $this->projectorRepository->delete($deleteEmittedEvents);
    }

    public function getState(): array
    {
        return $this->projectorContext->state->getState();
    }

    public function getStreamName(): string
    {
        return $this->streamName;
    }

    /**
     * @return Pipe[]
     */
    protected function getPipes(): array
    {
        return [
            new PersistentRunner($this->projectorRepository),
            new StreamHandler($this->chronicler, $this->messageAlias, $this->projectorRepository),
            new ProjectionUpdater($this->projectorRepository),
            new SignalDispatcher(),
            new ProjectionReset($this->projectorRepository)
        ];
    }

    abstract protected function createEventHandlerContext(PersistentProjector $projector,
                                                          ?string &$streamName): object;
}
