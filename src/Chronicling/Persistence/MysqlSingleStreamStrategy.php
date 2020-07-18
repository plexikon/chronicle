<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Chronicling\Persistence;

use Illuminate\Database\Schema\Blueprint;

final class MysqlSingleStreamStrategy extends SingleStreamPersistenceStrategy
{
    public function up(string $tableName): ?callable
    {
        return function (Blueprint $table) {
            $table->collation = 'utf8mb4_bin';
            $table->charset = 'utf8mb4';
            $table->bigInteger('no', true);
            $table->uuid('event_id');
            $table->string('event_type', 100);
            $table->json('headers');
            $table->json('payload');
            $table->dateTimeTz('created_at', 6);
            $table->integer('aggregate_version', false, true)->storedAs(
                'JSON_UNQUOTE(JSON_EXTRACT(headers, \'$.__aggregate_version\'))'
            );
            $table->uuid('aggregate_id')->storedAs(
                'JSON_UNQUOTE(JSON_EXTRACT(headers, \'$.__aggregate_id\'))'
            );

            $table->string('aggregate_type', 150)->storedAs(
                'JSON_UNQUOTE(JSON_EXTRACT(headers, \'$.__aggregate_type\'))'
            );
            $table->unique('event_id', 'ix_event_id');
            $table->unique(['aggregate_id', 'aggregate_version', 'aggregate_type'], 'ix_unique_event');
            $table->index(['aggregate_type', 'aggregate_id', 'no'], 'ix_query_aggregate');
        };
    }
}
