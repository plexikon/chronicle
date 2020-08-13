<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\Concerns;

use Plexikon\Chronicle\Projector\Pipe\PersistentRunner;
use Plexikon\Chronicle\Projector\Pipe\ProjectionReset;
use Plexikon\Chronicle\Projector\Pipe\ProjectionUpdater;
use Plexikon\Chronicle\Projector\Pipe\SignalDispatcher;
use Plexikon\Chronicle\Projector\Pipe\StreamHandler;
use Plexikon\Chronicle\Projector\ProjectionStatusRepository;
use Plexikon\Chronicle\Projector\ProjectorContext;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Projector\PersistentProjector;
use Plexikon\Chronicle\Support\Contract\Projector\Pipe;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorLock;
use Plexikon\Chronicle\Support\Projector\Pipeline;

trait HasPersistentProjector
{
    protected ProjectorContext $context;
    protected ProjectorLock $lock;
    protected ProjectionStatusRepository $statusRepository;
    protected Chronicler $chronicler;
    protected MessageAlias $messageAlias;

    public function run(bool $keepRunning = true): void
    {
        $this->context->factory->withKeepRunning($keepRunning);

        /** @var PersistentProjector&HasPersistentProjector $this */
        $this->context->setUpProjection(
            $this->createEventHandlerContext($this, $this->context->currentStreamName)
        );

        try {
            $pipeline = new Pipeline();
            $pipeline->through($this->getPipes());

            do {
                $isStopped = $pipeline
                    ->send($this->context)
                    ->then(fn(ProjectorContext $context): bool => $context->isStopped);
            } while ($this->context->keepRunning() && !$isStopped);
        } finally {
            $this->lock->releaseLock();
        }
    }

    public function stop(): void
    {
        $this->lock->stopProjection();
    }

    public function reset(): void
    {
        $this->lock->resetProjection();
    }

    public function delete(bool $deleteEmittedEvents): void
    {
        $this->lock->deleteProjection($deleteEmittedEvents);
    }

    public function getState(): array
    {
        return $this->context->state->getState();
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
            new PersistentRunner($this->statusRepository, $this->lock),
            new StreamHandler($this->chronicler, $this->messageAlias, $this->lock),
            new ProjectionUpdater($this->lock),
            new SignalDispatcher(),
            new ProjectionReset($this->statusRepository)
        ];
    }

    abstract protected function createEventHandlerContext(PersistentProjector $projector,
                                                          ?string &$streamName): object;
}
