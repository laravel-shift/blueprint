<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiaryJourneyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('diary_journey', function (Blueprint $table) {
            $table->foreignId('diary_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('journey_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('diary_journey');
    }
}
