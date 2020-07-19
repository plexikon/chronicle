<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Plexikon\Chronicle\Support\Contract\Chronicling\Model\EventStreamModel;

class CreateEventStreamsTable extends Migration
{
    public function up(): void
    {
        Schema::create(EventStreamModel::TABLE, static function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->string('real_stream_name', 150)->unique();
            $table->char('stream_name', 41);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(EventStreamModel::TABLE);
    }
}
