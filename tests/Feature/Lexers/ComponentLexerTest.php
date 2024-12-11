<?php

namespace Feature\Lexers;

use Blueprint\Lexers\ComponentLexer;
use Blueprint\Lexers\StatementLexer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @see ComponentLexer
 */
final class ComponentLexerTest extends TestCase
{
    /**
     * @var ComponentLexer
     */
    private $subject;

    /**
     * @var \Mockery\MockInterface
     */
    private $statementLexer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->statementLexer = \Mockery::mock(StatementLexer::class);

        $this->subject = new ComponentLexer($this->statementLexer);
    }

    #[Test]
    public function it_returns_nothing_without_components_token(): void
    {
        $this->assertEquals([
            'components' => [],
        ], $this->subject->analyze([]));
    }

    #[Test]
    public function it_returns_components(): void
    {
        $tokens = [
            'components' => [
                'UpdateProfile' => [
                    'mount' => 'user, dashboard_url',
                    'update' => [
                        'validate' => 'user',
                        'dispatch' => 'ProfileUpdated with:user',
                        'redirect' => 'dashboard_url',
                    ],
                ],
                'OverrideComponent' => [
                    'render' => [
                        'render' => 'custom.view',
                    ],
                ],
            ],
        ];

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'validate' => 'user',
                'dispatch' => 'ProfileUpdated with:user',
                'redirect' => 'dashboard_url',
            ])
            ->andReturn(['validate-statement', 'dispatch-statement', 'redirect-statement']);
        $this->statementLexer->shouldReceive('analyze')
            ->with(['render' => 'livewire.update-profile'])
            ->andReturn(['render-statement']);
        $this->statementLexer->shouldReceive('analyze')
            ->with(['render' => 'custom.view'])
            ->andReturn(['custom-render-statement']);

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(2, $actual['components']);

        $component = $actual['components']['UpdateProfile'];
        $this->assertEquals('UpdateProfile', $component->name());

        $properties = $component->properties();
        $this->assertCount(2, $properties);
        $this->assertArrayHasKey('user', $properties);
        $this->assertArrayHasKey('dashboard_url', $properties);

        $methods = $component->methods();
        $this->assertCount(2, $methods);

        $this->assertCount(3, $methods['update']);
        $this->assertEquals('validate-statement', $methods['update'][0]);
        $this->assertEquals('dispatch-statement', $methods['update'][1]);
        $this->assertEquals('redirect-statement', $methods['update'][2]);

        $this->assertCount(1, $methods['render']);
        $this->assertEquals('render-statement', $methods['render'][0]);

        $component = $actual['components']['OverrideComponent'];
        $this->assertEquals('OverrideComponent', $component->name());

        $properties = $component->properties();
        $this->assertCount(0, $properties);

        $methods = $component->methods();
        $this->assertCount(1, $methods);

        $this->assertCount(1, $methods['render']);
        $this->assertEquals('custom-render-statement', $methods['render'][0]);
    }
}
