<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Support\Projector;

use Generator;
use Plexikon\Chronicle\Support\Projector\InMemoryProjectionState;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use stdClass;

final class inMemoryProjectionStateTest extends TestCase
{
    /**
     * @test
     */
    public function it_construct_with_empty_state(): void
    {
        $state = new InMemoryProjectionState();

        $this->assertEmpty($state->getState());
    }

    /**
     * @test
     * @dataProvider provideInvalidState
     * @param $invalidState
     */
    public function it_only_set_state_as_array($invalidState): void
    {
        $state = new InMemoryProjectionState();

        $state->setState($invalidState);

        $this->assertEmpty($state->getState());
    }

    /**
     * @test
     */
    public function it_access_state(): void
    {
        $state = new InMemoryProjectionState();

        $state->setState(['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $state->getState());
    }

    /**
     * @test
     */
    public function it_reset_state(): void
    {
        $state = new InMemoryProjectionState();

        $state->setState(['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $state->getState());

        $state->resetState();

        $this->assertEmpty($state->getState());
    }

    public function provideInvalidState(): Generator
    {
        yield [null];
        yield [''];
        yield [1];
        yield [new stdClass()];
    }
}
