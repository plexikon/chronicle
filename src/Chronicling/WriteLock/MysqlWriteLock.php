<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Chronicling\WriteLock;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use Plexikon\Chronicle\Support\Contract\Chronicling\WriteLockStrategy;

final class MysqlWriteLock implements WriteLockStrategy
{
    private ConnectionInterface $connection;
    private int $timeout;

    public function __construct(ConnectionInterface $connection, int $timeout = -1)
    {
        $this->connection = $connection;
        $this->timeout = $timeout;
    }

    public function getLock(string $tableName): bool
    {
        $name = $this->determineLockName($tableName);

        try {
            $result = $this->connection
                ->getReadPdo()
                ->query('SELECT GET_LOCK(\'' . $name . '\', ' . $this->timeout . ') as \'get_lock\'');
        } catch (QueryException $exception) {
            if ('3058' === $exception->getCode()) {
                return false;
            }

            throw $exception;
        }

        if (!$result) {
            return false;
        }

        $writeLockStatus = $result->fetchAll();

        return '1' === ($writeLockStatus[0]['get_lock'] ?? 0);
    }

    public function releaseLock(string $tableName): bool
    {
        $name = $this->determineLockName($tableName);

        return $this->connection->statement('DO RELEASE_LOCK(\'' . $name . '\') as \'release_lock\'');
    }

    private function determineLockName(string $tableName): string
    {
        return '_' . $tableName . '_write_lock';
    }
}
