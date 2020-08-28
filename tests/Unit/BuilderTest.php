<?php

namespace Tests\Unit;

use Blueprint\Blueprint;
use Blueprint\Builder;
use Blueprint\Tree;
use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

class BuilderTest extends TestCase
{
    /**
     * @test
     */
    public function execute_builds_draft_content()
    {
        $draft = 'draft blueprint content';
        $tokens = ['some', 'blueprint', 'tokens'];
        $registry = new Tree(['controllers' => [1, 2, 3]]);
        $only = [];
        $skip = [];
        $generated = ['created' => [1, 2], 'updated' => [3]];

        $blueprint = \Mockery::mock(Blueprint::class);
        $blueprint->expects('parse')
            ->with($draft, true)
            ->andReturn($tokens);
        $blueprint->expects('analyze')
            ->with($tokens + ['cache' => []])
            ->andReturn($registry);
        $blueprint->expects('generate')
            ->with($registry, $only, $skip, false)
            ->andReturn($generated);
        $blueprint->expects('dump')
            ->with($generated)
            ->andReturn('cacheable blueprint content');

        $file = \Mockery::mock(Filesystem::class);
        $file->expects('get')
            ->with('draft.yaml')
            ->andReturn($draft);
        $file->expects('exists')
            ->with('.blueprint')
            ->andReturnFalse();
        $file->expects('put')
            ->with('.blueprint', 'cacheable blueprint content');

        $actual = (new Builder)->execute($blueprint, $file, 'draft.yaml');

        $this->assertSame($generated, $actual);
    }

    /**
     * @test
     */
    public function execute_uses_cache_and_remembers_models()
    {
        $cache = [
            'models' => [4, 5, 6],
            'created' => [4],
            'unknown' => [6],
        ];
        $draft = 'draft blueprint content';
        $tokens = [
            'models' => [1, 2, 3]
        ];
        $registry = new Tree(['registry']);
        $only = [];
        $skip = [];
        $generated = ['created' => [1, 2], 'updated' => [3]];

        $blueprint = \Mockery::mock(Blueprint::class);
        $blueprint->expects('parse')
            ->with($draft, true)
            ->andReturn($tokens);
        $blueprint->expects('parse')
            ->with('cached blueprint content')
            ->andReturn($cache);
        $blueprint->expects('analyze')
            ->with($tokens + ['cache' => $cache['models']])
            ->andReturn($registry);
        $blueprint->expects('generate')
            ->with($registry, $only, $skip, false)
            ->andReturn($generated);
        $blueprint->expects('dump')
            ->with([
                'created' => [1, 2],
                'updated' => [3],
                'models' => [4, 5, 6, 1, 2, 3]
            ])
            ->andReturn('cacheable blueprint content');

        $file = \Mockery::mock(Filesystem::class);
        $file->expects('get')
            ->with('draft.yaml')
            ->andReturn($draft);
        $file->expects('exists')
            ->with('.blueprint')
            ->andReturnTrue();
        $file->expects('get')
            ->with('.blueprint')
            ->andReturn('cached blueprint content');
        $file->expects('put')
            ->with('.blueprint', 'cacheable blueprint content');

        $actual = (new Builder)->execute($blueprint, $file, 'draft.yaml');

        $this->assertSame($generated, $actual);
    }

    /**
     * @test
     */
    public function execute_calls_builder_without_stripping_dashes_for_draft_file_with_indexes_defined()
    {
        $draft = 'models:';
        $draft .= PHP_EOL . '  Post:';
        $draft .= PHP_EOL . '    indexes:';
        $draft .= PHP_EOL . '      - index: author_id';
        $draft .= PHP_EOL . '      - index: author_id, published_at';

        $tokens = [
            'models' => [1, 2, 3]
        ];
        $registry = new Tree(['registry']);
        $only = [];
        $skip = [];
        $generated = ['created' => [1, 2], 'updated' => [3]];

        $blueprint = \Mockery::mock(Blueprint::class);
        $blueprint->expects('parse')
            ->with($draft, false)
            ->andReturn($tokens);
        $blueprint->expects('analyze')
            ->with($tokens + ['cache' => []])
            ->andReturn($registry);
        $blueprint->expects('generate')
            ->with($registry, $only, $skip, false)
            ->andReturn($generated);
        $blueprint->expects('dump')
            ->with([
                'created' => [1, 2],
                'updated' => [3],
                'models' => [1, 2, 3]
            ])
            ->andReturn('cacheable blueprint content');

        $file = \Mockery::mock(Filesystem::class);
        $file->expects('get')
            ->with('draft.yaml')
            ->andReturn($draft);
        $file->expects('exists')
            ->with('.blueprint')
            ->andReturnFalse();
        $file->expects('put')
            ->with('.blueprint', 'cacheable blueprint content');

        $actual = (new Builder)->execute($blueprint, $file, 'draft.yaml');

        $this->assertSame($generated, $actual);
    }
}
