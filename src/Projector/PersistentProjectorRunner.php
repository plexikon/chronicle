<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector;

use Plexikon\Chronicle\Support\Contract\Projector\ProjectorRunner;
use Plexikon\Chronicle\Support\Contract\Projector\ReadModel;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorLock as BaseProjectorLock;

final class PersistentProjectorRunner implements ProjectorRunner
{
    private ?ReadModel $readModel;
    private ProjectorContext $projectorContext;
    private BaseProjectorLock $projectorLock;
    private ProjectionStatusLoader $projectionStatusLoader;
    private StreamHandler $streamHandler;

    public function __construct(ProjectorContext $projectorContext,
                                BaseProjectorLock $projectorLock,
                                ProjectionStatusLoader $projectionStatusLoader,
                                StreamHandler $streamHandler,
                                ?ReadModel $readModel)
    {
        $this->projectorContext = $projectorContext;
        $this->projectorLock = $projectorLock;
        $this->projectionStatusLoader = $projectionStatusLoader;
        $this->streamHandler = $streamHandler;
        $this->readModel = $readModel;
    }

    public function runProjection(bool $keepRunning): void
    {
        if ($this->projectionStatusLoader->fromRemote(true, $keepRunning)) {
            return;
        }

        $this->preparePersistentRunner();

        try {
            do {
                $this->streamHandler->handleStreams($this->projectorContext->streamPosition->all());

                $this->updateProjectionOnEventCounter();

                $this->projectorContext->dispatchPCNTLSignal();

                $this->projectionStatusLoader->fromRemote(false, $keepRunning);

                $this->projectorContext->setupStreamPosition();
            } while ($keepRunning && !$this->projectorContext->isProjectionStopped);
        } finally {
            $this->projectorLock->releaseLock();
        }
    }

    private function preparePersistentRunner(): void
    {
        $this->projectorContext->isProjectionStopped = false;

        if (!$this->projectorLock->isProjectionExists()) {
            $this->projectorLock->createProjection();
        }

        $this->projectorLock->acquireLock();

        if ($this->readModel && !$this->readModel->isInitialized()) {
            $this->readModel->initialize();
        }

        $this->projectorContext->setupStreamPosition();

        $this->projectorLock->loadProjectionState();
    }

    private function updateProjectionOnEventCounter(): void
    {
        if ($this->projectorContext->eventCounter->isReset()) {
            usleep($this->projectorContext->options->sleep());

            $this->projectorLock->updateLock();
        } else {
            $this->projectorLock->persistProjection();
        }

        $this->projectorContext->eventCounter->reset();
    }
}
