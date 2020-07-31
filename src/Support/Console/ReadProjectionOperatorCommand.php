<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Support\Console;

use Illuminate\Console\Command;
use Plexikon\Chronicle\Exception\RuntimeException;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorManager;

class ReadProjectionOperatorCommand extends Command
{
    protected $signature = 'chronicle:find
                                {stream : stream name}
                                {field : available field (state position status)}';

    protected $description = 'Find status, stream position or state by projection stream name';

    private ProjectorManager $projector;

    public function handle(): void
    {
        $this->projector = $this->getLaravel()->get(ProjectorManager::class);

        [$streamName, $fieldName] = $this->determineArguments();

        $result = $this->fetchProjectionByField($streamName, $fieldName);
        $result = empty($result) ? 'EMPTY' : json_encode($result);

        $this->info("{$fieldName} for stream $streamName is $result");
    }

    protected function fetchProjectionByField(string $streamName, string $fieldName): array
    {
        switch ($fieldName) {
            case 'state':
                return $this->projector->stateOf($streamName);
            case 'position':
                return $this->projector->streamPositionsOf($streamName);
            case 'status':
                return [$this->projector->statusOf($streamName)];
            default:
                throw new RuntimeException("Invalid field name $fieldName");
        }
    }

    protected function determineArguments(): array
    {
        $name = $this->argument('stream') ?? null;
        assert(null !== $name || !empty($name));

        $field = $this->argument('field');
        assert(in_array($field, ['state', 'position', 'status']));

        return [$name, $field];
    }
}
