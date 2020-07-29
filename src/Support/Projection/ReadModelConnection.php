<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Support\Projection;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Plexikon\Chronicle\Support\Contract\Projector\ReadModel;

abstract class ReadModelConnection implements ReadModel
{
    use HasReadModelConnection, HasReadModelOperation;

    /**
     * @var ConnectionInterface|Connection
     */
    protected ConnectionInterface $connection;
    protected bool $isTransactionDisabled;

    public function __construct(ConnectionInterface $connection, bool $isTransactionDisabled = false)
    {
        $this->connection = $connection;
        $this->isTransactionDisabled = $isTransactionDisabled;
    }
}
