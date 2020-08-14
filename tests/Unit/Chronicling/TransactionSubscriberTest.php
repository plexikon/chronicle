<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Chronicling;

use Plexikon\Chronicle\Chronicling\TransactionSubscriber;
use Plexikon\Chronicle\Support\Contract\Chronicling\TransactionalChronicler;
use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use Plexikon\Chronicle\Tracker\TrackingMessage;
use RuntimeException;

final class TransactionSubscriberTest extends TestCase
{
    /**
     * @test
     */
    public function it_handle_transaction(): void
    {
        $chronicler = $this->prophesize(TransactionalChronicler::class);
        $chronicler->beginTransaction()->shouldBeCalled();
        $chronicler->inTransaction()->willReturn(true)->shouldBeCalled();
        $chronicler->commitTransaction()->shouldBeCalled();

        $subscriber = new TransactionSubscriber($chronicler->reveal());

        $tracker = new TrackingMessage();
        $subscriber->attachToTracker($tracker);

        $context = $tracker->newContext(Reporter::DISPATCH_EVENT);
        $tracker->fire($context);

        $context->withEvent(Reporter::FINALIZE_EVENT);
        $tracker->fire($context);
    }

    /**
     * @test
     */
    public function it_rollback_transaction(): void
    {
        $chronicler = $this->prophesize(TransactionalChronicler::class);
        $chronicler->beginTransaction()->shouldBeCalled();
        $chronicler->inTransaction()->willReturn(true)->shouldBeCalled();
        $chronicler->rollbackTransaction()->shouldBeCalled();

        $subscriber = new TransactionSubscriber($chronicler->reveal());

        $tracker = new TrackingMessage();
        $subscriber->attachToTracker($tracker);

        $context = $tracker->newContext(Reporter::DISPATCH_EVENT);
        $context->withRaisedException(new RuntimeException('foo'));
        $tracker->fire($context);

        $context->withEvent(Reporter::FINALIZE_EVENT);
        $tracker->fire($context);
    }

    /**
     * @test
     */
    public function it_does_not_commit_transaction_if_transaction_has_not_started(): void
    {
        $chronicler = $this->prophesize(TransactionalChronicler::class);
        $chronicler->beginTransaction()->shouldNotBeCalled();
        $chronicler->inTransaction()->willReturn(false)->shouldBeCalled();
        $chronicler->commitTransaction()->shouldNotBeCalled();

        $subscriber = new TransactionSubscriber($chronicler->reveal());

        $tracker = new TrackingMessage();
        $subscriber->attachToTracker($tracker);

        $context = $tracker->newContext(Reporter::FINALIZE_EVENT);

        $tracker->fire($context);
    }

    /**
     * @test
     */
    public function it_does_not_rollback_transaction_if_transaction_has_not_started(): void
    {
        $chronicler = $this->prophesize(TransactionalChronicler::class);
        $chronicler->beginTransaction()->shouldNotBeCalled();
        $chronicler->inTransaction()->willReturn(false)->shouldBeCalled();
        $chronicler->rollbackTransaction()->shouldNotBeCalled();

        $subscriber = new TransactionSubscriber($chronicler->reveal());

        $tracker = new TrackingMessage();
        $subscriber->attachToTracker($tracker);

        $context = $tracker->newContext(Reporter::FINALIZE_EVENT);
        $context->withRaisedException(new RuntimeException('foo'));

        $tracker->fire($context);
    }
}
