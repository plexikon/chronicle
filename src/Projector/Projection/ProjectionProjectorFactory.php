<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\Projection;

use Plexikon\Chronicle\Projector\Concerns\HasProjector;
use Plexikon\Chronicle\Projector\Concerns\HasProjectorFactory;
use Plexikon\Chronicle\Projector\ProjectorContext;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorFactory;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorLock;

final class ProjectionProjectorFactory implements ProjectorFactory
{
    use HasProjectorFactory, HasProjector;

    protected ProjectorContext $projectorContext;
    private ProjectorLock $projectorLock;
    private Chronicler $chronicler;
    private MessageAlias $chronicleAlias;
    private string $streamName;

    public function __construct(ProjectorContext $projectorContext,
                                ProjectorLock $projectorLock,
                                Chronicler $chronicler,
                                MessageAlias $messageAlias,
                                string $streamName)
    {
        $this->projectorContext = $projectorContext;
        $this->projectorLock = $projectorLock;
        $this->chronicler = $chronicler;
        $this->chronicleAlias = $messageAlias;
        $this->streamName = $streamName;
    }

    public function run(bool $keepRunning = true): void
    {
        $this->projector = new ProjectionProjector(
            $this->projectorContext,
            $this->projectorLock,
            $this->chronicler,
            $this->chronicleAlias,
            $this->streamName
        );

        $this->projector->run($keepRunning);
    }
}
