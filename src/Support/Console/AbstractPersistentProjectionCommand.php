<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Support\Console;

use Illuminate\Console\Command;
use Plexikon\Chronicle\Reporter\DomainEvent;
use Plexikon\Chronicle\Support\Contract\Chronicling\Chronicler;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectionProjector;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorFactory;
use Plexikon\Chronicle\Support\Contract\Projector\ProjectorManager;
use Plexikon\Chronicle\Support\Contract\Projector\ReadModel;

/**
 * @method ReadModel readModel()
 * @method ProjectionProjector emit(DomainEvent $event)
 * @method ProjectionProjector linkTo(string $streamName, DomainEvent $event)
 */
final class AbstractPersistentProjectionCommand extends Command
{
    protected bool $usePcntlSignal = true;
    protected string $projectorManagerId = ProjectorManager::class;
    protected string $chroniclerId = Chronicler::class;
    private ?ProjectorManager $projectorManager = null;
    private ?Chronicler $chronicler = null;

    protected function projectorManager(): ProjectorManager
    {
        return $this->projectorManager
            ?? $this->projectorManager = $this->getLaravel()->get($this->projectorManagerId);
    }

    protected function chronicler(): Chronicler
    {
        return $this->chronicler
            ?? $this->chronicler = $this->getLaravel()->get($this->chroniclerId);
    }

    protected function withProjection(string $streamName, ?string $readModel): ProjectorFactory
    {
        if ($this->usePcntlSignal) {
            pcntl_async_signals(true);
        }

        $projection = $readModel
            ? $this->projectorManager()->createReadModelProjection($streamName, $this->getLaravel()->make($readModel))
            : $this->projectorManager()->createProjection($streamName);

        if ($this->usePcntlSignal) {
            pcntl_signal(SIGINT, function () use ($projection, $streamName): void {
                if (null !== $this->output) {
                    $this->warn("Stopping $streamName projection");
                }

                $projection->stop();
            });
        }

        return $projection;
    }
}
