<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Namespace
    |--------------------------------------------------------------------------
    |
    | Blueprint uses the default Laravel application namespace of 'App'.
    | However, you may configure Blueprint to use a custom namespace.
    | This value should match a PSR-4 autoload configuration value
    | within the composer.json file of your Laravel application.
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
    | your own custom namespace when generating these components.
    |
    */
    'models_namespace' => 'Models',

    'controllers_namespace' => 'Http\\Controllers',

    'policy_namespace' => 'Policies',

    /*
    |--------------------------------------------------------------------------
    | Application Path
    |--------------------------------------------------------------------------
    |
    | By default, Blueprint will save the generated application components
    | under the files under the `app` folder. However, you may configure
    | Blueprint  to save these generated component under a custom path.
    |
    */
    'app_path' => 'app',

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

    /*
    |--------------------------------------------------------------------------
    | Foreign Key Constraints
    |--------------------------------------------------------------------------
    |
    | Here you may enable Blueprint to always add foreign key constraints
    | within the generated migration. This will relate these records
    | together to add structure and integrity to your database.
    |
    | In addition, you may specify the action to perform `ON DELETE`. By
    | default Blueprint will use `cascade`. However, you may set this
    | to 'restrict', 'no_action', or 'null' as well as inline
    | by defining your `foreign` key column with an `onDelete`.
    |
    */
    'use_constraints' => false,

    'on_delete' => 'cascade',

    'on_update' => 'cascade',

    /*
    |--------------------------------------------------------------------------
    | Fake Nullables
    |--------------------------------------------------------------------------
    |
    | By default, Blueprint will set fake data even for nullable columns
    | within the generated model factories. However, you may disable
    | this behavior if you prefer to only set required columns
    | within your model factories.
    |
    */
    'fake_nullables' => true,

    /*
    |--------------------------------------------------------------------------
    | Use Guarded
    |--------------------------------------------------------------------------
    |
    | By default, Blueprint will set the `fillable` property within generated
    | models with the defined columns. These are set to provide a foundation
    | for mass assignment protection provided by Laravel. However, you may
    | configure Blueprint to instead set an empty `guarded` property to
    | generated "unguarded" models.
    |
    */
    'use_guarded' => false,

    /*
    |--------------------------------------------------------------------------
    | Singular route names
    |--------------------------------------------------------------------------
    |
    | By default, Blueprint will `kebab-case` the plural name of the controller
    | for the route name. If you would like to ensure a singular route name
    | is used for controllers, you may set this to `true`.
    |
    */
    'singular_routes' => false,

    /*
    |--------------------------------------------------------------------------
    | Constructor Property Promotion
    |--------------------------------------------------------------------------
    |
    | By default, Blueprint generates class properties explicitly. You may enable
    | this option to have Blueprint generate code for classes which contain a
    | constructor to set properties with "constructor property promotion".
    |
    */
    'property_promotion' => false,

    /*
    |--------------------------------------------------------------------------
    | Generators
    |--------------------------------------------------------------------------
    |
    | Blueprint will automatically register this default array of Generator classes.
    | However, you may configure Blueprint to use a custom Generator class to grant
    | expanded functionality to Blueprint.
    |
    | Your custom Generator class MUST implement the 'Blueprint\Contracts\Generator' interface.
    |
    */
    'generators' => [
        'controller' => \Blueprint\Generators\ControllerGenerator::class,
        'factory' => \Blueprint\Generators\FactoryGenerator::class,
        'migration' => \Blueprint\Generators\MigrationGenerator::class,
        'model' => \Blueprint\Generators\ModelGenerator::class,
        'route' => \Blueprint\Generators\RouteGenerator::class,
        'seeder' => \Blueprint\Generators\SeederGenerator::class,
        'test' => \Blueprint\Generators\PhpUnitTestGenerator::class,
        // 'test' => \Blueprint\Generators\PestTestGenerator::class,
        'event' => \Blueprint\Generators\Statements\EventGenerator::class,
        'form_request' => \Blueprint\Generators\Statements\FormRequestGenerator::class,
        'job' => \Blueprint\Generators\Statements\JobGenerator::class,
        'mail' => \Blueprint\Generators\Statements\MailGenerator::class,
        'notification' => \Blueprint\Generators\Statements\NotificationGenerator::class,
        'resource' => \Blueprint\Generators\Statements\ResourceGenerator::class,
        'view' => \Blueprint\Generators\Statements\ViewGenerator::class,
        'policy' => \Blueprint\Generators\PolicyGenerator::class,
    ],

];
