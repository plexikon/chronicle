<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Plexikon\Chronicle\Support\Contract\Chronicling\Model\ProjectionModel;

class CreateProjectionsTable extends Migration
{
    public function up(): void
    {
        Schema::create(ProjectionModel::TABLE, static function (Blueprint $table) {
            $table->bigInteger('no', true);
            $table->string('name', 150)->unique();
            $table->json('position');
            $table->json('state');
            $table->string('status', 28);
            $table->char('locked_until', 26)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(ProjectionModel::TABLE);
    }
}
