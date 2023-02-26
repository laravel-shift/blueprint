<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('numerics', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('foo')->default(100);
            $table->boolean('bar')->default(0);
            $table->decimal('baz')->default(1.0);
            $table->integer('qui')->default(1);
            $table->mediumInteger('qux')->default(1);
            $table->smallInteger('quux')->default(1);
            $table->tinyInteger('corge')->default(1);
            $table->unsignedInteger('grault')->default(1);
            $table->integer('garply')->default(null)->nullable();
            $table->unsignedDecimal('waldo')->default('i');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('numerics');
    }
};
