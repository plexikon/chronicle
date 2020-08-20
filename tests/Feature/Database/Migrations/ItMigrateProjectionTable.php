<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Feature\Database\Migrations;

use Illuminate\Support\Facades\Schema;
use Plexikon\Chronicle\Support\Contract\Chronicling\Model\ProjectionModel;
use Plexikon\Chronicle\Tests\Feature\ITestCase;

final class ItMigrateProjectionTable extends ITestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__  . '/../../../../database/migrations');

        $this->loadLaravelMigrations(['--database' => 'testing']);
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
    }

    protected function getPackageProviders($app)
    {
        return [];
    }

    /**
     * @test
     */
    public function it_run_migration(): void
    {
        $this->assertTrue(Schema::hasColumns(ProjectionModel::TABLE,[
            'no','name','position','state','status','locked_until'
        ]));
    }
}
