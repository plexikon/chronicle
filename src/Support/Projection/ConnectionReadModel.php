<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Support\Projection;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Plexikon\Chronicle\Support\Connection\HasConnectionTransaction;
use Plexikon\Chronicle\Support\Contract\Projector\ReadModel;
use Throwable;

abstract class ConnectionReadModel implements ReadModel
{
    use HasConnectionTransaction, HasReadModelOperation;

    /**
     * @var ConnectionInterface|Connection
     */
    protected ConnectionInterface $connection;
    private bool $isTransactionDisabled;

    public function __construct(ConnectionInterface $connection, bool $isTransactionDisabled = false)
    {
        $this->connection = $connection;
        $this->isTransactionDisabled = $isTransactionDisabled;
    }

    public function initialize(): void
    {
        $this->connection->getSchemaBuilder()->create($this->tableName(), $this->up());
    }

    public function isInitialized(): bool
    {
        return $this->connection->getSchemaBuilder()->hasTable($this->tableName());
    }

    public function reset(): void
    {
        $schema = $this->connection->getSchemaBuilder();

        $this->beginTransaction();

        try {
            $schema->disableForeignKeyConstraints();

            $this->connection->table($this->tableName())->truncate();

            $schema->enableForeignKeyConstraints();

        } catch (Throwable $exception) {
            $this->rollbackTransaction();

            throw $exception;
        }

        $this->commitTransaction();
    }

    public function down(): void
    {
        $schema = $this->connection->getSchemaBuilder();

        $this->beginTransaction();

        try {
            $schema->disableForeignKeyConstraints();

            $schema->drop($this->tableName());

            $schema->enableForeignKeyConstraints();
        } catch (Throwable $exception) {
            $this->rollbackTransaction();

            throw $exception;
        }

        $this->commitTransaction();
    }

    protected function isTransactionDisabled(): bool
    {
        return $this->isTransactionDisabled;
    }

    abstract protected function up(): callable;

    abstract protected function tableName(): string;
}
