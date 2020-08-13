<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector;

use Plexikon\Chronicle\Exception\Assertion;
use Plexikon\Chronicle\Exception\StreamNotFound;
use Plexikon\Chronicle\Projector\Concerns\HasProjectorLock;
use Plexikon\Chronicle\Stream\StreamName;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorLock;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorLockDecorator;

final class ProjectionLock implements ProjectorLockDecorator
{
    use HasProjectorLock;

    protected ProjectorLock $projectorLock;
    private Chronicler $chronicler;

    public function __construct(ProjectorLock $projectorLock, Chronicler $chronicler)
    {
        Assertion::notIsInstanceOf($projectorLock, ProjectorLockDecorator::class);

        $this->projectorLock = $projectorLock;
        $this->chronicler = $chronicler;
    }

    public function persistProjection(): void
    {
        $this->projectorLock->persistProjection();
    }

    public function resetProjection(): void
    {
        $this->projectorLock->resetProjection();

        $this->deleteStream();
    }

    public function deleteProjection(bool $deleteEmittedEvents): void
    {
        $this->projectorLock->deleteProjection($deleteEmittedEvents);

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
