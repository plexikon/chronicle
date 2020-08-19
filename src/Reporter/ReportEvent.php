<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Reporter;

use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;

class ReportEvent extends ReportMessage
{
    public function publish($event): void
    {
        $context = $this->tracker->newContext(Reporter::DISPATCH_EVENT);

        $context->withMessage($event);

        $this->publishMessage($context);
    }
}
