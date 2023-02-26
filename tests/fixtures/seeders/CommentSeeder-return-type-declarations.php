<?php

namespace Database\Seeders;

use App\Models\Blog\Comment;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Comment::factory()->count(5)->create();
    }
}
