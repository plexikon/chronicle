<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Support\Console;

use Illuminate\Console\Command;
use Plexikon\Chronicle\Exception\ProjectionNotFound;
use Plexikon\Chronicle\Exception\RuntimeException;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorManager;

class PersistentProjectionOperatorCommand extends Command
{
    protected const OPERATIONS_AVAILABLE = ['stop', 'reset', 'delete', 'deleteIncl'];

    protected $signature = 'chronicle:project
                                {op : operation on projection (available: stop reset delete deleteIncl)}
                                {stream : stream name}';

    protected $description = 'Stop reset delete ( with/out emitted events ) one projection by stream name';

    protected ProjectorManager $projector;

    public function handle(): void
    {
        [$streamName, $operation] = $this->determineArguments();

        $this->assertProjectionOperationExists($operation);

        if (!$this->isOperationOnStreamConfirmed($streamName, $operation)) {
            return;
        }

        $this->processProjection($streamName, $operation);

        $this->info("Operation $operation on stream $streamName successful");
    }

    protected function processProjection(string $streamName, string $operation): void
    {
        switch ($operation) {
            case 'stop':
                $this->projector()->stopProjection($streamName);
                break;
            case 'reset':
                $this->projector()->resetProjection($streamName);
                break;
            case 'delete':
                $this->projector()->deleteProjection($streamName, false);
                break;
            case 'deleteIncl':
                $this->projector()->deleteProjection($streamName, true);
                break;
        }
    }

    protected function isOperationOnStreamConfirmed(string $streamName, string $operation): bool
    {
        try {
            $projectionStatus = $this->projector()->statusOf($streamName);
        } catch (ProjectionNotFound $projectionNotFound) {
            $this->error("Projection no found with stream name $streamName ... operation aborted");
            return false;
        }

        $this->warn("Status of $streamName projection is $projectionStatus");

        if (!$this->confirm("Are you sure you want to $operation stream name $streamName")) {
            $this->warn("Operation $operation on stream $streamName aborted");
            return false;
        }

        return true;
    }

    protected function determineArguments(): array
    {
        return [$this->argument('stream'), $this->argument('op')];
    }

    protected function assertProjectionOperationExists(string $operation): void
    {
        if (!in_array($operation, self::OPERATIONS_AVAILABLE)) {
            throw new RuntimeException("Invalid operation $operation");
        }
    }

    protected function projector(): ProjectorManager
    {
        return $this->projector ?? $this->projector = $this->getLaravel()->get(ProjectorManager::class);
    }
}
