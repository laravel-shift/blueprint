<?php

namespace Tests\Feature;

use Blueprint\Blueprint;
use Blueprint\Validator;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see Validator
 */
final class ValidatorTest extends TestCase
{
    private Validator $subject;

    private string $cwd;

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new Validator(new Filesystem);

        $this->cwd = getcwd();
        $this->directory = sys_get_temp_dir() . '/blueprint-validator-' . uniqid();
        mkdir($this->directory . '/bootstrap', 0755, true);
        file_put_contents($this->directory . '/bootstrap/app.php', '<?php');
        chdir($this->directory);
    }

    protected function tearDown(): void
    {
        chdir($this->cwd);
        (new Filesystem)->deleteDirectory($this->directory);

        parent::tearDown();
    }

    #[Test]
    public function it_returns_no_diagnostics_for_a_valid_draft(): void
    {
        $this->assertSame([], $this->validate('drafts/readme-example.yaml'));
    }

    #[Test]
    public function it_does_not_write_files(): void
    {
        $this->validate('drafts/readme-example.yaml');

        $this->assertSame(
            ['bootstrap/app.php'],
            array_map(fn ($file) => $file->getRelativePathname(), (new Filesystem)->allFiles($this->directory))
        );
    }

    #[Test]
    public function it_returns_an_error_for_invalid_yaml(): void
    {
        $this->assertSame([
            ['line' => 4, 'severity' => 'error', 'message' => 'Unable to parse (near "published_at timestamp").'],
        ], $this->validate('validate/invalid-yaml.yaml'));
    }

    #[Test]
    public function it_returns_an_error_when_the_build_fails(): void
    {
        $this->assertSame([
            ['line' => null, 'severity' => 'error', 'message' => 'The model class [App\Models\Post] could not be found.'],
        ], $this->validate('validate/missing-model.yaml'));
    }

    #[Test]
    public function it_returns_warnings_for_statements_which_generate_invalid_code(): void
    {
        $this->assertSame([
            ['line' => 11, 'severity' => 'warning', 'message' => 'The [save] statement expects a reference such as `post` or `post.id`, but [post with:published_at] was given.'],
            ['line' => 12, 'severity' => 'warning', 'message' => 'Unknown statement [redner] will be ignored.'],
            ['line' => 14, 'severity' => 'warning', 'message' => 'The [fire] statement expects a name followed by an optional `with:` list, but [post.published with:post title] was given.'],
        ], $this->validate('validate/statements.yaml'));
    }

    private function validate(string $fixture): array
    {
        return $this->subject->validate(resolve(Blueprint::class), __DIR__ . '/../fixtures/' . $fixture);
    }
}
