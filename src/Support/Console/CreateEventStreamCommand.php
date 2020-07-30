<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Support\Console;

use Illuminate\Console\Command;
use Plexikon\Chronicle\Stream\Stream;
use Plexikon\Chronicle\Stream\StreamName;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;

final class CreateEventStreamCommand extends Command
{
    protected $signature = 'chronicle:create-stream
                                {stream : stream name}';

    protected $description = 'first commit for one event stream';

    public function handle(): void
    {
        $streamName = new StreamName($this->argument('stream'));

        $chronicle = $this->getLaravel()->get(Chronicler::class);

        if ($chronicle->hasStream($streamName)) {
            $this->error("Stream $streamName already exists ... operation aborted");
            return;
        }

        $chronicle->persistFirstCommit(new Stream($streamName));

        $this->info("Stream $streamName created");
    }
}
