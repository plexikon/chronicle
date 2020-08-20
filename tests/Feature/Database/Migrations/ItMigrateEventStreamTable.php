<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Tests\Feature\Database\Migrations;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Plexikon\Chronicle\Support\Contract\Chronicling\Model\EventStreamModel;
use Plexikon\Chronicle\Tests\Feature\ITestCase;

final class ItMigrateEventStreamTable extends ITestCase
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
        $this->assertTrue(Schema::hasColumns(EventStreamModel::TABLE,[
            'id','real_stream_name','stream_name'
        ]));

        DB::table(EventStreamModel::TABLE)->insert([
            'id' => 1,
            'real_stream_name' => 'foo',
            'stream_name' => 'foo_bar'
        ]);

        $this->assertEquals([
            'id' => 1,
            'real_stream_name' => 'foo',
            'stream_name' => 'foo_bar'
        ], (array)DB::table(EventStreamModel::TABLE)->first());
    }
}
