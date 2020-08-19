<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector;

use Plexikon\Chronicle\Exception\StreamNotFound;
use Plexikon\Chronicle\Projector\Concerns\HasProjectorRepository;
use Plexikon\Chronicle\Stream\StreamName;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorRepository;
use Plexikon\Chronicle\Support\Contract\Projector\ReadModel;

final class ProjectionRepository implements ProjectorRepository
{
    use HasProjectorRepository;

    protected ProjectorRepository $projectorRepository;
    private Chronicler $chronicler;

    public function __construct(ProjectorRepository $projectorRepository, Chronicler $chronicler)
    {
        $this->projectorRepository = $projectorRepository;
        $this->chronicler = $chronicler;
    }

    public function prepare(?ReadModel $readModel): void
    {
        $this->projectorRepository->prepare(null);
    }

    public function persist(): void
    {
        $this->projectorRepository->persist();
    }

    public function reset(): void
    {
        $this->projectorRepository->reset();

        $this->deleteStream();
    }

    public function delete(bool $deleteEmittedEvents): void
    {
        $this->projectorRepository->delete($deleteEmittedEvents);

        if ($deleteEmittedEvents) {
            $this->deleteStream();
        }
    }

    private function deleteStream(): void
    {
        try {
            $this->chronicler->delete(new StreamName($this->getStreamName()));
        } catch (StreamNotFound $streamNotFound) {
            //
        }
    }
}
