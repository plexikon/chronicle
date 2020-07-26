<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\ReadModel;

use Plexikon\Chronicle\Projector\Concerns\HasProjector;
use Plexikon\Chronicle\Projector\Concerns\HasProjectorFactory;
use Plexikon\Chronicle\Projector\ProjectorContext;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorFactory;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorLockDecorator;
use Plexikon\Chronicle\Support\Contract\Projector\ReadModel;

final class ReadModelProjectorFactory implements ProjectorFactory
{
    use HasProjectorFactory, HasProjector;

    protected ProjectorContext $projectorContext;
    private ProjectorLockDecorator $projectorLock;
    private Chronicler $chronicler;
    private MessageAlias $messageAlias;
    private ReadModel $readModel;
    private string $streamName;

    public function __construct(ProjectorContext $projectorContext,
                                ProjectorLockDecorator $projectorLock,
                                Chronicler $chronicler,
                                MessageAlias $messageAlias,
                                ReadModel $readModel,
                                string $streamName)
    {
        $this->projectorContext = $projectorContext;
        $this->projectorLock = $projectorLock;
        $this->chronicler = $chronicler;
        $this->messageAlias = $messageAlias;
        $this->readModel = $readModel;
        $this->streamName = $streamName;
    }

    public function run(bool $keepRunning = true): void
    {
        $this->projector = new ReadModelProjector(
            $this->projectorContext,
            $this->projectorLock,
            $this->chronicler,
            $this->messageAlias,
            $this->readModel,
            $this->streamName
        );

        $this->projector->run($keepRunning);
    }
}
