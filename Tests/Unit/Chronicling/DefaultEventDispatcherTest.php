<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Chronicling;

use Plexikon\Chronicle\Chronicling\DefaultEventDispatcher;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Reporter\ReportEvent;
use Plexikon\Chronicle\Tests\Double\SomeCommand;
use Plexikon\Chronicle\Tests\Unit\TestCase;

final class DefaultEventDispatcherTest extends TestCase
{
    /**
     * @test
     */
    public function it_dispatch_events(): void
    {
        $message = new Message(SomeCommand::fromPayload([]));

        $reporter = $this->prophesize(ReportEvent::class);
        $reporter->publish($message)->shouldBeCalled();

        $dispatcher = new DefaultEventDispatcher($reporter->reveal());

        $dispatcher->dispatch($message);
    }
}
