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

        $updated = [];
        foreach (['web', 'api'] as $type) {
            $updated[] = $this->dumpRoutes($tree, $type);
        }
        $updated = array_filter($updated);

        return compact('updated');
    }

    private function dumpRoutes($tree, $type)
    {
        $routes = '';
        /** @var \Blueprint\Models\Controller $controller */
        foreach ($tree['controllers'] as $controller) {
            if (($type == 'web' && $controller->isAPI()) || ($type == 'api' && !$controller->isAPI())) {
                continue;
            }
            $routes .= PHP_EOL . PHP_EOL . $this->buildRoutes($controller);
        }
        $routes .= PHP_EOL;
        if (empty(trim($routes))) {
            return null;
        }

        $path = "routes/{$type}.php";
        $this->files->append($path, $routes);

        return $path;
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
