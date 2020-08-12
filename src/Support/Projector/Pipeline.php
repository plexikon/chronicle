<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Support\Projector;

use Closure;
use Plexikon\Chronicle\Projector\ProjectorContext;
use Plexikon\Chronicle\Support\Contract\Projector\Pipe;
use Throwable;

final class Pipeline
{
    /**
     * @var Pipe[]
     */
    protected array $pipes = [];

    protected ProjectorContext $passable;

    public function send(ProjectorContext $passable): self
    {
        $this->passable = $passable;

        return $this;
    }

    public function through(array $pipes): self
    {
        $this->pipes = $pipes;

        return $this;
    }

    public function then(Closure $destination)
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes()), $this->carry(), $this->prepareDestination($destination)
        );

        return $pipeline($this->passable);
    }

    protected function prepareDestination(Closure $destination)
    {
        return function ($passable) use ($destination) {
            try {
                return $destination($passable);
            } catch (Throwable $e) {
                throw $e;
            }
        };
    }

    protected function carry(): Closure
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                try {
                    return $pipe($passable, $stack);
                } catch (Throwable $e) {
                    throw $e;
                }
            };
        };
    }

    protected function pipes(): array
    {
        return $this->pipes;
    }

    protected function handleCarry($carry)
    {
        return $carry;
    }
}
