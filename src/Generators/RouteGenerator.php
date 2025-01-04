<?php

namespace Blueprint\Generators;

use Blueprint\Contracts\Generator;
use Blueprint\Models\Controller;
use Blueprint\Tree;
use Illuminate\Support\Str;

class RouteGenerator extends AbstractClassGenerator implements Generator
{
    protected array $types = ['routes'];

    public function output(Tree $tree): array
    {
        if (empty($tree->controllers())) {
            return [];
        }

        $routes = ['api' => '', 'web' => ''];

        /**
         * @var \Blueprint\Models\Controller $controller
         */
        foreach ($tree->controllers() as $controller) {
            $type = $controller->isApiResource() ? 'api' : 'web';
            $routes[$type] .= PHP_EOL . PHP_EOL . $this->buildRoutes($controller);
        }

        $paths = [];

        if (isset($routes['api'])) {
            $this->setupApiRouter();
        }

        foreach (array_filter($routes) as $type => $definitions) {
            $path = 'routes/' . $type . '.php';
            $this->filesystem->append($path, $definitions . PHP_EOL);
            $paths[] = $path;
        }

        return ['updated' => $paths];
    }

    protected function buildRoutes(Controller $controller): string
    {
        $routes = '';
        $methods = array_keys($controller->methods());
        $className = $this->getClassName($controller);
        $slug = config('blueprint.singular_routes') ? Str::kebab($controller->prefix()) : Str::plural(Str::kebab($controller->prefix()));

        if ($controller->parent()) {
            $parentSlug = config('blueprint.singular_routes') ? Str::kebab($controller->parent()) : Str::plural(Str::kebab($controller->parent()));
            $parentBinding = '/{' . Str::kebab($controller->parent()) . '}/';
            $slug = $parentSlug . $parentBinding . $slug;
        }

        foreach (array_diff($methods, Controller::$resourceMethods) as $method) {
            $routes .= $this->buildRouteLine($className, $slug, $method);
            $routes .= PHP_EOL;
        }

        $resource_methods = array_intersect($methods, Controller::$resourceMethods);
        if (count($resource_methods)) {
            $routes .= $controller->isApiResource()
                ? sprintf("Route::apiResource('%s', %s)", $slug, $className)
                : sprintf("Route::resource('%s', %s)", $slug, $className);

            $missing_methods = $controller->isApiResource()
                ? array_diff(Controller::$apiResourceMethods, $resource_methods)
                : array_diff(Controller::$resourceMethods, $resource_methods);

            if (count($missing_methods)) {
                if (count($missing_methods) < 4) {
                    $routes .= sprintf("->except('%s')", implode("', '", $missing_methods));
                } else {
                    $routes .= sprintf("->only('%s')", implode("', '", $resource_methods));
                }
            }

            $routes .= ';' . PHP_EOL;
        }

        return trim($routes);
    }

    protected function getClassName(Controller $controller): string
    {
        return $controller->fullyQualifiedClassName() . '::class';
    }

    protected function buildRouteLine($className, $slug, $method): string
    {
        if ($method === '__invoke') {
            return sprintf("Route::get('%s', %s);", $slug, $className);
        }

        return sprintf("Route::get('%s/%s', [%s, '%s']);", $slug, Str::kebab($method), $className, $method);
    }

    protected function setupApiRouter(): void
    {
        $this->createApiRoutesFileIfMissing();
    }

    protected function createApiRoutesFileIfMissing(): void
    {
        $apiPath = 'routes/api.php';
        if (!$this->filesystem->exists($apiPath)) {
            $this->filesystem->put($apiPath, $this->filesystem->stub('routes.api.stub'));
            $this->configureApiRoutesInAppBootstrap();
        }
    }

    protected function configureApiRoutesInAppBootstrap(): void
    {
        $appBootstrapPath = 'bootstrap/app.php';
        $content = $this->filesystem->get($appBootstrapPath);

        if (str_contains($content, '// api: ')) {
            $this->filesystem->replaceInFile(
                '// api: ',
                'api: ',
                $appBootstrapPath,
            );
        } elseif (str_contains($content, 'web: __DIR__.\'/../routes/web.php\',')) {
            $this->filesystem->replaceInFile(
                'web: __DIR__.\'/../routes/web.php\',',
                'web: __DIR__.\'/../routes/web.php\',' . PHP_EOL . '        api: __DIR__.\'/../routes/api.php\',',
                $appBootstrapPath,
            );
        }
    }
}
