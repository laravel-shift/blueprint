<?php

namespace Tests\Feature;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

final class SchemaTest extends TestCase
{
    /**
     * Drafts which are valid standard YAML, but intentionally not
     * covered by the schema. Drafts which are not valid standard
     * YAML (i.e. shorthands, dashes) are skipped automatically.
     */
    private const EXCLUDED = [
        // bare-word shorthands parsed as strings
        'invokable-controller-shorthand.yaml',
        'multiple-resource-controllers.yaml',
        'routes-mixed.yaml',
        'shorthands.yaml',
        'test-relationships.yaml',
        'with-timezones.yaml',
        'readme-example-dashes.yaml',
        // livewire components are not wired into the build
        'components-only.yaml',
        'livewire-properties-statements.yaml',
        'livewire-simple.yaml',
        'livewire-with-properties.yaml',
        // lexer fixtures which are not valid drafts
        'controllers-only.yaml',
        'custom-indexes.yaml',
        'invalid.yaml',
    ];

    #[Test]
    #[DataProvider('draftsDataProvider')]
    public function draft_validates_against_schema(string $draft): void
    {
        $result = (new Validator)->validate(
            json_decode(json_encode(Yaml::parse($this->fixture($draft)))),
            json_decode(file_get_contents(__DIR__ . '/../../schema.json'))
        );

        if ($result->hasError()) {
            $this->fail(json_encode((new ErrorFormatter)->format($result->error(), false), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        $this->assertTrue($result->isValid());
    }

    public static function draftsDataProvider(): array
    {
        return collect(glob(__DIR__ . '/../fixtures/{drafts,expanded}/*.yaml', GLOB_BRACE))
            ->map(fn ($path) => basename(dirname($path)) . '/' . basename($path))
            ->diff(array_map(fn ($draft) => 'drafts/' . $draft, self::EXCLUDED))
            ->filter(fn ($draft) => self::isStandardYaml(__DIR__ . '/../fixtures/' . $draft))
            ->mapWithKeys(fn ($draft) => [$draft => [$draft]])
            ->all();
    }

    private static function isStandardYaml(string $path): bool
    {
        try {
            Yaml::parseFile($path);
        } catch (ParseException) {
            return false;
        }

        return true;
    }
}
