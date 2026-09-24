<?php

namespace Tests\Feature\Commands;

use Blueprint\Blueprint;
use Blueprint\Validator;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \Blueprint\Commands\ValidateCommand
 */
final class ValidateCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[Test]
    public function it_uses_the_default_draft_file(): void
    {
        $this->filesystem->expects('exists')
            ->with('draft.yaml')
            ->andReturnTrue();

        $this->mock(Validator::class)
            ->expects('validate')
            ->with(resolve(Blueprint::class), 'draft.yaml')
            ->andReturn([]);

        $this->artisan('blueprint:validate')
            ->expectsOutput('draft.yaml: Draft is valid.')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_fails_if_the_draft_file_not_exists(): void
    {
        $this->filesystem->expects('exists')
            ->with('test.yml')
            ->andReturnFalse();

        $this->mock(Validator::class)
            ->shouldNotReceive('validate');

        $this->artisan('blueprint:validate test.yml')
            ->expectsOutput('test.yml: error: Draft file could not be found.')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_outputs_diagnostics_and_fails_for_errors(): void
    {
        $this->filesystem->expects('exists')
            ->with('test.yml')
            ->andReturnTrue();

        $this->mock(Validator::class)
            ->expects('validate')
            ->with(resolve(Blueprint::class), 'test.yml')
            ->andReturn([
                ['line' => 3, 'severity' => 'warning', 'message' => 'Unknown statement [redner] will be ignored.'],
                ['line' => null, 'severity' => 'error', 'message' => 'The model class [App\Models\Post] could not be found.'],
            ]);

        $this->artisan('blueprint:validate test.yml')
            ->expectsOutput('test.yml:3: warning: Unknown statement [redner] will be ignored.')
            ->expectsOutput('test.yml: error: The model class [App\Models\Post] could not be found.')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_passes_with_only_warnings(): void
    {
        $this->filesystem->expects('exists')
            ->with('draft.yaml')
            ->andReturnTrue();

        $this->mock(Validator::class)
            ->expects('validate')
            ->andReturn([
                ['line' => 3, 'severity' => 'warning', 'message' => 'Unknown statement [redner] will be ignored.'],
            ]);

        $this->artisan('blueprint:validate')
            ->expectsOutput('draft.yaml:3: warning: Unknown statement [redner] will be ignored.')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_expands_shorthand_before_validating(): void
    {
        $this->filesystem->expects('exists')
            ->with('draft.yaml')
            ->andReturnTrue();

        $this->filesystem->expects('get')
            ->with('draft.yaml')
            ->andReturn("models:\n  Post:\n    softDeletes\n");

        $this->filesystem->expects('put')
            ->with('draft.yaml', "models:\n  Post:\n    softDeletes: true\n");

        $this->mock(Validator::class)
            ->expects('validate')
            ->andReturn([]);

        $this->artisan('blueprint:validate --expand')
            ->expectsOutput('draft.yaml: Expanded shorthand into standard YAML.')
            ->expectsOutput('draft.yaml: Draft is valid.')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_does_not_write_the_draft_when_there_is_nothing_to_expand(): void
    {
        $this->filesystem->expects('exists')
            ->with('draft.yaml')
            ->andReturnTrue();

        $this->filesystem->expects('get')
            ->with('draft.yaml')
            ->andReturn("models:\n  Post:\n    softDeletes: true\n");

        $this->mock(Validator::class)
            ->expects('validate')
            ->andReturn([]);

        $this->artisan('blueprint:validate --expand')
            ->doesntExpectOutput('draft.yaml: Expanded shorthand into standard YAML.')
            ->assertExitCode(0);

        $this->filesystem->shouldNotHaveReceived('put');
    }
}
