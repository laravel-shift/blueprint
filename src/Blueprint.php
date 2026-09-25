<?php

namespace Blueprint;

use Blueprint\Contracts\Generator;
use Blueprint\Contracts\Lexer;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

class Blueprint
{
    private array $lexers = [];

    private array $generators = [];

    private array $shorthands = [];

    public function registerShorthand(string $shorthand, \Closure $callback): void
    {
        $this->shorthands[$shorthand] = $callback;
    }

    private function expandShorthands(string $content): string
    {
        $content = preg_replace_callback(
            '/^(\s+)(id|timestamps(Tz)?|softDeletes(Tz)?)(: true)?$/mi',
            fn ($matches) => $matches[1] . strtolower($matches[2]) . ': ' . $matches[2],
            $content
        );

        $content = preg_replace_callback(
            '/^(\s+)resource$/mi',
            fn ($matches) => $matches[1] . 'resource: web',
            $content
        );

        $content = preg_replace_callback(
            '/^(\s+)invokable$/mi',
            fn ($matches) => $matches[1] . 'invokable: true',
            $content
        );

        $content = preg_replace_callback(
            '/^(\s+)(ulid|uuid)(: true)?$/mi',
            fn ($matches) => $matches[1] . 'id: ' . $matches[2] . ' primary',
            $content
        );

        foreach ($this->shorthands as $shorthand => $callback) {
            $content = preg_replace_callback(
                '/^(\s+)' . preg_quote($shorthand, '/') . '$/mi',
                $callback,
                $content
            );
        }

        return $content;
    }

    public static function relativeNamespace(string $fullyQualifiedClassName): string
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
        $content = $this->expandShorthands($content);

        return Yaml::parse($content);
    }

    /**
     * Rewrite shorthands, dashes, and duplicate statement keys, which
     * Blueprint accepts, but are not standard YAML, into their
     * explicit form. Each line is rewritten in place.
     */
    public function expand(string $content): string
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        if (preg_match('/^\s+indexes:$/m', $content) !== 1) {
            $content = preg_replace('/^(\s*)-\s+/m', '\1', $content);
        }

        $content = $this->expandDuplicatePropertyKeys($content);

        $explicit = [
            'id' => 'id: true',
            'timestamps' => 'timestamps: true',
            'timestampstz' => 'timestampsTz: true',
            'softdeletes' => 'softDeletes: true',
            'softdeletestz' => 'softDeletesTz: true',
            'invokable' => 'invokable: true',
            'resource' => 'resource: web',
        ];

        $content = preg_replace_callback(
            '/^(\s+)(' . implode('|', array_keys($explicit)) . ')$/mi',
            fn ($matches) => $matches[1] . $explicit[strtolower($matches[2])],
            $content
        );
        $content = preg_replace_callback(
            '/^(\s+)(ulid|uuid)(: true)?$/mi',
            fn ($matches) => $matches[1] . 'id: ' . strtolower($matches[2]) . ' primary',
            $content
        );

        foreach ($this->shorthands as $shorthand => $callback) {
            $content = preg_replace_callback(
                '/^(\s+)' . preg_quote($shorthand, '/') . '$/mi',
                $callback,
                $content
            );
        }

        return $content;
    }

    public function analyze(array $tokens): Tree
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

    public function dump(array $generated): string
    {
        return Yaml::dump($generated);
    }

    public function registerLexer(Lexer $lexer): void
    {
        $this->lexers[] = $lexer;
    }

    public function registerGenerator(Generator $generator): void
    {
        $this->generators[] = $generator;
    }

    public function swapGenerator(string $concrete, Generator $generator): void
    {
        foreach ($this->generators as $key => $registeredGenerator) {
            if (get_class($registeredGenerator) === $concrete) {
                unset($this->generators[$key]);
            }
        }

        $this->registerGenerator($generator);
    }

    public function withFilesystem(Filesystem $filesystem): self
    {
        $blueprint = clone $this;
        $blueprint->generators = array_map(
            fn (Generator $generator) => new ($generator::class)($filesystem),
            $this->generators
        );

        return $blueprint;
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

    private function expandDuplicatePropertyKeys(string $content): string
    {
        $lines = explode("\n", $content);
        $controllers = false;
        $counts = [];

        foreach ($lines as $index => $line) {
            if (preg_match('/^\S/', $line)) {
                $controllers = $line === 'controllers:';

                continue;
            }

            if (!$controllers) {
                continue;
            }

            if (preg_match('/^( {2}| {4}|\t){2}\w+:$/', $line)) {
                $counts = [];

                continue;
            }

            if (preg_match('/^((?: {2}| {4}|\t){3})(dispatch|fire|notify|send):(\s.*)$/', $line, $matches)) {
                $counts[$matches[2]] = ($counts[$matches[2]] ?? 0) + 1;

                if ($counts[$matches[2]] > 1) {
                    $lines[$index] = $matches[1] . $matches[2] . '-' . $counts[$matches[2]] . ':' . $matches[3];
                }
            }
        }

        return implode("\n", $lines);
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
