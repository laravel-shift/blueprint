<?php

namespace Tests\Feature\Generators\Statements;

use Blueprint\Blueprint;
use Blueprint\Generators\Statements\NotificationGenerator;
use Blueprint\Lexers\StatementLexer;
use Blueprint\Tree;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see NotificationGenerator
 */
final class NotificationGeneratorTest extends TestCase
{
    private $blueprint;

    protected $files;

    /** @var NotificationGenerator */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new NotificationGenerator($this->files);

        $this->blueprint = new Blueprint;
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ControllerLexer(new StatementLexer));
        $this->blueprint->registerGenerator($this->subject);
    }

    #[Test]
    public function output_writes_nothing_for_empty_tree(): void
    {
        $this->filesystem->expects('stub')
            ->with('notification.stub')
            ->andReturn($this->stub('notification.stub'));

        $this->filesystem->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(new Tree(['controllers' => []])));
    }

    #[Test]
    public function output_writes_nothing_tree_without_validate_statements(): void
    {
        $this->filesystem->expects('stub')
            ->with('notification.stub')
            ->andReturn($this->stub('notification.stub'));

        $this->filesystem->shouldNotHaveReceived('put');

        $tokens = $this->blueprint->parse($this->fixture('drafts/render-statements.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }

    #[Test]
    #[DataProvider('notificationDraftProvider')]
    public function output_writes_notifications($draft): void
    {
        $this->filesystem->expects('stub')
            ->with('notification.stub')
            ->andReturn($this->stub('notification.stub'));
        $this->filesystem->shouldReceive('stub')
            ->with('constructor.stub')
            ->andReturn($this->stub('constructor.stub'));

        if ($draft === 'drafts/send-statements-notification-facade.yaml') {
            $this->filesystem->shouldReceive('stub')
                ->with('constructor.stub')
                ->andReturn($this->stub('constructor.stub'));
        }

        $this->filesystem->shouldReceive('exists')
            ->twice()
            ->with('app/Notification')
            ->andReturns(false, true);
        $this->filesystem->expects('exists')
            ->with('app/Notification/ReviewPostNotification.php')
            ->andReturnFalse();
        $this->filesystem->expects('makeDirectory')
            ->with('app/Notification', 0755, true);
        $this->filesystem->expects('put')
            ->with('app/Notification/ReviewPostNotification.php', $this->fixture('notifications/review-post.php'));
        $this->filesystem->expects('exists')
            ->with('app/Notification/PublishedPostNotification.php')
            ->andReturnFalse();
        $this->filesystem->expects('put')
            ->with('app/Notification/PublishedPostNotification.php', $this->fixture('notifications/published-post.php'));

        $tokens = $this->blueprint->parse($this->fixture($draft));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/Notification/ReviewPostNotification.php', 'app/Notification/PublishedPostNotification.php']], $this->subject->output($tree));
    }

    #[Test]
    public function it_only_outputs_new_notifications(): void
    {
        $this->filesystem->expects('stub')
            ->with('notification.stub')
            ->andReturn($this->stub('notification.stub'));
        $this->filesystem->expects('exists')
            ->with('app/Notification/ReviewPostNotification.php')
            ->andReturnTrue();
        $this->filesystem->expects('exists')
            ->with('app/Notification/PublishedPostNotification.php')
            ->andReturnTrue();

        $tokens = $this->blueprint->parse($this->fixture('drafts/send-statements-notification-facade.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }

    #[Test]
    public function it_respects_configuration(): void
    {
        $this->app['config']->set('blueprint.namespace', 'Some\\App');
        $this->app['config']->set('blueprint.app_path', 'src/path');

        $this->filesystem->expects('stub')
            ->with('notification.stub')
            ->andReturn($this->stub('notification.stub'));
        $this->filesystem->expects('stub')
            ->with('constructor.stub')
            ->andReturn($this->stub('constructor.stub'));
        $this->filesystem->expects('exists')
            ->with('src/path/Notification')
            ->andReturnFalse();
        $this->filesystem->expects('exists')
            ->with('src/path/Notification/ReviewNotification.php')
            ->andReturnFalse();
        $this->filesystem->expects('makeDirectory')
            ->with('src/path/Notification', 0755, true);
        $this->filesystem->expects('put')
            ->with('src/path/Notification/ReviewNotification.php', $this->fixture('notifications/notification-configured.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/readme-example-notification-facade.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['src/path/Notification/ReviewNotification.php']], $this->subject->output($tree));
    }

    #[Test]
    public function it_respects_configuration_for_property_promotion(): void
    {
        $this->app['config']->set('blueprint.namespace', 'Some\\App');
        $this->app['config']->set('blueprint.app_path', 'src/path');
        $this->app['config']->set('blueprint.property_promotion', true);

        $this->filesystem->expects('stub')
            ->with('notification.stub')
            ->andReturn($this->stub('notification.stub'));
        $this->filesystem->expects('stub')
            ->with('constructor.stub')
            ->andReturn($this->stub('constructor.stub'));
        $this->filesystem->expects('exists')
            ->with('src/path/Notification')
            ->andReturnFalse();
        $this->filesystem->expects('exists')
            ->with('src/path/Notification/ReviewNotification.php')
            ->andReturnFalse();
        $this->filesystem->expects('makeDirectory')
            ->with('src/path/Notification', 0755, true);
        $this->filesystem->expects('put')
            ->with('src/path/Notification/ReviewNotification.php', $this->fixture('notifications/notification-configured-with-constructor-property-promotion.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/readme-example-notification-facade.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['src/path/Notification/ReviewNotification.php']], $this->subject->output($tree));
    }

    public static function notificationDraftProvider(): array
    {
        return [
            ['drafts/send-statements-notification-facade.yaml'],
            ['drafts/send-statements-notification-model.yaml'],
        ];
    }
}
