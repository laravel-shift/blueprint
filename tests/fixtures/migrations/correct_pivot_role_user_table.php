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

        Schema::create('app_role_app_user', function (Blueprint $table) {
            $table->foreignUlid('app_role_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUlid('app_user_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_role_app_user');
    }
};
