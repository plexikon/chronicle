<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector;

use Plexikon\Chronicle\Exception\Assertion;
use Plexikon\Chronicle\Projector\Concerns\HasProjectorRepository;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorRepository;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorRepositoryDecorator;
use Plexikon\Chronicle\Support\Contract\Projector\ReadModel;

final class ReadModelProjectorRepository implements ProjectorRepositoryDecorator
{
    use HasProjectorRepository;

    protected ProjectorRepository $projectorRepository;
    private ReadModel $readModel;

    public function __construct(ProjectorRepository $projectorRepository, ReadModel $readModel)
    {
        Assertion::notIsInstanceOf($projectorRepository, ProjectorRepositoryDecorator::class);

        $this->projectorRepository = $projectorRepository;
        $this->readModel = $readModel;
    }

    public function prepare(?ReadModel $readModel): void
    {
        $this->projectorRepository->prepare($this->readModel);
    }

    public function persist(): void
    {
        $this->readModel->persist();

        $this->projectorRepository->persist();
    }

    public function reset(): void
    {
        $this->projectorRepository->reset();

        $this->readModel->reset();
    }

    public function delete(bool $deleteEmittedEvents): void
    {
        $this->projectorRepository->delete($deleteEmittedEvents);

        if ($deleteEmittedEvents) {
            $this->readModel->down();
        }
    }
}
