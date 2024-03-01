<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TraceTest extends TestCase
{
    #[Test]
    public function it_stores_readme_example(): void
    {
        $this->assertFileExists('.blueprint');

        $prefix = date('Y_m_d_');
        $actual = preg_replace(
            '/database\/migrations\/' . $prefix . '\d{6}/',
            'database/migrations/',
            file_get_contents('.blueprint')
        );

        $this->assertEquals($this->expectedStub(), trim($actual));
    }

    private function expectedStub(): string
    {
        return <<<STUB
created: 'app/Http/Controllers/PostController.php database/factories/PostFactory.php database/migrations/_create_posts_table.php app/Models/Post.php tests/Feature/Http/Controllers/PostControllerTest.php app/Events/NewPost.php app/Http/Requests/PostStoreRequest.php app/Jobs/SyncMedia.php app/Mail/ReviewPost.php resources/views/emails/review-post.blade.php resources/views/post/index.blade.php'
updated: routes/web.php
models:
    Post: { title: 'string:400', content: longtext, published_at: 'timestamp nullable', author_id: 'biginteger unsigned' }
    User: { name: string, email: string, email_verified_at: 'timestamp nullable', password: string, remember_token: 'string:100 nullable' }
STUB;
    }
}
