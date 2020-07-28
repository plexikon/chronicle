<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Support\QueryScope;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Plexikon\Chronicle\Exception\RuntimeException;
use Plexikon\Chronicle\Support\Contract\Chronicling\QueryScope;

class QueryScopeFactory
{
    private Container $container;
    private array $config;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->config = $container->get(Repository::class)->get('chronicler');
    }

    public function fromConnection(Connection $connection): QueryScope
    {
        $driver = $connection->getDriverName();

        return $this->fromDriver($driver);
    }

    public function fromDriver(string $driver): QueryScope
    {
        $queryScopeClass = Arr::get($this->config, "connections.$driver.scope");

        if(is_string($queryScopeClass)){
            return $this->container->make($queryScopeClass);
        }

        $method = 'create' . Str::studly($driver . 'QueryScope');

        if (method_exists($this, $method)) {
            return $this->$method($queryScopeClass);
        }

        throw new RuntimeException("Invalid query scope driver $driver");
    }

    protected function createPgsqlQueryScope(): PgsqlQueryScope
    {
        return new PgsqlQueryScope();
    }

    protected function createMysqlQueryScope(): MysqlQueryScope
    {
        return new MysqlQueryScope();
    }

    protected function createInMemoryQueryScope(): InMemoryQueryScope
    {
        return new InMemoryQueryScope();
    }
}
