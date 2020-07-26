<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector\ReadModel;

use Plexikon\Chronicle\Projector\Concerns\HasPersistentProjector;
use Plexikon\Chronicle\Projector\ProjectorContext;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Projector\PersistentProjector;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorLock;
use Plexikon\Chronicle\Support\Contract\Projector\ReadModel;
use Plexikon\Chronicle\Support\Contract\Projector\ReadModelProjector as BaseProjector;

final class ReadModelProjector implements BaseProjector
{
    use HasPersistentProjector;

    protected ?ReadModel $readModel = null;
    protected ?string $streamName;
    protected ProjectorContext $projectorContext;
    protected ProjectorLock $projectorLock;
    protected Chronicler $chronicler;
    protected MessageAlias $messageAlias;

    public function __construct(ProjectorContext $projectorContext,
                                ProjectorLock $projectorLock,
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

    public function readModel(): ReadModel
    {
        return $this->readModel;
    }

    protected function createEventHandlerContext(PersistentProjector $projector, ?string $streamName): object
    {
        return new class($projector, $streamName) {
            private ReadModelProjector $projector;
            private ?string $streamName;

            public function __construct(ReadModelProjector $projector, ?string &$streamName)
            {
                $this->projector = $projector;
                $this->streamName = &$streamName;
            }

            public function stop(): void
            {
                $this->projector->stop();
            }

            public function readModel(): ReadModel
            {
                return $this->projector->readModel();
            }

            public function streamName(): ?string
            {
                return $this->streamName;
            }
        };
    }
}
