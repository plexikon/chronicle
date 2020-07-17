<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Reporter;

use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use React\Promise\PromiseInterface;
use Throwable;

class ReportQuery extends ReportMessage
{
    public function dispatch($query): PromiseInterface
    {
        $context = $this->tracker->newContext(Reporter::DISPATCH_EVENT);
        $context->withMessage($query);

        try {
            $this->dispatchMessage($context);
        } catch (Throwable $exception) {
            $context->withRaisedException($exception);
        } finally {
            $this->finalizeDispatching($context);
        }

        return $context->getPromise();
    }
}
