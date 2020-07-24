<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Chronicling\WriteLock;

use Plexikon\Chronicle\Chronicling\WriteLock\NoWriteLock;
use Plexikon\Chronicle\Tests\Unit\TestCase;

final class NoWriteLockTest extends TestCase
{
    /**
     * @test
     */
    public function it_always_acquire_lock(): void
    {
        $this->assertTrue((new NoWriteLock())->getLock('foo'));
    }

    /**
     * @test
     */
    public function it_always_release_lock(): void
    {
        $this->assertTrue((new NoWriteLock())->releaseLock('foo'));
    }
}
