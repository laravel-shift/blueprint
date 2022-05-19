<p align="right">
    <a href="https://github.com/laravel-shift/blueprint/actions"><img src="https://github.com/laravel-shift/blueprint/workflows/Build/badge.svg" alt="Build Status"></a>
    <a href="https://packagist.org/packages/laravel-shift/blueprint"><img src="https://poser.pugx.org/laravel-shift/blueprint/v/stable.svg" alt="Latest Stable Version"></a>
    <a href="https://github.com/badges/poser/blob/master/LICENSE"><img src="https://poser.pugx.org/laravel-shift/blueprint/license.svg" alt="License"></a>
</p>

![Blueprint](blueprint-logo.png)

_Blueprint_ is an open-source tool for **rapidly generating multiple** Laravel components from a **single, human readable** definition.

Watch a quick [demo of Blueprint](https://www.youtube.com/watch?v=A_gUCwni_6c) in action and continue reading to get started.

## Requirements
Blueprint requires a Laravel application running the latest stable release of Laravel, currently Laravel 8.x.

## Installation
You can install Blueprint via composer using the following command:

```sh
composer require --dev laravel-shift/blueprint
```

Blueprint will automatically register itself using [package discovery](https://laravel.com/docs/packages#package-discovery).

_**Additional Configuration**: If you are running Laravel 8, or registering class-based routes or using the `app/Models` folder, you will need to configure Blueprint. Please review the [Blueprint Docs](https://blueprint.laravelshift.com/docs/advanced-configuration/) for additional guidance._

## Basic Usage
Blueprint comes with a set of artisan commands. The one you'll use the most is the `blueprint:build` command to generate the Laravel components:

```sh
php artisan blueprint:build [draft]
```

The _draft_ file contains a [definition of the components](https://blueprint.laravelshift.com/docs/generating-components/) to generate.

Let's review the following, example draft file to generate some _blog_ components:

```yaml
models:
  Post:
    title: string:400
    content: longtext
    published_at: nullable timestamp
    author_id: id:user

controllers:
  Post:
    index:
      query: all
      render: post.index with:posts

    store:
      validate: title, content, author_id
      save: post
      send: ReviewPost to:post.author.email with:post
      dispatch: SyncMedia with:post
      fire: NewPost with:post
      flash: post.title
      redirect: post.index
```

From these simple 20 lines of YAML, Blueprint will generate all of the following Laravel components:

- A _model_ class for `Post` complete with `fillable`, `casts`, and `dates` properties, as well as relationships methods.
- A _migration_ to create the `posts` table.
- A [_factory_](https://laravel.com/docs/database-testing) intelligently setting columns with fake data.
- A _controller_ class for `PostController` with `index` and `store` actions complete with code generated for each [statement](https://blueprint.laravelshift.com/docs/controller-statements/).
- _Routes_ for the `PostController` actions.
- A [_form request_](https://laravel.com/docs/validation#form-request-validation) of `StorePostRequest` validating `title` and `content` based on the `Post` model definition.
- A _mailable_ class for `ReviewPost` complete with a `post` property set through the _constructor_.
- A _job_ class for `SyncMedia` complete with a `post` property set through the _constructor_.
- An _event_ class for `NewPost` complete with a `post` property set through the _constructor_.
- A _Blade template_ of `post/index.blade.php` rendered by `PostController@index`.

_**Note:** This example assumes features within a default Laravel application such as the `User` model and `app.blade.php` layout. Otherwise, the generated test may have failures._

## Documentation
Browse the [Blueprint Docs](https://blueprint.laravelshift.com/) for full details on [defining models](https://blueprint.laravelshift.com/docs/defining-models/), [defining controllers](https://blueprint.laravelshift.com/docs/defining-controllers/), [advanced configuration](https://blueprint.laravelshift.com/docs/advanced-configuration/), and [extending Blueprint](https://blueprint.laravelshift.com/docs/extending-blueprint/).

## Support Policy
Starting with version 2, Blueprint only generates code for the latest stable version of Laravel (currently Laravel 9). If you need to support older versions of Laravel, you may use version 1 or upgrade your application ([try using Shift](https://laravelshift.com)).

Blueprint still follows [semantic versioning](https://semver.org/). However, it does so with respect to its grammar. Any changes to the grammar will increase its major version number. Otherwise, minor version number increases will contain new features. This includes generating code for future versions of Laravel.
