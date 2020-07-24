<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Chronicling\WriteLock;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use PDO;
use PDOStatement;
use Plexikon\Chronicle\Chronicling\WriteLock\MysqlWriteLock;
use Plexikon\Chronicle\Tests\Double\SomePDOException;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use Prophecy\Argument;

final class MysqlWriteLockTest extends TestCase
{
    /**
     * @test
     */
    public function it_acquire_lock(): void
    {
        $timeout = 1;
        $tableName = 'foo';
        $lockName = '_' . $tableName . '_write_lock';
        $connection = $this->prophesize(Connection::class); // checkMe w/ ConnectionInterface

        $pdoStatement = $this->prophesize(PDOStatement::class);
        $pdoStatement
            ->fetchAll(Argument::any())
            ->willReturn([['get_lock' => '1']])
            ->shouldBeCalled();

        $pdo = $this->prophesize(PDO::class);
        $pdo
            ->query('SELECT GET_LOCK(\'' . $lockName . '\', ' . $timeout . ') as \'get_lock\'')
            ->willReturn($pdoStatement)
            ->shouldBeCalled();

        $connection->getReadPDo()->willReturn($pdo)->shouldBecalled();

        $lock = new MysqlWriteLock($connection->reveal(), $timeout);

        $this->assertTrue($lock->getLock($tableName));
    }

    /**
     * @test
     */
    public function it_failed_acquire_lock_on_null_result(): void
    {
        $timeout = 1;
        $tableName = 'foo';
        $lockName = '_' . $tableName . '_write_lock';
        $connection = $this->prophesize(Connection::class); // checkMe w/ ConnectionInterface

        $pdo = $this->prophesize(PDO::class);
        $pdo
            ->query('SELECT GET_LOCK(\'' . $lockName . '\', ' . $timeout . ') as \'get_lock\'')
            ->willReturn(null)
            ->shouldBeCalled();

        $connection->getReadPDo()->willReturn($pdo)->shouldBecalled();

        $lock = new MysqlWriteLock($connection->reveal(), $timeout);

        $this->assertFalse($lock->getLock($tableName));
    }

    /**
     * @test
     */
    public function it_failed_acquire_lock_on_3058_exception_code(): void
    {
        $timeout = 1;
        $tableName = 'foo';
        $lockName = '_' . $tableName . '_write_lock';
        $connection = $this->prophesize(Connection::class); // checkMe w/ ConnectionInterface

        $pdoException = new SomePDOException('3058', 'bar');
        $exception = new QueryException('foo', [], $pdoException);

        $pdo = $this->prophesize(PDO::class);
        $pdo
            ->query('SELECT GET_LOCK(\'' . $lockName . '\', ' . $timeout . ') as \'get_lock\'')
            ->willThrow($exception)
            ->shouldBeCalled();

        $connection->getReadPDo()->willReturn($pdo)->shouldBecalled();

        $lock = new MysqlWriteLock($connection->reveal(), $timeout);

        $this->assertFalse($lock->getLock($tableName));
    }

    /**
     * @test
     */
    public function it_raise_exception_on_acquire_lock_failure(): void
    {
        $this->expectException(QueryException::class);

        $timeout = 1;
        $tableName = 'foo';
        $lockName = '_' . $tableName . '_write_lock';
        $connection = $this->prophesize(Connection::class); // checkMe w/ ConnectionInterface

        $pdoException = new SomePDOException('whatever', 'bar');
        $exception = new QueryException('foo', [], $pdoException);

        $pdo = $this->prophesize(PDO::class);
        $pdo
            ->query('SELECT GET_LOCK(\'' . $lockName . '\', ' . $timeout . ') as \'get_lock\'')
            ->willThrow($exception)
            ->shouldBeCalled();

        $connection->getReadPDo()->willReturn($pdo)->shouldBecalled();

        $lock = new MysqlWriteLock($connection->reveal(), $timeout);

        $lock->getLock($tableName);
    }

    /**
     * @test
     */
    public function it_release_lock(): void
    {
        $timeout = 1;
        $tableName = 'foo';
        $lockName = '_' . $tableName . '_write_lock';
        $connection = $this->prophesize(ConnectionInterface::class);
        $connection
            ->statement('DO RELEASE_LOCK(\'' . $lockName . '\') as \'release_lock\'')
            ->willReturn(true)
            ->shouldBeCalled();

        $lock = new MysqlWriteLock($connection->reveal(), $timeout);

        $lock->releaseLock($tableName);
    }
}
