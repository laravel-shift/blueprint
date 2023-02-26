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
        Schema::create('optimizes', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('tiny');
            $table->unsignedSmallInteger('small');
            $table->unsignedMediumInteger('medium');
            $table->unsignedInteger('int');
            $table->unsignedDecimal('dec', 8, 2);
            $table->unsignedBigInteger('big');
            $table->morphs('foo');
            $table->nullableUuidMorphs('bar');
            $table->nullableMorphs('baz');
            $table->uuidMorphs('foobar');
            $table->nullableUuidMorphs('foobarbaz');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('optimizes');
    }
};
