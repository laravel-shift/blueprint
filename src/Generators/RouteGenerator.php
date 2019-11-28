<?php

namespace Blueprint\Generators;

use Blueprint\Contracts\Generator;
use Blueprint\Models\Controller;
use Illuminate\Support\Str;

class RouteGenerator implements Generator
{
    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    private $files;

    public function __construct($files)
    {
        $this->files = $files;
    }

    public function output(array $tree): array
    {
        if (empty($tree['controllers'])) {
            return [];
        }

        $routes = '';
        /** @var \Blueprint\Models\Controller $controller */
        foreach ($tree['controllers'] as $controller) {
            $routes .= PHP_EOL . PHP_EOL . $this->buildRoutes($controller);
        }
        $routes .= PHP_EOL;

        $path = 'routes/web.php';
        $this->files->append($path, $routes);

        return ['updated' => [$path]];
    }

    protected function buildRoutes(Controller $controller)
    {
        $routes = '';
        $methods = array_keys($controller->methods());

        $className = $controller->className();
        $slug = Str::kebab($controller->prefix());

        $resource_methods = array_intersect($methods, Controller::$resourceMethods);
        if (count($resource_methods)) {
            $routes .= sprintf("Route::resource('%s', '%s')", $slug, $className);

            $missing_methods = array_diff(Controller::$resourceMethods, $resource_methods);
            if (count($missing_methods)) {
                if (count($missing_methods) < 4) {
                    $routes .= sprintf("->except('%s')", implode("', '", $missing_methods));
                } else {
                    $routes .= sprintf("->only('%s')", implode("', '", $resource_methods));
                }
            }


            $routes .= ';' . PHP_EOL;
        }

        $methods = array_diff($methods, Controller::$resourceMethods);
        foreach ($methods as $method) {
            $routes .= sprintf("Route::get('%s/%s', '%s@%s');", $slug, Str::kebab($method), $className, $method);
            $routes .= PHP_EOL;
        }

        return trim($routes);
    }
}
