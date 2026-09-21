# Blueprint example: Blog

A complete, copy-pasteable example for newcomers and MCP servers.
Issue: laravel-shift/blueprint#753.

## Use

1. In a fresh Laravel app with Blueprint installed:
   `composer require -W --dev laravel-shift/blueprint`
2. Copy `draft.yaml` to the project root as `draft.yaml`.
3. Run `php artisan blueprint:build`.
4. Inspect generated files with `git status`.

Assumes a default Laravel app with a `User` model and `app.blade.php` layout.
Generated tests assume `jasonmccreary/laravel-test-assertions` if you run them.

## What it generates

- Models `Post`, `Comment`, `Tag` with fillable, casts, relationships
- Migrations, factories
- `PostController` with resource actions + custom `publish` action
- Routes, Blade component `post-card`
- Seeders for `Post` and `Tag`
- HTTP and unit tests

See the [Blueprint docs](https://blueprint.laravelshift.com/docs/generating-components/) for grammar details.
