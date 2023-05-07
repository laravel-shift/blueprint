<?php

namespace Blueprint;

use Blueprint\Contracts\Generator;
use Blueprint\Contracts\Lexer;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

class Blueprint
{
    private $lexers = [];

    private $generators = [];

    public static function relativeNamespace(string $fullyQualifiedClassName)
    {
        $namespace = config('blueprint.namespace') . '\\';
        $reference = ltrim($fullyQualifiedClassName, '\\');

        if (Str::startsWith($reference, $namespace)) {
            return Str::after($reference, $namespace);
        }

        return $reference;
    }

    public static function appPath()
    {
        return str_replace('\\', '/', config('blueprint.app_path'));
    }

    public function parse($content, $strip_dashes = true)
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        if ($strip_dashes) {
            $content = preg_replace('/^(\s*)-\s*/m', '\1', $content);
        }

        $content = $this->transformDuplicatePropertyKeys($content);

        $content = preg_replace_callback(
            '/^(\s+)(id|timestamps(Tz)?|softDeletes(Tz)?)$/mi',
            fn ($matches) => $matches[1] . strtolower($matches[2]) . ': ' . $matches[2],
            $content
        );

        $content = preg_replace_callback(
            '/^(\s+)(id|timestamps(Tz)?|softDeletes(Tz)?): true$/mi',
            fn ($matches) => $matches[1] . strtolower($matches[2]) . ': ' . $matches[2],
            $content
        );

        $content = preg_replace_callback(
            '/^(\s+)resource?$/mi',
            fn ($matches) => $matches[1] . 'resource: web',
            $content
        );

        $content = preg_replace_callback(
            '/^(\s+)invokable?$/mi',
            fn ($matches) => $matches[1] . 'invokable: true',
            $content
        );

        $content = preg_replace_callback(
            '/^(\s+)uuid(: true)?$/mi',
            fn ($matches) => $matches[1] . 'id: uuid primary',
            $content
        );

        return Yaml::parse($content);
    }

    public function analyze(array $tokens)
    {
        $registry = [
            'models' => [],
            'controllers' => [],
        ];

        foreach ($this->lexers as $lexer) {
            $registry = array_merge($registry, $lexer->analyze($tokens));
        }

        return new Tree($registry);
    }

    public function generate(Tree $tree, array $only = [], array $skip = [], $overwriteMigrations = false): array
    {
        $components = [];

        foreach ($this->generators as $generator) {
            if ($this->shouldGenerate($generator->types(), $only, $skip)) {
                $components = array_merge_recursive($components, $generator->output($tree, $overwriteMigrations));
            }
        }

        return $components;
    }

    public function dump(array $generated)
    {
        return Yaml::dump($generated);
    }

    public function registerLexer(Lexer $lexer)
    {
        $this->lexers[] = $lexer;
    }

    public function registerGenerator(Generator $generator)
    {
        $this->generators[] = $generator;
    }

    public function swapGenerator(string $concrete, Generator $generator)
    {
        foreach ($this->generators as $key => $registeredGenerator) {
            if (get_class($registeredGenerator) === $concrete) {
                unset($this->generators[$key]);
            }
        }

        $this->registerGenerator($generator);
    }

    protected function shouldGenerate(array $types, array $only, array $skip): bool
    {
        if (count($only)) {
            return collect($types)->intersect($only)->isNotEmpty();
        }

        if (count($skip)) {
            return collect($types)->intersect($skip)->isEmpty();
        }

        return true;
    }

    private function transformDuplicatePropertyKeys(string $content): string
    {
        preg_match('/^controllers:$/m', $content, $matches, PREG_OFFSET_CAPTURE);

        if (empty($matches)) {
            return $content;
        }

        $offset = $matches[0][1];
        $lines = explode("\n", substr($content, $offset));

        $methods = [];
        $statements = [];
        foreach ($lines as $index => $line) {
            $method = preg_match('/^( {2}| {4}|\t){2}\w+:$/', $line);
            if ($method) {
                $methods[] = $statements ?? [];
                $statements = [];

                continue;
            }

            preg_match('/^( {2}| {4}|\t){3}(dispatch|fire|notify|send):\s/', $line, $matches);
            if (empty($matches)) {
                continue;
            }

            $statements[$index] = $matches[2];
        }

        $methods[] = $statements ?? [];

        $multiples = collect($methods)
            ->filter(fn ($statements) => count(array_unique($statements)) !== count($statements))
            ->mapWithKeys(fn ($statements) => $statements);

        if ($multiples->isEmpty()) {
            return $content;
        }

        foreach ($multiples as $line => $statement) {
            $lines[$line] = preg_replace(
                '/^(\s+)' . $statement . ':/',
                '$1' . $statement . '-' . $line . ':',
                $lines[$line]
            );
        }

        return substr_replace(
            $content,
            implode("\n", $lines),
            $offset
        );
    }
}
