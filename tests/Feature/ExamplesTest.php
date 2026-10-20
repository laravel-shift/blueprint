<?php

namespace Tests\Feature;

use Blueprint\Blueprint;
use Blueprint\Lexers\ControllerLexer;
use Blueprint\Lexers\ModelLexer;
use Blueprint\Lexers\StatementLexer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ExamplesTest extends TestCase
{
    #[Test]
    #[DataProvider('examplesProvider')]
    public function examples_parse_and_analyze(string $example): void
    {
        $blueprint = new Blueprint;
        $blueprint->registerLexer(new ModelLexer);
        $blueprint->registerLexer(new ControllerLexer(new StatementLexer));

        $tokens = $blueprint->parse(file_get_contents($example));
        $tree = $blueprint->analyze($tokens);

        $this->assertSame(array_keys($tokens['models'] ?? []), array_keys($tree->models()));
        $this->assertSame(array_keys($tokens['controllers'] ?? []), array_keys($tree->controllers()));
    }

    public static function examplesProvider(): array
    {
        return collect(glob(__DIR__ . '/../../examples/*.yaml'))
            ->mapWithKeys(fn ($example) => [basename($example) => [$example]])
            ->all();
    }
}
