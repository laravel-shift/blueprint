<?php

namespace Tests\Unit;

use Blueprint\Blueprint;
use Blueprint\Builder;
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
        $registry = ['controllers' => [1, 2, 3]];
        $generated = ['created' => [1, 2], 'updated' => [3]];

        $blueprint = \Mockery::mock(Blueprint::class);
        $blueprint->expects('parse')
            ->with($draft)
            ->andReturn($tokens);
        $blueprint->expects('analyze')
            ->with($tokens + ['cache' => []])
            ->andReturn($registry);
        $blueprint->expects('generate')
            ->with($registry)
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

        $actual = Builder::execute($blueprint, $file, 'draft.yaml');

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
        $registry = ['registry'];
        $generated = ['created' => [1, 2], 'updated' => [3]];

        $blueprint = \Mockery::mock(Blueprint::class);
        $blueprint->expects('parse')
            ->with($draft)
            ->andReturn($tokens);
        $blueprint->expects('parse')
            ->with('cached blueprint content')
            ->andReturn($cache);
        $blueprint->expects('analyze')
            ->with($tokens + ['cache' => $cache['models']])
            ->andReturn($registry);
        $blueprint->expects('generate')
            ->with($registry)
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

        $actual = Builder::execute($blueprint, $file, 'draft.yaml');

        $this->assertSame($generated, $actual);
    }
}
