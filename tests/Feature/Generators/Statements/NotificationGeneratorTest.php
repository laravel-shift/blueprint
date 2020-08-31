<?php

namespace Tests\Feature\Generator\Statements;

use Blueprint\Blueprint;
use Blueprint\Generators\Statements\NotificationGenerator;
use Blueprint\Lexers\StatementLexer;
use Blueprint\Tree;
use Tests\TestCase;

/**
 * @see NotificationGenerator
 */
class NotificationGeneratorTest extends TestCase
{
    private $blueprint;

    private $files;

    /** @var NotificationGenerator */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = \Mockery::mock();
        $this->subject = new NotificationGenerator($this->files);

        $this->blueprint = new Blueprint();
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ControllerLexer(new StatementLexer()));
        $this->blueprint->registerGenerator($this->subject);
    }

    /**
     * @test
     */
    public function output_writes_nothing_for_empty_tree()
    {
        $this->files->expects('stub')
            ->with('notification.stub')
            ->andReturn($this->stub('notification.stub'));

        $this->files->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(new Tree(['controllers' => []])));
    }

    /**
     * @test
     */
    public function output_writes_nothing_tree_without_validate_statements()
    {
        $this->files->expects('stub')
            ->with('notification.stub')
            ->andReturn($this->stub('notification.stub'));

        $this->files->shouldNotHaveReceived('put');

        $tokens = $this->blueprint->parse($this->fixture('drafts/render-statements.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }

    /**
     * @test
     * @dataProvider notificationDraftProvider
     */
    public function output_writes_notifications($draft)
    {
        $this->files->expects('stub')
            ->with('notification.stub')
            ->andReturn($this->stub('notification.stub'));

        if ($draft === 'drafts/send-statements-notification-facade.yaml') {
            $this->files->expects('stub')
                ->with('constructor.stub')
                ->andReturn($this->stub('constructor.stub'));
        }

        $this->files->shouldReceive('exists')
            ->twice()
            ->with('app/Notification')
            ->andReturns(false, true);
        $this->files->expects('exists')
            ->with('app/Notification/ReviewPostNotification.php')
            ->andReturnFalse();
        $this->files->expects('makeDirectory')
            ->with('app/Notification', 0755, true);
        $this->files->expects('put')
            ->with('app/Notification/ReviewPostNotification.php', $this->fixture('notifications/review-post.php'));

        $this->files->expects('exists')
            ->with('app/Notification/PublishedPostNotification.php')
            ->andReturnFalse();
        $this->files->expects('put')
            ->with('app/Notification/PublishedPostNotification.php', $this->fixture('notifications/published-post.php'));

        $tokens = $this->blueprint->parse($this->fixture($draft));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/Notification/ReviewPostNotification.php', 'app/Notification/PublishedPostNotification.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function it_only_outputs_new_notifications()
    {
        $this->files->expects('stub')
            ->with('notification.stub')
            ->andReturn($this->stub('notification.stub'));

        $this->files->expects('exists')
            ->with('app/Notification/ReviewPostNotification.php')
            ->andReturnTrue();
        $this->files->expects('exists')
            ->with('app/Notification/PublishedPostNotification.php')
            ->andReturnTrue();

        $tokens = $this->blueprint->parse($this->fixture('drafts/send-statements-notification-facade.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function it_respects_configuration()
    {
        $this->app['config']->set('blueprint.namespace', 'Some\\App');
        $this->app['config']->set('blueprint.app_path', 'src/path');

        $this->files->expects('stub')
            ->with('notification.stub')
            ->andReturn($this->stub('notification.stub'));

        $this->files->expects('exists')
            ->with('src/path/Notification')
            ->andReturnFalse();
        $this->files->expects('exists')
            ->with('src/path/Notification/ReviewNotification.php')
            ->andReturnFalse();
        $this->files->expects('makeDirectory')
            ->with('src/path/Notification', 0755, true);
        $this->files->expects('put')
            ->with('src/path/Notification/ReviewNotification.php', $this->fixture('notifications/notification-configured.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/readme-example-notification-facade.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['src/path/Notification/ReviewNotification.php']], $this->subject->output($tree));
    }

    public function notificationDraftProvider()
    {
        return [
            ['drafts/send-statements-notification-facade.yaml'],
            ['drafts/send-statements-notification-model.yaml']
        ];
    }
}
