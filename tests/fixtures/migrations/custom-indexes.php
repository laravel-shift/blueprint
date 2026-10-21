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
        Schema::disableForeignKeyConstraints();

        Schema::create('cooltables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coolcool')->index('custom_index_coolcool')->constrained('coolcool');
            $table->foreignId('foobar')->index('custom_index_foobar')->constrained('foobars');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cooltables');
    }
};
