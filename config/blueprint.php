<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Namespace
    |--------------------------------------------------------------------------
    |
    | Blueprint assumes a default Laravel application namespace of 'App'.
    | However, you may configure Blueprint to use a custom namespace.
    | Ultimately this should match the PSR-4 autoload value set
    | within the composer.json file of your application.
    |
    */
    'namespace' => 'App',


    /*
    |--------------------------------------------------------------------------
    | Component Namespaces
    |--------------------------------------------------------------------------
    |
    | Blueprint promotes following Laravel conventions. As such, it generates
    | components under the default namespaces. For example, models are under
    | the `App` namespace. However, you may configure Blueprint to use
    | a custom namespace when generating these components.
    |
    */
    'models_namespace' => '',
    'controllers_namespace' => 'Http\\Controllers',


    /*
    |--------------------------------------------------------------------------
    | Application Path
    |--------------------------------------------------------------------------
    |
    | Here you may customize the path where Blueprint stores generated
    | components. By default, Blueprint will store files under the
    | `app` folder However, you may change the path to store
    | generated component elsewhere.
    |
    */
    'app_path' => app_path(),

    /*
    |--------------------------------------------------------------------------
    | Generate PHPDocs
    |--------------------------------------------------------------------------
    |
    | Here you may enable generate PHPDocs for classes like Models. This
    | not only serves as documentation, but also allows your IDE to
    | map to the dynamic properties used by Laravel Models.
    |
    */
    'generate_phpdocs' => false,

];
