<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Unit\Support\Projection;

use Closure;
use Generator;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Plexikon\Chronicle\Support\Projection\ReadModelConnection;
use Plexikon\Chronicle\Tests\Unit\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;

final class ConnectionReadModelTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_initialize_read_model(): void
    {
        $schemaBuilder = $this->prophesize(SchemaBuilder::class);
        $schemaBuilder->create('foo', Argument::type(Closure::class));
        $this->connection->getSchemaBuilder()->willReturn($schemaBuilder)->shouldBeCalled();

        $instance = $this->connectionReadModelInstance(false);
        $instance->initialize();
    }

    /**
     * @test
     */
    public function it_can_check_if_read_model_is_initialized(): void
    {
        $schemaBuilder = $this->prophesize(SchemaBuilder::class);
        $schemaBuilder->hasTable('foo')->willReturn(true)->shouldBeCalled();
        $this->connection->getSchemaBuilder()->willReturn($schemaBuilder)->shouldBeCalled();

        $instance = $this->connectionReadModelInstance(false);
        $this->assertTrue($instance->isInitialized());
    }

    /**
     * @test
     * @dataProvider provideDisableTransaction
     * @param bool $isTransactionDisabled
     */
    public function it_can_reset_read_model(bool $isTransactionDisabled): void
    {
        $schemaBuilder = $this->prophesize(SchemaBuilder::class);

        $schemaBuilder->disableForeignKeyConstraints()->shouldBeCalled();
        $schemaBuilder->enableForeignKeyConstraints()->shouldBeCalled();
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->truncate()->shouldBeCalled();

        $this->connection->getSchemaBuilder()->willReturn($schemaBuilder)->shouldBeCalled();
        $this->connection->table('foo')->willReturn($queryBuilder);

        if ($isTransactionDisabled) {
            $this->connection->beginTransaction()->shouldNotBeCalled();
            $this->connection->commit()->shouldNotBeCalled();
        } else {
            $this->connection->beginTransaction()->shouldBeCalled();
            $this->connection->commit()->shouldBeCalled();
        }

        $instance = $this->connectionReadModelInstance($isTransactionDisabled);
        $instance->reset();
    }

    /**
     * @test
     * @dataProvider provideDisableTransaction
     * @param bool $isTransactionDisabled
     */
    public function it_rollback_transaction_on_truncate_exception_and_raise_exception(bool $isTransactionDisabled): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('foo');

        $schemaBuilder = $this->prophesize(SchemaBuilder::class);

        $schemaBuilder->disableForeignKeyConstraints()->shouldBeCalled();
        $schemaBuilder->enableForeignKeyConstraints()->shouldNotBeCalled();

        $exception = new RuntimeException('foo');
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->truncate()
            ->willThrow($exception)
            ->shouldBeCalled();

        $this->connection->getSchemaBuilder()->willReturn($schemaBuilder)->shouldBeCalled();
        $this->connection->table('foo')->willReturn($queryBuilder);

        if($isTransactionDisabled){
            $this->connection->beginTransaction()->shouldNotBeCalled();
            $this->connection->rollBack()->shouldNotBeCalled();
        }else{
            $this->connection->beginTransaction()->shouldBeCalled();
            $this->connection->rollBack()->shouldBeCalled();
        }

        $instance = $this->connectionReadModelInstance($isTransactionDisabled);
        $instance->reset();
    }

    /**
     * @test
     * @dataProvider provideDisableTransaction
     * @param bool $isTransactionDisabled
     */
    public function it_delete_read_model(bool $isTransactionDisabled): void
    {
        $schemaBuilder = $this->prophesize(SchemaBuilder::class);
        $schemaBuilder->disableForeignKeyConstraints()->shouldBeCalled();
        $schemaBuilder->enableForeignKeyConstraints()->shouldBeCalled();
        $schemaBuilder->drop('foo')->shouldBeCalled();

        $this->connection->getSchemaBuilder()->willReturn($schemaBuilder)->shouldBeCalled();

        if($isTransactionDisabled){
            $this->connection->beginTransaction()->shouldNotBeCalled();
            $this->connection->commit()->shouldNotBeCalled();
        }else{
            $this->connection->beginTransaction()->shouldBeCalled();
            $this->connection->commit()->shouldBeCalled();
        }

        $instance = $this->connectionReadModelInstance($isTransactionDisabled);
        $instance->down();
    }

    /**
     * @test
     * @dataProvider provideDisableTransaction
     * @param bool $isTransactionDisabled
     */
    public function it_rollback_transaction_on_delete_exception_and_raise_exception(bool $isTransactionDisabled): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('foo');

        $exception = new RuntimeException('foo');

        $schemaBuilder = $this->prophesize(SchemaBuilder::class);
        $schemaBuilder->disableForeignKeyConstraints()->shouldBeCalled();
        $schemaBuilder->enableForeignKeyConstraints()->shouldNotBeCalled();
        $schemaBuilder
            ->drop('foo')
            ->willThrow($exception)
            ->shouldBeCalled();

        $this->connection->getSchemaBuilder()->willReturn($schemaBuilder)->shouldBeCalled();

        if($isTransactionDisabled){
            $this->connection->beginTransaction()->shouldNotBeCalled();
            $this->connection->rollBack()->shouldNotBeCalled();
        }else{
            $this->connection->beginTransaction()->shouldBeCalled();
            $this->connection->rollBack()->shouldBeCalled();
        }

        $instance = $this->connectionReadModelInstance($isTransactionDisabled);
        $instance->down();
    }

    public function provideDisableTransaction(): Generator
    {
        yield [true];
        yield [false];
    }

    public function connectionReadModelInstance(bool $isTransactionDisabled): ReadModelConnection
    {
        $connection = $this->connection->reveal();
        return new class($connection, $isTransactionDisabled) extends ReadModelConnection {

            protected function up(): callable
            {
                return function () {
                    //
                };
            }

            protected function tableName(): string
            {
                return 'foo';
            }
        };
    }

    private ObjectProphecy $connection;

    protected function setUp(): void
    {
        $this->connection = $this->prophesize(Connection::class);
    }
}
