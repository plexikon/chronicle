<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Projector;

use Plexikon\Chronicle\Projector\Concerns\HasPersistentProjector;
use Plexikon\Chronicle\Projector\Concerns\HasProjectorFactory;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Projector\PersistentProjector;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorFactory;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorLock;
use Plexikon\Chronicle\Support\Contract\Projector\ReadModel;
use Plexikon\Chronicle\Support\Contract\Projector\ReadModelProjector as BaseProjector;

final class ReadModelProjector implements BaseProjector, ProjectorFactory
{
    use HasPersistentProjector, HasProjectorFactory;

    protected ?ReadModel $readModel = null;
    protected ProjectorContext $context;
    protected ProjectorLock $lock;
    protected ProjectionStatusRepository $statusLoader;
    protected Chronicler $chronicler;
    protected MessageAlias $messageAlias;
    private string $streamName;

    public function __construct(ProjectorContext $context,
                                ProjectorLock $lock,
                                Chronicler $chronicler,
                                MessageAlias $messageAlias,
                                ReadModel $readModel,
                                string $streamName)
    {
        $this->context = $context;
        $this->lock = $lock;
        $this->chronicler = $chronicler;
        $this->messageAlias = $messageAlias;
        $this->readModel = $readModel;
        $this->streamName = $streamName;
        $this->statusLoader = new ProjectionStatusRepository($this->lock);
    }

    public function readModel(): ReadModel
    {
        return $this->readModel;
    }

    protected function createEventHandlerContext(PersistentProjector $projector, ?string $streamName): object
    {
        return new class($projector, $streamName) {
            private BaseProjector $projector;
            private ?string $streamName;

            public function __construct(BaseProjector $projector, ?string &$streamName)
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
