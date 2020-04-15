<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOptimizesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('optimizes', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('tiny');
            $table->unsignedSmallInteger('small');
            $table->unsignedMediumInteger('medium');
            $table->unsignedInteger('int');
            $table->unsignedDecimal('dec', 8, 2);
            $table->unsignedBigInteger('big');
            $table->nullableMorphs('foo');
            $table->nullableUuidMorphs('foobar');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('optimizes');
    }
}
