![Blueprint](blueprint-logo.png)

_Blueprint_ is an open-source tool for **rapidly generating multiple** Laravel components from a **single, human readable** definition.

Watch a quick [demo of Blueprint](https://www.youtube.com/watch?v=A_gUCwni_6c) in action and continue reading this document to get started.

- [Installation](#installation)
- [Requirements](#requirements)
- [Basic Usage](#basic-usage)
- [Defining Components](#defining-components)
- [Additional Console Commands](#additional-console-commands)
- [Contributing](#contributing)


## Installation
You can install Blueprint via composer using the following command:

```sh
composer require --dev laravel-shift/blueprint
```

Blueprint will automatically register itself using [package discovery](https://laravel.com/docs/packages#package-discovery).

## Requirements
Blueprint requires a Laravel application running version 6.0 or higher.

While Blueprint may be more flexible in a future version, it currently assumes a standard project structure using the default `App` namespace.

## Basic Usage
Blueprint adds multiple artisan commands. The most commonly used command is the `blueprint:build` command to generate the Laravel components:

```sh
php artisan blueprint:build [draft]
```

The _draft_ file contains a [definition of the components](#defining-components) to generate. By default, the `blueprint:build` command attempts to load a `draft.yaml` file from the project root folder.


## Defining Components
Use Blueprint's `artisan` commands you may generate multiple Laravel components from a single definition called a _draft_ file.

Within this draft file you define _models_ and _controllers_ using an expressive, human-readable YAML syntax.

Let's review the following draft file:

```yaml
models:
  Post:
    title: string:400
    content: longtext
    published_at: nullable timestamp

controllers:
  Post:
    index:
      query: all
      render: post.index with:posts

    store:
      validate: title, content
      save: post
      send: ReviewNotification to:post.author with:post
      dispatch: SyncMedia with:post
      fire: NewPost with:post
      flash: post.title
      redirect: post.index
```

From these simple 20 lines of YAML, Blueprint will generate all of the following Laravel components:

- A _model_ class for `Post` complete with `fillable`, `casts`, and `dates` properties, as well as relationships methods.
- A _migration_ to create the `posts` table.
- A [_factory_](https://laravel.com/docs/database-testing) intelligently setting columns with fake data.
- A _controller_ class for `PostController` with `index` and `store` actions complete with code generated for each [statement](#statements).
- _Routes_ for the `PostController` actions.
- A [_form request_](https://laravel.com/docs/validation#form-request-validation) of `StorePostRequest` validating `title` and `content` based on the `Post` model definition.
- A _mailable_ class for `ReviewNotification` complete with a `post` property set through the _constructor_.
- A _job_ class for `SyncMedia` complete with a `post` property set through the _constructor_.
- An _event_ class for `NewPost` complete with a `post` property set through the _constructor_.
- A _Blade template_ of `post/index.blade.php` rendered by `PostController@index`.

While this draft file only defines a single model and controller, you may define multiple [models](#models) and [controllers](#controllers).

### Models
Within the `models` section of a draft file you may define multiple models. Each model begins with a _name_ followed by a list of columns. Columns are `key: value` pairs where `key` is the column name and `value` defines its attributes.

Expanding on the example above, this draft file defines multiple models:

```yaml
models:
  Post:
    title: string:400
    content: longtext
    published_at: nullable timestamp

  Comment:
    content: longtext
    published_at: nullable timestamp

  # additional models...
```

From this definition, Blueprint creates two models: `Post` and `Comment`, respectively. You may continue to define additional models.

Blueprint recommends defining the model name in its _StudlyCase_, singular form to follow Laravel naming conventions. For example, `Post` instead of `post` or `posts`.

Similarly, column names will be used as-is. The attributes of these columns may be any of the [column types](https://laravel.com/docs/migrations#creating-columns) and [column modifiers](https://laravel.com/docs/migrations#column-modifiers) available in Laravel. You may define these as-is or using lowercase.

For complex attributes, you may use a `key:value` pair. From these example above, `string:400` defines a `string` column type with a maximum length of `400` characters. Other examples include `enum:'foo','bar','baz'` or `decimal:10,2`.

By default, each model will automatically be defined with an `id` and _timestamps_ columns. To disable these columns you may define them with a `false` value. For example, `timestamps: false`.

Blueprint also offers additional _shorthands_ which will be expanded into valid YAML. Shorthands include an `id` data type, as well as defining [soft deleting](https://laravel.com/docs/eloquent#soft-deleting) models.

For example:

```yaml
models:
  Comment:
    user_id: id
    softDeletes
    # ...
```

Using these shorthands, Blueprint will generate a `Comment` class using the `SoftDeletes` trait. It will also create a `user_id` column with the appropriate data type for an integer foreign key.

Blueprint also inspects columns and assigns them to `fillable`, `casts`, and `dates` properties, as well as generate relationships methods.

By default, all columns except for `id` and _timestamps_ will be added to the `fillable` property.

Where appropriate, Blueprint will [cast](https://laravel.com/docs/5.8/eloquent-mutators#attribute-casting) columns to `integer`, `boolean`, and `decimal` types. Any _date_ columns will be added to the `dates` properties.

Columns which use an `id` data type or end with `_id` will be used to generate `belongsTo` relationships. By default, Blueprint uses the column name prefix for the related model. If you define a relationship for a different model, you may use a `id:model` syntax.

For example:

```yaml
models:
  Post:
    author_id: id:user
    # ...
```


### Controllers
Similar to `models`, you may also define multiple `controllers`. Within the `controllers` section you define a _controller_ by name. Each controller may define multiple `actions` which contain a list of [statements](#statements).

Expanding on the example above, this draft file defines multiple controllers:

```yaml
controllers:
  Post:
    index:
      query: all
      render: post.index with:posts
    create:
      render: post.create
    store:
      validate: title, content
      save: post
      redirect: post.index

  Comment:
    show:
      render: comment.show with:show

  # additional controller...
```

From this definition, Blueprint will generate two controllers. A `PostController` with `index`, `create`, and `store` actions. And a `CommentController` with a `show` action.

While you may specify the full name of a controller, Blueprint will automatically suffix names with `Controller`.

Blueprint encourages you to define [resource controllers](https://laravel.com/docs/controllers#resource-controllers). Doing so allows Blueprint to infer details and generate even more code automatically.


#### Statements
Blueprint comes with an expressive set of statements which implicitly define additional components to generate. Each statement is a `key: value` pair.

The `key` defines the _type_ of statement to generate. Currently, Blueprint supports the following types of statements:

<dl>
  <dt>validate</dt>
  <dd>

  Generates a form request with _rules_ based on the referenced model definition. Blueprint accepts a `value` containing a comma separated list of column names.

  For example:

  ```yaml
  validate: title, content, author_id
  ```

  Blueprint also updates the type-hint of the injected request object.</dd>

  <dt>find</dt>
  <dd>

  Generates an Eloquent `find` statement. If the `value` provided is a qualified [reference](#references), Blueprint will expand the reference to determine the model. Otherwise, Blueprint will attempt to use the controller to determine the related model.</dd>

  <dt>query</dt>
  <dd>

  Generates an Eloquent query statement using `key:value` pairs provided in `value`. Keys may be any of the basic query builder methods for [`where` clauses](https://laravel.com/docs/queries#where-clauses) and [ordering](https://laravel.com/docs/queries#ordering-grouping-limit-and-offset).

  For example:

  ```yaml
  query: where:title where:content order:published_at limit:5
  ```

  Currently, Blueprint supports generating query statements for `all`, `get`, `pluck`, and `count`.</dd>


  <dt>save/delete</dt>
  <dd>

  Generates an Eloquent statement for saving a model. Blueprint uses the controller action to infer which statement to generate.

  For example, for a `store` controller action, Blueprint will generate a `Model::create()` statement. Otherwise, a `$model->save()` statement will be generated.

  Similarly, within a `destroy` controller action, Blueprint will generate a `$model->delete()` statement. Otherwise, a `Model::destroy()` statement will be generated.</dd>

  <dt>flash</dt>
  <dd>

  Generates a statement to [flash data](https://laravel.com/docs/session#flash-data) to the session. Blueprint will use the `value` as the session key and expands the reference as the session value.
  
  For example:
  
  ```yaml
  flash: post.title
  ```
  </dd>

  <dt>render</dt>
  <dd>Generates a `return view();` statement complete with a template reference and data.

  For example:

  ```yaml
  view: post.show with:post
  ```

  When the template does not exist, Blueprint will generate the Blade template for the view.</dd>


  <dt>redirect</dt>
  <dd>Generates a `return redirect()` statement using the `value` as a reference to a named route passing any data as parameters.

  For example:

  ```yaml
  redirect: post.show with:post
  ```
  </dd>


  <dt>dispatch</dt>
  <dd>

  Generates a statement to dispatch a [Job](https://laravel.com/docs/queues#creating-jobs) using the `value` to instantiate an object and pass any data.

  For example:

  ```yaml
  dispatch: SyncMedia with:post
  ```

  If the referenced _job_ class does not exist, Blueprint will create one using any data to define properties and a `__construct` method which assigns them.</dd>

  <dt>fire</dt>
  <dd>

  Generates a statement to dispatch a [Event](https://laravel.com/docs/events#defining-events) using the `value` to instantiate the object and pass any data.

  For example:

  ```yaml
  fire: NewPost with:post
  ```

  If the referenced _event_ class does not exist, Blueprint will create one using any data to define properties and a `__construct` method which assigns them.</dd>


  <dt>send</dt>
  <dd>

  Generates a statement to send a [Mailable](https://laravel.com/docs/mail#generating-mailables) using the `value` to instantiate the object, specify the recipient, and pass any data.

  For example:

  ```yaml
  send: ReviewNotification to:post.author with:post
  ```

  If the referenced _mailable_ class does not exist, Blueprint will create one using any data to define properties and a `__construct` method which assigns them.</dd>
</dl>

#### References
For convenience, Blueprint will use the name of a controller to infer the related model. For example, Blueprint will assume a `PostController` relates to a `Post` model.

Blueprint also supports a dot (`.`) syntax for more complex references. This allows you to define values which reference columns on other models.

For example, to _find_ a `User` model within the `PostController` you may use:

```yaml
controllers:
  Post:
    show:
      find: user.id
      # ...
```

While these references will often be used within _Eloquent_ and `query` statements, they may be used in other statements as well. When necessary, Blueprint will convert these into variable references using an arrow (`->`) syntax.

## Additional Console Commands
Beyond the `blueprint:build` command, Blueprint provides additional commands:

<dl>
  <dt>blueprint:erase</dt>
  <dd>

  Erases the components created by the last _build_ and warns about any updated components.
  
  While this command is helpful, it's better to run the `blueprint:build` command from a clean working state and use Git commands like `reset` and `clean` to _undo_ the last _build_.</dd>
  <dt>blueprint:trace</dt>
  <dd>

  Loads definitions for existing models into the Blueprint cache file (`.blueprint`) so you may reference them in your _draft_ file.</dd>
</dl>


## Contributing
Contributions may be made by submitting a Pull Request against the `master` branch. Any submissions should be complete with tests and adhere to the [PSR-2 code style](https://www.php-fig.org/psr/psr-2/).

You may also contribute by [opening an issue](https://github.com/laravel-shift/blueprint/issues) to report a bug or suggest a new feature.
