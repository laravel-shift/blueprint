<?php

namespace Blueprint\Generators;

use Blueprint\Contracts\Generator;
use Blueprint\Models\Controller;
use Blueprint\Tree;
use Illuminate\Support\Str;

class RouteGenerator extends AbstractClassGenerator implements Generator
{
    protected $types = ['routes'];

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

        foreach (array_filter($routes) as $type => $definitions) {
            $path = 'routes/' . $type . '.php';
            $this->filesystem->append($path, $definitions . PHP_EOL);
            $paths[] = $path;
        }

        return ['updated' => $paths];
    }

    protected function buildRoutes(Controller $controller)
    {
        $routes = '';
        $methods = array_keys($controller->methods());
        $className = $this->getClassName($controller);
        $slug = config('blueprint.plural_routes') ? Str::plural(Str::kebab($controller->prefix())) : Str::kebab($controller->prefix());

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

    protected function getClassName(Controller $controller)
    {
        return $controller->fullyQualifiedClassName() . '::class';
    }

    protected function buildRouteLine($className, $slug, $method)
    {
        if ($method === '__invoke') {
            return sprintf("Route::get('%s', %s);", $slug, $className);
        }

        return sprintf("Route::get('%s/%s', [%s, '%s']);", $slug, Str::kebab($method), $className, $method);
    }
}
