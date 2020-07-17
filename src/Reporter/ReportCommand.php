<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Reporter;

use Throwable;

class ReportCommand extends ReportMessage
{
    private array $queue = [];
    private bool $isDispatching = false;

    public function dispatch($message): void
    {
        $this->queue[] = $message;

        if (!$this->isDispatching) {
            $this->isDispatching = true;

            try {
                while ($command = array_shift($this->queue)) {
                    $context = $this->tracker->newContext(self::DISPATCH_EVENT);
                    $context->withMessage($command);

                    try {
                        $this->dispatchMessage($context);
                    } catch (Throwable $exception) {
                        $context->withRaisedException($exception);
                    } finally {
                        $this->finalizeDispatching($context);
                    }
                }
                $this->isDispatching = false;
            } catch (Throwable $exception) {
                $this->isDispatching = false;

                throw $exception;
            }
        }
    }
}
