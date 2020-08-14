<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Chronicling\WriteLock;

use Illuminate\Database\ConnectionInterface;
use Plexikon\Chronicle\Chronicling\WriteLock\PgsqlWriteLock;
use Plexikon\Chronicle\Tests\Unit\TestCase;

final class PgsqlWriteLockTest extends TestCase
{
    /**
     * @test
     */
    public function it_acquire_lock(): void
    {
        $tableName = 'foo';
        $lockName = '_' . $tableName . '_write_lock';

        $connection = $this->prophesize(ConnectionInterface::class);
        $connection
            ->statement('select pg_advisory_lock( hashtext(\'' . $lockName . '\') )')
            ->shouldBeCalled()
            ->willReturn(true);

        $lock = new PgsqlWriteLock($connection->reveal());

        $this->assertTrue($lock->getLock($tableName));
    }

    /**
     * @test
     */
    public function it_release_lock()
    {
        $tableName = 'foo';
        $lockName = '_' . $tableName . '_write_lock';

        $connection = $this->prophesize(ConnectionInterface::class);
        $connection
            ->statement('select pg_advisory_unlock( hashtext(\'' . $lockName . '\') )')
            ->shouldBeCalled()
            ->willReturn(true);

        $lock = new PgsqlWriteLock($connection->reveal());

        $this->assertTrue($lock->releaseLock($tableName));
    }
}
