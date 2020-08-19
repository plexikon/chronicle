<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Reporter;

use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use React\Promise\PromiseInterface;

class ReportQuery extends ReportMessage
{
    public function publish($query): PromiseInterface
    {
        $context = $this->tracker->newContext(Reporter::DISPATCH_EVENT);

        $context->withMessage($query);

        $this->publishMessage($context);

        return $context->getPromise();
    }
}
