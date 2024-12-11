<?php

namespace Tests\Feature\Generators;

use Blueprint\Blueprint;
use Blueprint\Generators\PolicyGenerator;
use Blueprint\Lexers\StatementLexer;
use Blueprint\Tree;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see PolicyGenerator
 */
final class PolicyGeneratorTest extends TestCase
{
    private $blueprint;

    /** @var PolicyGenerator */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new PolicyGenerator($this->filesystem);

        $this->blueprint = new Blueprint;
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ModelLexer);
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ControllerLexer(new StatementLexer));
        $this->blueprint->registerGenerator($this->subject);
    }

    #[Test]
    public function output_writes_nothing_for_empty_tree(): void
    {
        $this->filesystem->expects('stub')
            ->with('policy.class.stub')
            ->andReturn($this->stub('policy.class.stub'));

        $this->filesystem->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(new Tree(['controllers' => [], 'policies' => []])));
    }

    #[Test]
    public function output_generates_policies_for_controller_with_all_policies(): void
    {
        $definition = 'drafts/policy-with-all-policies.yaml';
        $path = 'app/Policies/PostPolicy.php';
        $policy = 'policies/with-all-policies.php';

        $this->filesystem->expects('stub')
            ->with('policy.class.stub')
            ->andReturn($this->stub('policy.class.stub'));
        $this->filesystem->expects('stub')
            ->with('policy.method.viewAny.stub')
            ->andReturn($this->stub('policy.method.viewAny.stub'));
        $this->filesystem->expects('stub')
            ->with('policy.method.view.stub')
            ->andReturn($this->stub('policy.method.view.stub'));
        $this->filesystem->expects('stub')
            ->with('policy.method.create.stub')
            ->andReturn($this->stub('policy.method.create.stub'));
        $this->filesystem->expects('stub')
            ->with('policy.method.update.stub')
            ->andReturn($this->stub('policy.method.update.stub'));
        $this->filesystem->expects('stub')
            ->with('policy.method.delete.stub')
            ->andReturn($this->stub('policy.method.delete.stub'));

        $this->filesystem->expects('exists')
            ->with(dirname($path))
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->with($path, $this->fixture($policy));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
    }

    #[Test]
    public function output_generates_policies_for_controller_with_some_policies(): void
    {
        $definition = 'drafts/policy-with-some-policies.yaml';
        $path = 'app/Policies/PostPolicy.php';
        $policy = 'policies/with-some-policies.php';

        $this->filesystem->expects('stub')
            ->with('policy.class.stub')
            ->andReturn($this->stub('policy.class.stub'));
        $this->filesystem->expects('stub')
            ->with('policy.method.viewAny.stub')
            ->andReturn($this->stub('policy.method.viewAny.stub'));
        $this->filesystem->expects('stub')
            ->with('policy.method.view.stub')
            ->andReturn($this->stub('policy.method.view.stub'));

        $this->filesystem->expects('exists')
            ->with(dirname($path))
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->with($path, $this->fixture($policy));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
    }

    #[Test]
    public function output_generates_policies_for_controller_with_authorize_resource(): void
    {
        $definition = 'drafts/policy-with-authorize-resource.yaml';
        $path = 'app/Policies/PostPolicy.php';
        $policy = 'policies/with-authorize-resource.php';

        $this->filesystem->expects('stub')
            ->with('policy.class.stub')
            ->andReturn($this->stub('policy.class.stub'));
        $this->filesystem->expects('stub')
            ->with('policy.method.viewAny.stub')
            ->andReturn($this->stub('policy.method.viewAny.stub'));
        $this->filesystem->expects('stub')
            ->with('policy.method.view.stub')
            ->andReturn($this->stub('policy.method.view.stub'));
        $this->filesystem->expects('stub')
            ->with('policy.method.create.stub')
            ->andReturn($this->stub('policy.method.create.stub'));
        $this->filesystem->expects('stub')
            ->with('policy.method.update.stub')
            ->andReturn($this->stub('policy.method.update.stub'));
        $this->filesystem->expects('stub')
            ->with('policy.method.delete.stub')
            ->andReturn($this->stub('policy.method.delete.stub'));

        $this->filesystem->expects('exists')
            ->with(dirname($path))
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->with($path, $this->fixture($policy));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
    }
}
