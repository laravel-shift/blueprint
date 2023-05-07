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
        Schema::create('omits', function (Blueprint $table) {
            $table->id();
            $table->integer('integer')->unique();
            $table->tinyInteger('tiny_integer')->unique();
            $table->smallInteger('small_integer')->unique();
            $table->mediumInteger('medium_integer')->unique();
            $table->bigInteger('big_integer')->unique();
            $table->unsignedInteger('unsigned_integer')->unique();
            $table->unsignedTinyInteger('unsigned_tiny_integer')->unique();
            $table->unsignedSmallInteger('unsigned_small_integer')->unique();
            $table->unsignedMediumInteger('unsigned_medium_integer')->unique();
            $table->unsignedBigInteger('unsigned_big_integer')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('omits');
    }
};
