<?php

namespace Blueprint;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Exception\ParseException;

class Validator
{
    private const REFERENCE = '[A-Za-z_]\w*(\.[A-Za-z_]\w*)*';

    private const STATEMENTS = [
        'delete', 'dispatch', 'find', 'fire', 'flash', 'inertia', 'notify', 'query', 'redirect',
        'render', 'resource', 'respond', 'save', 'send', 'store', 'update', 'validate',
    ];

    private const REPEATABLE_STATEMENTS = ['dispatch', 'fire', 'notify', 'send'];

    private Filesystem $filesystem;

    private array $lines = [];

    private array $diagnostics = [];

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Validate the draft builds, and warn about statements which
     * build successfully, but generate invalid code.
     *
     * @return array<int, array{line: ?int, severity: string, message: string}>
     */
    public function validate(Blueprint $blueprint, string $draft): array
    {
        $this->diagnostics = [];

        $contents = $this->filesystem->get($draft);
        $this->lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $contents));

        try {
            $tokens = $blueprint->parse($contents, preg_match('/^\s+indexes:\R/m', $contents) !== 1);
        } catch (ParseException $exception) {
            $line = $exception->getParsedLine();
            $this->error(preg_replace('/ at line \d+/', '', $exception->getMessage()), $line > 0 ? $line : null);

            return $this->diagnostics;
        }

        if (is_array($tokens['controllers'] ?? null)) {
            $this->checkControllers($tokens['controllers']);
        }

        try {
            $this->build($blueprint, $tokens);
        } catch (\Throwable $throwable) {
            $this->error($throwable->getMessage());
        }

        usort($this->diagnostics, fn ($a, $b) => ($a['line'] ?? 0) <=> ($b['line'] ?? 0));

        return $this->diagnostics;
    }

    private function build(Blueprint $blueprint, mixed $tokens): void
    {
        $cache = [];
        if ($this->filesystem->exists('.blueprint')) {
            $cache = $blueprint->parse($this->filesystem->get('.blueprint'));
        }

        $tokens['cache'] = $cache['models'] ?? [];

        $blueprint->withFilesystem($this->readOnlyFilesystem())
            ->generate($blueprint->analyze($tokens));
    }

    private function checkControllers(array $controllers): void
    {
        foreach ($controllers as $controller => $definition) {
            if (!is_array($definition)) {
                continue;
            }

            foreach ($definition as $action => $statements) {
                if (in_array($action, ['resource', 'meta'], true) || !is_array($statements)) {
                    continue;
                }

                foreach ($statements as $statement => $value) {
                    $this->checkStatement(['controllers', $controller, $action, $statement], (string)$statement, $value);
                }
            }
        }
    }

    private function checkStatement(array $path, string $statement, mixed $value): void
    {
        $line = $this->locateDuplicate($statement);
        if ($line) {
            $statement = Str::before($statement, '-');
        } else {
            $line = $this->locate($path);
        }

        $name = in_array(Str::before($statement, '-'), self::REPEATABLE_STATEMENTS, true)
            ? Str::before($statement, '-')
            : $statement;

        if (!in_array($name, self::STATEMENTS, true)) {
            $this->warning(sprintf('Unknown statement [%s] will be ignored.', $statement), $line);

            return;
        }

        if (!is_scalar($value)) {
            return;
        }

        $value = trim((string)$value);

        $expected = match ($name) {
            'delete', 'find', 'flash', 'save', 'store' => $this->isReference($value) ? null : 'a reference such as `post` or `post.id`',
            'update' => $this->isReferenceList($value) ? null : 'a reference such as `post`, or a list of columns',
            'resource' => preg_match('/^((collection|paginate):)?' . self::REFERENCE . '$/', $value) ? null : 'a reference such as `post` or `collection:posts`',
            'respond' => ctype_digit($value) || $this->isReference($value) ? null : 'a status code or a reference such as `post`',
            'dispatch', 'fire', 'inertia', 'redirect', 'render' => $this->hasOptionalData($value, '\S+') ? null : 'a name followed by an optional `with:` list',
            'notify' => $this->hasOptionalData($value, self::REFERENCE . '\s+\S+') ? null : 'a reference and a notification followed by an optional `with:` list',
            'send' => $this->hasOptionalData(preg_replace('/\s+(to|view):\S+/', '', $value), '\S+') ? null : 'a name followed by optional `to:`, `view:`, and `with:` values',
            default => null,
        };

        if ($expected) {
            $this->warning(sprintf('The [%s] statement expects %s, but [%s] was given.', $statement, $expected, $value), $line);
        }
    }

    private function isReference(string $value): bool
    {
        return preg_match('/^' . self::REFERENCE . '$/', $value) === 1;
    }

    private function isReferenceList(string $value): bool
    {
        return collect(preg_split('/,\s*/', $value))->every(fn ($reference) => $this->isReference($reference));
    }

    private function hasOptionalData(string $value, string $subject): bool
    {
        if (!preg_match('/^' . $subject . '(\s+with:(?<data>.+))?$/', $value, $matches)) {
            return false;
        }

        return empty($matches['data']) || $this->isReferenceList(trim($matches['data']));
    }

    /**
     * Find the line for a path of keys by tracking the
     * indentation of each key within the draft.
     */
    private function locate(array $path): ?int
    {
        $stack = [];

        foreach ($this->lines as $index => $line) {
            if (!preg_match('/^(?<indent>\s*)(-\s+)?(?<key>[^\s#:][^:]*?)\s*:(\s|$)/', $line, $matches)) {
                continue;
            }

            $indent = strlen($matches['indent']);
            while ($stack && end($stack)[0] >= $indent) {
                array_pop($stack);
            }

            $stack[] = [$indent, $matches['key']];

            if (array_column($stack, 1) === array_map('strval', $path)) {
                return $index + 1;
            }
        }

        return null;
    }

    /**
     * Blueprint suffixes duplicate statement keys with
     * their line offset from the `controllers` key.
     */
    private function locateDuplicate(string $statement): ?int
    {
        if (!preg_match('/^(?<name>dispatch|fire|notify|send)-(?<offset>\d+)$/', $statement, $matches)) {
            return null;
        }

        $controllers = array_search('controllers:', $this->lines, true);
        if ($controllers === false) {
            return null;
        }

        $index = $controllers + (int)$matches['offset'];
        if (!preg_match('/^\s+' . $matches['name'] . ':/', $this->lines[$index] ?? '')) {
            return null;
        }

        return $index + 1;
    }

    private function error(string $message, ?int $line = null): void
    {
        $this->diagnostics[] = ['line' => $line, 'severity' => 'error', 'message' => $message];
    }

    private function warning(string $message, ?int $line = null): void
    {
        $this->diagnostics[] = ['line' => $line, 'severity' => 'warning', 'message' => $message];
    }

    private function readOnlyFilesystem(): Filesystem
    {
        return new class extends Filesystem {
            public function put($path, $contents, $lock = false)
            {
                return strlen($contents);
            }

            public function replace($path, $content, $mode = null)
            {
            }

            public function replaceInFile($search, $replace, $path)
            {
            }

            public function prepend($path, $data)
            {
                return strlen($data);
            }

            public function append($path, $data, $lock = false)
            {
                return strlen($data);
            }

            public function delete($paths)
            {
                return true;
            }

            public function move($path, $target)
            {
                return true;
            }

            public function copy($path, $target)
            {
                return true;
            }

            public function makeDirectory($path, $mode = 0755, $recursive = false, $force = false)
            {
                return true;
            }
        };
    }
}
