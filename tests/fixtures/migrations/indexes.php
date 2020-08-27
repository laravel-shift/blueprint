<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->string('title');
            $table->unsignedBigInteger('parent_post_id');
            $table->unsignedBigInteger('author_id');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('word_count');
            $table->geometry('location');
            $table->primary('id');
            $table->index('author_id');
            $table->index(['author_id', 'published_at']);
            $table->unique('title');
            $table->unique(['title', 'parent_post_id']);
            $table->spatialIndex('location');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('posts');
    }
}
