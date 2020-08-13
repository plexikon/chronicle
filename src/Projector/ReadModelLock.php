<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector;

use Plexikon\Chronicle\Exception\Assertion;
use Plexikon\Chronicle\Projector\Concerns\HasProjectorLock;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorLock;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorLockDecorator;
use Plexikon\Chronicle\Support\Contract\Projector\ReadModel;

final class ReadModelLock implements ProjectorLockDecorator
{
    use HasProjectorLock;

    protected ProjectorLock $projectorLock;
    private ReadModel $readModel;

    public function __construct(ProjectorLock $projectorLock, ReadModel $readModel)
    {
        Assertion::notIsInstanceOf($projectorLock, ProjectorLockDecorator::class);

        $this->projectorLock = $projectorLock;
        $this->readModel = $readModel;
    }

    public function prepareProjection(ProjectorContext $context, ?ReadModel $readModel = null): void
    {
        $this->projectorLock->prepareProjection($context, $this->readModel);
    }

    public function persistProjection(): void
    {
        $this->readModel->persist();

        $this->projectorLock->persistProjection();
    }

    public function resetProjection(): void
    {
        $this->projectorLock->resetProjection();

        $this->readModel->reset();
    }

    public function deleteProjection(bool $deleteEmittedEvents): void
    {
        $this->projectorLock->deleteProjection($deleteEmittedEvents);

        if ($deleteEmittedEvents) {
            $this->readModel->down();
        }
    }
}
