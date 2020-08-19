<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Reporter\Subscribers;

use Generator;
use Plexikon\Chronicle\Exception\UnauthorizedException;
use Plexikon\Chronicle\Messaging\Message;
use Plexikon\Chronicle\Reporter\Subscribers\RouteGuardSubscriber;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageAlias;
use Plexikon\Chronicle\Support\Contract\Messaging\MessageHeader;
use Plexikon\Chronicle\Support\Contract\Reporter\AuthorizationService;
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use Plexikon\Chronicle\Tracker\TrackingMessage;
use stdClass;

final class RouteGuardSubscriberTest extends TestCase
{
    /**
     * @test
     * @param bool $isAllowed
     * @dataProvider provideBool
     */
    public function it_grant_access(bool $isAllowed): void
    {
        $message = new Message(new stdClass(), [
            MessageHeader::EVENT_TYPE => 'foo_bar'
        ]);

        $auth = $this->prophesize(AuthorizationService::class);
        $auth->isGranted('foo', $message)->willReturn($isAllowed)->shouldBeCalled();

        $alias = $this->prophesize(MessageAlias::class);
        $alias->typeToAlias('foo_bar')->willReturn('foo')->shouldBeCalled();

        $subscriber = new RouteGuardSubscriber($auth->reveal(), $alias->reveal());

        $context = $this->prophesize(MessageContext::class);
        $context->getCurrentEvent()->willReturn(Reporter::DISPATCH_EVENT);

        $context->getMessage()->willReturn($message)->shouldBeCalled();

        if ($isAllowed) {
            $context->isPropagationStopped()->willReturn(false)->shouldBeCalled();
        } else {
            $context->stopPropagation(true)->shouldBeCalled();
            $context->isPropagationStopped()->shouldNotBeCalled();

            $this->expectException(UnauthorizedException::class);
            $this->expectExceptionMessage("Unauthorized for event foo");
        }

        $tracker = new TrackingMessage();
        $subscriber->attachToTracker($tracker);

        $tracker->fire($context->reveal());
    }

    public function provideBool(): Generator
    {
        yield [true];
        yield [false];
    }
}
