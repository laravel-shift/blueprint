<?php

namespace Tests\Feature\Generators\Statements;

use Blueprint\Blueprint;
use Blueprint\Generators\Statements\MailGenerator;
use Blueprint\Lexers\StatementLexer;
use Blueprint\Tree;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see MailGenerator
 */
final class MailGeneratorTest extends TestCase
{
    private $blueprint;

    protected $files;

    /** @var MailGenerator */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new MailGenerator($this->files);

        $this->blueprint = new Blueprint;
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ControllerLexer(new StatementLexer));
        $this->blueprint->registerGenerator($this->subject);
    }

    #[Test]
    public function output_writes_nothing_for_empty_tree(): void
    {
        $this->filesystem->expects('stub')
            ->with('mail.stub')
            ->andReturn($this->stub('mail.stub'));
        $this->filesystem->expects('stub')
            ->with('mail.view.stub')
            ->andReturn($this->stub('mail.view.stub'));

        $this->filesystem->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(new Tree(['controllers' => []])));
    }

    #[Test]
    public function output_writes_nothing_tree_without_validate_statements(): void
    {
        $this->filesystem->expects('stub')
            ->with('mail.stub')
            ->andReturn($this->stub('mail.stub'));
        $this->filesystem->expects('stub')
            ->with('mail.view.stub')
            ->andReturn($this->stub('mail.view.stub'));

        $this->filesystem->shouldNotHaveReceived('put');

        $tokens = $this->blueprint->parse($this->fixture('drafts/render-statements.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }

    #[Test]
    public function output_writes_mails(): void
    {
        $this->filesystem->expects('stub')
            ->with('mail.stub')
            ->andReturn($this->stub('mail.stub'));
        $this->filesystem->expects('stub')
            ->with('mail.view.stub')
            ->andReturn($this->stub('mail.view.stub'));
        $this->filesystem->shouldReceive('stub')
            ->with('constructor.stub')
            ->andReturn($this->stub('constructor.stub'));
        $this->filesystem->expects('exists')
            ->twice()
            ->with('app/Mail')
            ->andReturns(false, true);
        $this->filesystem->expects('exists')
            ->twice()
            ->with('resources/views/emails')
            ->andReturns(false, true);
        $this->filesystem->expects('exists')
            ->with('app/Mail/ReviewPost.php')
            ->andReturnFalse();
        $this->filesystem->expects('makeDirectory')
            ->with('app/Mail', 0755, true);
        $this->filesystem->expects('put')
            ->with('app/Mail/ReviewPost.php', $this->fixture('mailables/review-post.php'));
        $this->filesystem->expects('exists')
            ->with('resources/views/emails/review-post.blade.php')
            ->andReturnFalse();
        $this->filesystem->expects('makeDirectory')
            ->with('resources/views/emails', 0755, true);
        $this->filesystem->expects('put')
            ->with('resources/views/emails/review-post.blade.php', $this->fixture('mailables/review-post-view.blade.php'));

        $this->filesystem->expects('exists')
            ->with('app/Mail/PublishedPost.php')
            ->andReturnFalse();
        $this->filesystem->expects('put')
            ->with('app/Mail/PublishedPost.php', $this->fixture('mailables/published-post.php'));
        $this->filesystem->expects('exists')
            ->with('resources/views/emails/published-post.blade.php')
            ->andReturnFalse();
        $this->filesystem->expects('put')
            ->with('resources/views/emails/published-post.blade.php', $this->fixture('mailables/published-post-view.blade.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/send-statements.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([
            'created' => [
                'app/Mail/ReviewPost.php',
                'resources/views/emails/review-post.blade.php',
                'app/Mail/PublishedPost.php',
                'resources/views/emails/published-post.blade.php',
            ],
        ], $this->subject->output($tree));
    }

    #[Test]
    public function it_only_outputs_new_mails(): void
    {
        $this->filesystem->expects('stub')
            ->with('mail.stub')
            ->andReturn($this->stub('mail.stub'));
        $this->filesystem->expects('stub')
            ->with('mail.view.stub')
            ->andReturn($this->stub('mail.view.stub'));
        $this->filesystem->expects('exists')
            ->with('app/Mail/ReviewPost.php')
            ->andReturnTrue();
        $this->filesystem->expects('exists')
            ->with('app/Mail/PublishedPost.php')
            ->andReturnTrue();

        $tokens = $this->blueprint->parse($this->fixture('drafts/send-statements.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }

    #[Test]
    public function it_respects_configuration(): void
    {
        $this->app['config']->set('blueprint.namespace', 'Some\\App');
        $this->app['config']->set('blueprint.app_path', 'src/path');

        $this->filesystem->expects('stub')
            ->with('mail.stub')
            ->andReturn($this->stub('mail.stub'));
        $this->filesystem->expects('stub')
            ->with('mail.view.stub')
            ->andReturn($this->stub('mail.view.stub'));
        $this->filesystem->expects('stub')
            ->with('constructor.stub')
            ->andReturn($this->stub('constructor.stub'));
        $this->filesystem->expects('exists')
            ->with('src/path/Mail')
            ->andReturnFalse();
        $this->filesystem->expects('exists')
            ->with('src/path/Mail/ReviewPost.php')
            ->andReturnFalse();
        $this->filesystem->expects('makeDirectory')
            ->with('src/path/Mail', 0755, true);
        $this->filesystem->expects('put')
            ->with('src/path/Mail/ReviewPost.php', $this->fixture('mailables/mail-configured.php'));
        $this->filesystem->expects('exists')
            ->with('resources/views/emails/review-post.blade.php')
            ->andReturnFalse();
        $this->filesystem->expects('makeDirectory')
            ->with('resources/views/emails', 0755, true);
        $this->filesystem->expects('put')
            ->with('resources/views/emails/review-post.blade.php', $this->fixture('mailables/review-post-view.blade.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/readme-example.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([
            'created' => [
                'src/path/Mail/ReviewPost.php',
                'resources/views/emails/review-post.blade.php',
            ],
        ], $this->subject->output($tree));
    }

    #[Test]
    public function it_respects_configuration_for_property_promotion(): void
    {
        $this->app['config']->set('blueprint.namespace', 'Some\\App');
        $this->app['config']->set('blueprint.app_path', 'src/path');
        $this->app['config']->set('blueprint.property_promotion', true);

        $this->filesystem->expects('stub')
            ->with('mail.stub')
            ->andReturn($this->stub('mail.stub'));
        $this->filesystem->expects('stub')
            ->with('mail.view.stub')
            ->andReturn($this->stub('mail.view.stub'));
        $this->filesystem->expects('stub')
            ->with('constructor.stub')
            ->andReturn($this->stub('constructor.stub'));
        $this->filesystem->expects('exists')
            ->with('src/path/Mail')
            ->andReturnFalse();
        $this->filesystem->expects('exists')
            ->with('src/path/Mail/ReviewPost.php')
            ->andReturnFalse();
        $this->filesystem->expects('makeDirectory')
            ->with('src/path/Mail', 0755, true);
        $this->filesystem->expects('put')
            ->with('src/path/Mail/ReviewPost.php', $this->fixture('mailables/mail-configured-with-constructor-property-promotion.php'));
        $this->filesystem->expects('exists')
            ->with('resources/views/emails/review-post.blade.php')
            ->andReturnFalse();
        $this->filesystem->expects('makeDirectory')
            ->with('resources/views/emails', 0755, true);
        $this->filesystem->expects('put')
            ->with('resources/views/emails/review-post.blade.php', $this->fixture('mailables/review-post-view.blade.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/readme-example.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([
            'created' => [
                'src/path/Mail/ReviewPost.php',
                'resources/views/emails/review-post.blade.php',
            ],
        ], $this->subject->output($tree));
    }

    #[Test]
    public function output_writes_mails_but_not_existing_templates(): void
    {
        $this->filesystem->expects('stub')
            ->with('mail.stub')
            ->andReturn($this->stub('mail.stub'));
        $this->filesystem->expects('stub')
            ->with('mail.view.stub')
            ->andReturn($this->stub('mail.view.stub'));
        $this->filesystem->shouldReceive('stub')
            ->with('constructor.stub')
            ->andReturn($this->stub('constructor.stub'));
        $this->filesystem->expects('exists')
            ->twice()
            ->with('app/Mail')
            ->andReturns(false);
        $this->filesystem->expects('exists')
            ->with('app/Mail/ReviewPost.php')
            ->andReturnFalse();
        $this->filesystem->expects('put')
            ->with('app/Mail/ReviewPost.php', $this->fixture('mailables/review-post.php'));
        $this->filesystem->expects('exists')
            ->with('resources/views/emails/review-post.blade.php')
            ->andReturnTrue();

        $this->filesystem->expects('exists')
            ->with('app/Mail/PublishedPost.php')
            ->andReturnFalse();
        $this->filesystem->expects('put')
            ->with('app/Mail/PublishedPost.php', $this->fixture('mailables/published-post.php'));
        $this->filesystem->expects('exists')
            ->with('resources/views/emails/published-post.blade.php')
            ->andReturnTrue();

        $tokens = $this->blueprint->parse($this->fixture('drafts/send-statements.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([
            'created' => [
                'app/Mail/ReviewPost.php',
                'app/Mail/PublishedPost.php',
            ],
        ], $this->subject->output($tree));
    }

    #[Test]
    public function output_writes_mail_with_custom_template(): void
    {
        $this->filesystem->expects('stub')
            ->with('mail.stub')
            ->andReturn($this->stub('mail.stub'));
        $this->filesystem->expects('stub')
            ->with('mail.view.stub')
            ->andReturn($this->stub('mail.view.stub'));
        $this->filesystem->shouldReceive('stub')
            ->with('constructor.stub')
            ->andReturn($this->stub('constructor.stub'));
        $this->filesystem->expects('exists')
            ->with('app/Mail')
            ->andReturns(true);
        $this->filesystem->expects('exists')
            ->with('resources/views/emails/admin')
            ->andReturns(false);
        $this->filesystem->expects('exists')
            ->with('app/Mail/AddedAdmin.php')
            ->andReturnFalse();
        $this->filesystem->expects('put')
            ->with('app/Mail/AddedAdmin.php', $this->fixture('mailables/added-admin.php'));
        $this->filesystem->expects('exists')
            ->with('resources/views/emails/admin/added.blade.php')
            ->andReturnFalse();
        $this->filesystem->expects('makeDirectory')
            ->with('resources/views/emails/admin', 0755, true);
        $this->filesystem->expects('put')
            ->with('resources/views/emails/admin/added.blade.php', $this->fixture('mailables/added-admin-view.blade.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/send-statement-with-view.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([
            'created' => [
                'app/Mail/AddedAdmin.php',
                'resources/views/emails/admin/added.blade.php',
            ],
        ], $this->subject->output($tree));
    }
}
