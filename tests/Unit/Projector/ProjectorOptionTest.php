<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Projector;

use Generator;
use Plexikon\Chronicle\Exception\RuntimeException;
use Plexikon\Chronicle\Projector\ProjectorOption;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use stdClass;

final class ProjectorOptionTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_constructed_with_default_values(): void
    {
        $options = new ProjectorOption();

        $this->assertEquals(false, $options->dispatchSignal());
        $this->assertEquals(1000, $options->lockTimoutMs());
        $this->assertEquals(10000, $options->sleep());
        $this->assertEquals(1000, $options->persistBlockSize());
        $this->assertEquals(0, $options->updateLockThreshold());
    }

    /**
     * @test
     */
    public function it_raise_exception_with_invalid_array_key(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Projector option foo does not exists");
        new ProjectorOption(
            ['foo' => true]
        );
    }

    /**
     * @test
     * @dataProvider provideInvalidValue
     * @param $invalidValue
     */
    public function it_raise_exception_with_invalid_values($invalidValue): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Projector option value accept positive integer (or 0) and boolean value only");
        new ProjectorOption(
            [ProjectorOption::OPTION_LOCK_TIMEOUT_MS => $invalidValue]
        );
    }

    public function provideInvalidValue(): Generator
    {
        yield [-1];
        yield ['foo'];
        yield [new stdClass()];
        yield [[]];
    }
}
