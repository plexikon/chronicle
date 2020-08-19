<?php
declare(strict_types=1);

namespace Plexikon\Chronicle;

use Illuminate\Database\QueryException;
use Plexikon\Chronicle\Exception\QueryFailure;
use Plexikon\Chronicle\Projector\Concerns\HasReadProjectorManager;
use Plexikon\Chronicle\Projector\ProjectionRepository;
use Plexikon\Chronicle\Projector\ProjectionProjector;
use Plexikon\Chronicle\Projector\ProjectionStatus;
use Plexikon\Chronicle\Projector\ProjectorContext;
use Plexikon\Chronicle\Projector\ProjectorOption;
use Plexikon\Chronicle\Projector\ProjectorRepository;
use Plexikon\Chronicle\Projector\QueryProjector;
use Plexikon\Chronicle\Projector\ReadModelRepository;
use Plexikon\Chronicle\Projector\ReadModelProjector;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Chronicling\Model\EventStreamProvider;
use Plexikon\Chronicle\Support\Contract\Chronicling\Model\ProjectionProvider;
use Plexikon\Chronicle\Support\Contract\Chronicling\QueryScope;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorFactory;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorManager as BaseProjectorManager;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorRepository as BaseProjectorRepository;
use Plexikon\Chronicle\Support\Contract\Projector\ReadModel;
use Plexikon\Chronicle\Support\Projector\InMemoryProjectionState;
use Plexikon\Chronicle\Support\Projector\StreamPosition;

final class ProjectorManager implements BaseProjectorManager
{
    use HasReadProjectorManager;

    protected ProjectionProvider $projectionProvider;
    private EventStreamProvider $eventStreamProvider;
    private Chronicler $chronicler;
    private MessageAlias $messageAlias;
    private QueryScope $queryScope;
    private array $options;

    public function __construct(Chronicler $chronicler,
                                EventStreamProvider $eventStreamProvider,
                                ProjectionProvider $projectionProvider,
                                MessageAlias $messageAlias,
                                QueryScope $queryScope,
                                array $options = [])
    {
        $this->chronicler = $chronicler;
        $this->eventStreamProvider = $eventStreamProvider;
        $this->projectionProvider = $projectionProvider;
        $this->messageAlias = $messageAlias;
        $this->queryScope = $queryScope;
        $this->options = $options;
    }

    public function createQuery(array $options = []): ProjectorFactory
    {
        $context = $this->newProjectorContext($options);

        return new QueryProjector($context, $this->chronicler, $this->messageAlias);
    }

    public function createProjection(string $streamName, array $options = []): ProjectorFactory
    {
        $context = $this->newProjectorContext($options);

        $projectorRepository = new ProjectionRepository(
            $this->newProjectorRepository($streamName, $context),
            $this->chronicler
        );

        return new ProjectionProjector(
            $context, $projectorRepository, $this->chronicler,
            $this->messageAlias, $streamName
        );
    }

    public function createReadModelProjection(string $streamName,
                                              ReadModel $readModel,
                                              array $options = []): ProjectorFactory
    {
        $context = $this->newProjectorContext($options);

        $projectorRepository = new ReadModelRepository(
            $this->newProjectorRepository($streamName, $context),
            $readModel
        );

        return new ReadModelProjector(
            $context, $projectorRepository, $this->chronicler,
            $this->messageAlias, $streamName, $readModel
        );
    }

    public function stopProjection(string $name): void
    {
        $this->updateProjectionStatus($name, ProjectionStatus::STOPPING());
    }

    public function resetProjection(string $name): void
    {
        $this->updateProjectionStatus($name, ProjectionStatus::RESETTING());
    }

    public function deleteProjection(string $name, bool $deleteEmittedEvents): void
    {
        $deleteProjectionStatus = $deleteEmittedEvents
            ? ProjectionStatus::DELETING_EMITTED_EVENTS()
            : ProjectionStatus::DELETING();

        $this->updateProjectionStatus($name, $deleteProjectionStatus);
    }

    public function projectionQueryScope(): QueryScope
    {
        return $this->queryScope;
    }

    private function updateProjectionStatus(string $name, ProjectionStatus $projectionStatus): void
    {
        try {
            $result = $this->projectionProvider->updateProjection(
                $name,
                ['status' => $projectionStatus->getValue()]
            );
        } catch (QueryException $exception) {
            throw QueryFailure::fromQueryException($exception);
        }

        if (0 === $result) {
            $this->assertProjectionNameExists($name);
        }
    }

    private function newProjectorContext(array $options = []): ProjectorContext
    {
        return new ProjectorContext(
            new ProjectorOption(empty($options) ? $this->options : $options),
            new StreamPosition($this->eventStreamProvider),
            new InMemoryProjectionState()
        );
    }

    private function newProjectorRepository(string $streamName,
                                            ProjectorContext $context): BaseProjectorRepository
    {
        return new ProjectorRepository($context, $this->projectionProvider, $streamName);
    }
}
