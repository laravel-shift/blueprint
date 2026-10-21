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

        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained();
            $table->foreignId('team_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('status_id')->comment('State')->constrained();
            $table->foreignId('reviewer_id')->nullable()->unique()->constrained('users');
            $table->foreignUuid('tenant_id')->unique()->constrained();
            $table->foreignUlid('session_id')->index()->constrained();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
