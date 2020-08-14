<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Chronicling;

use Plexikon\Chronicle\Chronicling\TransactionalEventChronicler;
use Plexikon\Chronicle\Exception\TransactionAlreadyStarted;
use Plexikon\Chronicle\Exception\TransactionNotStarted;
use Plexikon\Chronicle\Support\Contract\Chronicling\TransactionalChronicler;
use Plexikon\Chronicle\Support\Contract\Tracker\EventContext;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use Plexikon\Chronicle\Tracker\TransactionalTrackingEvent;

final class TransactionalEventChroniclerTest extends TestCase
{
    /**
     * @test
     */
    public function it_dispatch_begin_transaction_event(): void
    {
        $tracker = new TransactionalTrackingEvent();
        $chronicler = $this->prophesize(TransactionalChronicler::class);
        $chronicler->beginTransaction()->shouldBeCalled();

        $transactionalEventChronicler = new TransactionalEventChronicler($chronicler->reveal(), $tracker);

        $transactionalEventChronicler->beginTransaction();
    }

    /**
     * @test
     */
    public function it_dispatch_begin_transaction_event_and_raised_exception_if_transaction_already_started(): void
    {
        $this->expectException(TransactionAlreadyStarted::class);

        $tracker = new TransactionalTrackingEvent();
        $chronicler = $this->prophesize(TransactionalChronicler::class);
        $chronicler
            ->beginTransaction()
            ->willThrow(new TransactionAlreadyStarted('foo'))
            ->shouldBeCalled();

        $transactionalEventChronicler = new TransactionalEventChronicler($chronicler->reveal(), $tracker);

        $tracker->listen(TransactionalChronicler::BEGIN_TRANSACTION_EVENT,
            function (EventContext $context): void {
                $this->assertTrue($context->hasException());
            });

        $transactionalEventChronicler->beginTransaction();
    }

    /**
     * @test
     */
    public function it_dispatch_commit_transaction_event(): void
    {
        $tracker = new TransactionalTrackingEvent();
        $chronicler = $this->prophesize(TransactionalChronicler::class);
        $chronicler->commitTransaction()->shouldBeCalled();

        $transactionalEventChronicler = new TransactionalEventChronicler($chronicler->reveal(), $tracker);

        $transactionalEventChronicler->commitTransaction();
    }

    /**
     * @test
     */
    public function it_dispatch_commit_transaction_event_and_raise_exception_if_exception_not_started(): void
    {
        $this->expectException(TransactionNotStarted::class);

        $tracker = new TransactionalTrackingEvent();
        $chronicler = $this->prophesize(TransactionalChronicler::class);
        $chronicler
            ->commitTransaction()
            ->willThrow(new TransactionNotStarted())
            ->shouldBeCalled();

        $transactionalEventChronicler = new TransactionalEventChronicler($chronicler->reveal(), $tracker);

        $tracker->listen(TransactionalChronicler::COMMIT_TRANSACTION_EVENT,
            function (EventContext $context): void {
                $this->assertTrue($context->hasException());
            });

        $transactionalEventChronicler->commitTransaction();
    }

    /**
     * @test
     */
    public function it_dispatch_rollback_transaction_event(): void
    {
        $tracker = new TransactionalTrackingEvent();
        $chronicler = $this->prophesize(TransactionalChronicler::class);
        $chronicler->rollbackTransaction()->shouldBeCalled();

        $transactionalEventChronicler = new TransactionalEventChronicler($chronicler->reveal(), $tracker);

        $transactionalEventChronicler->rollbackTransaction();
    }

    /**
     * @test
     */
    public function it_dispatch_rollback_transaction_event_and_raise_exception_if_exception_not_started(): void
    {
        $this->expectException(TransactionNotStarted::class);

        $tracker = new TransactionalTrackingEvent();
        $chronicler = $this->prophesize(TransactionalChronicler::class);
        $chronicler
            ->rollbackTransaction()
            ->willThrow(new TransactionNotStarted())
            ->shouldBeCalled();

        $transactionalEventChronicler = new TransactionalEventChronicler($chronicler->reveal(), $tracker);

        $tracker->listen(TransactionalChronicler::ROLLBACK_TRANSACTION_EVENT,
            function (EventContext $context): void {
                $this->assertTrue($context->hasException());
            });

        $transactionalEventChronicler->rollbackTransaction();
    }

    /**
     * @test
     * @dataProvider provideBool
     * @param bool $inTransaction
     */
    public function it_check_if_in_transaction(bool $inTransaction): void
    {
        $tracker = new TransactionalTrackingEvent();
        $chronicler = $this->prophesize(TransactionalChronicler::class);
        $chronicler
            ->inTransaction()
            ->willReturn($inTransaction)
            ->shouldBeCalled();

        $transactionalEventChronicler = new TransactionalEventChronicler($chronicler->reveal(), $tracker);

        $this->assertEquals($inTransaction, $transactionalEventChronicler->inTransaction());
    }

    public function provideBool(): \Generator
    {
        yield [true];

        yield [false];
    }
}
