<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSnapshotsTable extends Migration
{
    public function up(): void
    {
        Schema::create('snapshots', static function (Blueprint $table) {
            $table->string('aggregate_id', 150)->primary();
            $table->string('aggregate_type', 150);
            $table->bigInteger('last_version');
            $table->char('created_at', 26);
            $table->binary('aggregate_root');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('snapshots');
    }
}
