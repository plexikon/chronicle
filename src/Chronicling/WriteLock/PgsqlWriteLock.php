<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Chronicling\WriteLock;

use Illuminate\Database\ConnectionInterface;
use Plexikon\Chronicle\Support\Contract\Chronicling\WriteLockStrategy;

final class PgsqlWriteLock implements WriteLockStrategy
{
    private ConnectionInterface $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    public function getLock(string $tableName): bool
    {
        $name = $this->determineLockName($tableName);

        return $this->connection->statement(
            'select pg_advisory_lock( hashtext(\'' . $name . '\') )'
        );
    }

    public function releaseLock(string $tableName): bool
    {
        $name = $this->determineLockName($tableName);

        return $this->connection->statement(
            'select pg_advisory_unlock( hashtext(\'' . $name . '\') )'
        );
    }

    private function determineLockName(string $tableName): string
    {
        return '_' . $tableName . '_write_lock';
    }
}
