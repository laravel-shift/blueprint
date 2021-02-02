<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained();
            $table->foreignId('post_id')->constrained()->onDelete('set null');
            $table->foreignId('comment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('approver_id')->constrained('users')->onDelete('no action');
            $table->timestamps();
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
        Schema::dropIfExists('comments');
    }
}
