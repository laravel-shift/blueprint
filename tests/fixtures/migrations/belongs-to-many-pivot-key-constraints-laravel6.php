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
            $table->unsignedBigInteger('diary_id');
            $table->foreign('diary_id')->references('id')->on('diaries')->onDelete('cascade');
            $table->unsignedBigInteger('journey_id');
            $table->foreign('journey_id')->references('id')->on('journeys')->onDelete('cascade');
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
