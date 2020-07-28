<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\Concerns;

use Plexikon\Chronicle\Projector\PersistentProjectorRunner;
use Plexikon\Chronicle\Projector\ProjectionStatusLoader;
use Plexikon\Chronicle\Projector\ProjectorContext;
use Plexikon\Chronicle\Projector\StreamHandler;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Projector\PersistentProjector;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorLock;
use Plexikon\Chronicle\Support\Contract\Projector\ReadModel;

trait HasPersistentProjector
{
    protected ?ReadModel $readModel = null;
    protected ?string $streamName;
    protected ProjectorContext $projectorContext;
    protected ProjectorLock $projectorLock;
    protected Chronicler $chronicler;
    protected MessageAlias $messageAlias;

    public function run(bool $keepRunning = true): void
    {
        /** @var PersistentProjector&static $this */
        $this->projectorContext->setUpProjection(
            $this->createEventHandlerContext($this, $this->projectorContext->currentStreamName)
        );

        $statusHandler = new ProjectionStatusLoader($this->projectorLock);
        $streamHandler = new StreamHandler(
            $this->projectorContext, $this->chronicler, $this->messageAlias, $this->projectorLock
        );

        $runner = new PersistentProjectorRunner(
            $this->projectorContext, $this->projectorLock, $statusHandler, $streamHandler, $this->readModel
        );

        $runner->runProjection($keepRunning);
    }

    public function stop(): void
    {
        $this->projectorLock->stopProjection();
    }

    public function reset(): void
    {
        $this->projectorLock->resetProjection();
    }

    public function delete(bool $deleteEmittedEvents): void
    {
        $this->projectorLock->deleteProjection($deleteEmittedEvents);
    }

    public function getState(): array
    {
        return $this->projectorContext->state->getState();
    }

    public function getStreamName(): string
    {
        return $this->streamName;
    }

    abstract protected function createEventHandlerContext(PersistentProjector $projector,
                                                          ?string &$streamName): object;
}
