<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Support\Connection;

use Generator;
use Illuminate\Database\ConnectionInterface;
use InvalidArgumentException;
use Plexikon\Chronicle\Exception\RuntimeException;
use Plexikon\Chronicle\Exception\TransactionAlreadyStarted;
use Plexikon\Chronicle\Exception\TransactionNotStarted;
use Plexikon\Chronicle\Support\Connection\HasConnectionTransaction;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use Throwable;

final class HasConnectionTransactionTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideBool
     * @param bool $disableTransaction
     */
    public function it_start_transaction(bool $disableTransaction): void
    {
        $connection = $this->prophesize(ConnectionInterface::class);

        if ($disableTransaction) {
            $connection->beginTransaction()->shouldNotBeCalled();
            $instance = $this->withConnectionInstance($connection->reveal(), $disableTransaction);
            $instance->beginTransaction();
        } else {
            $connection->beginTransaction()->shouldBeCalled();
            $instance = $this->withConnectionInstance($connection->reveal(), $disableTransaction);
            $instance->beginTransaction();
        }
    }

    /**
     * @test
     * @dataProvider provideException
     * @param Throwable $exception
     */
    public function it_raise_exception_on_begin_transaction_if_transaction_already_started(Throwable $exception): void
    {
        $this->expectException(TransactionAlreadyStarted::class);

        $connection = $this->prophesize(ConnectionInterface::class);

        $connection->beginTransaction()->shouldBeCalled();
        $connection->beginTransaction()
            ->willThrow($exception)
            ->shouldBeCalled();

        $instance = $this->withConnectionInstance($connection->reveal(), false);

        $instance->beginTransaction();
        $instance->beginTransaction();
    }

    /**
     * @test
     * @dataProvider provideBool
     * @param bool $disableTransaction
     */
    public function it_commit_transaction(bool $disableTransaction): void
    {
        $connection = $this->prophesize(ConnectionInterface::class);

        if ($disableTransaction) {
            $connection->commit()->shouldNotBeCalled();
            $instance = $this->withConnectionInstance($connection->reveal(), $disableTransaction);
            $instance->commitTransaction();
        } else {
            $connection->commit()->shouldBeCalled();
            $instance = $this->withConnectionInstance($connection->reveal(), $disableTransaction);
            $instance->commitTransaction();
        }
    }

    /**
     * @test
     * @dataProvider provideException
     * @param Throwable $exception
     */
    public function it_raise_exception_on_commit_transaction_if_transaction_not_started(Throwable $exception): void
    {
        $this->expectException(TransactionNotStarted::class);

        $connection = $this->prophesize(ConnectionInterface::class);

        $connection->commit()->shouldBeCalled();
        $connection->commit()
            ->willThrow($exception)
            ->shouldBeCalled();

        $instance = $this->withConnectionInstance($connection->reveal(), false);

        $instance->commitTransaction();
    }

    /**
     * @test
     * @dataProvider provideBool
     * @param bool $disableTransaction
     */
    public function it_rollback_transaction(bool $disableTransaction): void
    {
        $connection = $this->prophesize(ConnectionInterface::class);

        if ($disableTransaction) {
            $connection->rollBack()->shouldNotBeCalled();
            $instance = $this->withConnectionInstance($connection->reveal(), $disableTransaction);
            $instance->rollbackTransaction();
        } else {
            $connection->rollBack()->shouldBeCalled();
            $instance = $this->withConnectionInstance($connection->reveal(), $disableTransaction);
            $instance->rollbackTransaction();
        }
    }

    /**
     * @test
     * @dataProvider provideException
     * @param Throwable $exception
     */
    public function it_raise_exception_on_rollback_transaction_if_transaction_not_started(Throwable $exception): void
    {
        $this->expectException(TransactionNotStarted::class);

        $connection = $this->prophesize(ConnectionInterface::class);

        $connection->rollBack()
            ->willThrow($exception)
            ->shouldBeCalled();

        $instance = $this->withConnectionInstance($connection->reveal(), false);

        $instance->rollbackTransaction();
    }

    private function withConnectionInstance(ConnectionInterface $connection, bool $disabled): object
    {
        return new class($connection, $disabled) {
            use HasConnectionTransaction;

            private bool $disabled;

            public function __construct(ConnectionInterface $connection, bool $disabled)
            {
                $this->connection = $connection;
                $this->disabled = $disabled;
            }

            protected function isTransactionDisabled(): bool
            {
                return $this->disabled;
            }
        };
    }

    /**
     * @test
     * @dataProvider provideBool
     * @param bool $disabled
     */
    public function it_check_if_in_transaction(bool $disabled): void
    {
        $connection = $this->prophesize(ConnectionInterface::class);

        if ($disabled) {
            $connection->transactionLevel()->shouldNotBeCalled();
            $instance = $this->withConnectionInstance($connection->reveal(), $disabled);

            $this->assertEquals(false, $instance->inTransaction());
        }else{
            $connection->transactionLevel()->willReturn(1)->shouldBeCalled();
            $instance = $this->withConnectionInstance($connection->reveal(), $disabled);

            $this->assertEquals(true, $instance->inTransaction());
        }
    }

    public function provideException(): Generator
    {
        yield [new RuntimeException('foo')];

        yield [new InvalidArgumentException('foo')];
    }

    public function provideBool(): Generator
    {
        yield [true];
        yield [false];
    }
}
