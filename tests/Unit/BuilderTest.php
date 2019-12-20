<?php

namespace Tests\Unit;

use Blueprint\Blueprint;
use Blueprint\Builder;
use Tests\TestCase;

class BuilderTest extends TestCase
{
    /**
     * @test
     */
    public function execute_uses_blueprint_to_build_draft()
    {
        $digits = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        shuffle($digits);

        $draft = implode($digits);
        $tokens = $digits;
        $registry = array_rand($digits, 9);
        $generated = array_rand($digits, 9);

        $blueprint = \Mockery::mock(Blueprint::class);
        $blueprint->expects('parse')
            ->with($draft)
            ->andReturn($tokens);
        $blueprint->expects('analyze')
            ->with($tokens)
            ->andReturn($registry);
        $blueprint->expects('generate')
            ->with($registry)
            ->andReturn($generated);

        $actual = Builder::execute($blueprint, $draft);

        $this->assertSame($generated, $actual);
    }
}
