<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Support\Projection;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Plexikon\Chronicle\Support\Projection\HasConnectionOperation;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use Prophecy\Prophecy\ObjectProphecy;

final class HasConnectionOperationTest extends TestCase
{
    private ObjectProphecy $connection;

    /**
     * @test
     */
    public function it_insert_data(): void
    {
        $queryBuilder = $this->prophesize(Builder::class);
        $this->connection->table('foo')->willReturn($queryBuilder);

        $queryBuilder->insert(['foo'])->shouldBeCalled();

        $instance = $this->connectionOperationInstance();

        $instance('insert', ['foo']);
    }

    /**
     * @test
     */
    public function it_update_data(): void
    {
        $queryBuilder = $this->prophesize(Builder::class);
        $this->connection->table('foo')->willReturn($queryBuilder);

        $queryBuilder->where('id', 'bar')->willReturn($queryBuilder)->shouldBeCalled();
        $queryBuilder->update(['foo'])->shouldBeCalled();

        $instance = $this->connectionOperationInstance();

        $this->assertEquals('id', $instance('getKey'));

        $instance('update', 'bar', ['foo']);
    }

    /**
     * @test
     */
    public function it_increment_value_with_data(): void
    {
        $queryBuilder = $this->prophesize(Builder::class);
        $this->connection->table('foo')->willReturn($queryBuilder);

        $queryBuilder->where('id', 'bar')->willReturn($queryBuilder)->shouldBeCalled();
        $queryBuilder->increment('col', 10, ['foo'])->shouldBeCalled();

        $instance = $this->connectionOperationInstance();

        $this->assertEquals('id', $instance('getKey'));

        $instance('increment', 'bar', 'col', 10, ['foo']);
    }

    /**
     * @test
     */
    public function it_decrement_value_with_data(): void
    {
        $queryBuilder = $this->prophesize(Builder::class);
        $this->connection->table('foo')->willReturn($queryBuilder);

        $queryBuilder->where('id', 'bar')->willReturn($queryBuilder)->shouldBeCalled();
        $queryBuilder->decrement('col', 10, ['foo'])->shouldBeCalled();

        $instance = $this->connectionOperationInstance();

        $this->assertEquals('id', $instance('getKey'));

        $instance('decrement', 'bar', 'col', 10, ['foo']);
    }

    protected function setUp(): void
    {
        $this->connection = $this->prophesize(ConnectionInterface::class);
    }

    private function connectionOperationInstance(): callable
    {
        $connection = $this->connection->reveal();

        return new class($connection) {
            use HasConnectionOperation;

            public function __construct(ConnectionInterface $connection)
            {
                $this->connection = $connection;
            }

            protected function tableName(): string
            {
                return 'foo';
            }

            /**
             * @param $method
             * @param mixed ...$args
             * @return mixed
             */
            public function __invoke($method, ...$args)
            {
                return $this->{$method}(...$args);
            }
        };
    }
}
