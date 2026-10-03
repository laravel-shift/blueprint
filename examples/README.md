# Examples

Full, runnable draft files demonstrating what you can build with a single
Blueprint YAML definition. These were requested in
[#753](https://github.com/laravel-shift/blueprint/issues/753) as a
starting point for newcomers (and for tools, like MCP servers, that want a
richer corpus of valid draft syntax than the one-off snippets scattered
through the docs).

Every file here is a complete, independently buildable draft -- copy one
into your Laravel app as `draft.yaml` and run:

```sh
php artisan blueprint:build
```

(or point at a specific file: `php artisan blueprint:build path/to/example.yaml`)

## Index

| File | Demonstrates |
| --- | --- |
| [`01-blog-crud.yaml`](01-blog-crud.yaml) | A classic full-CRUD resource controller: index/create/store/show/edit/update/destroy, with `validate`, `save`, `find`, `delete`, and `redirect` statements. |
| [`02-api-resource.yaml`](02-api-resource.yaml) | A JSON API resource (`resource: api`) with a `hasMany`/`belongsTo` parent-child relationship and a nested child route via `meta:`. |
| [`03-relationships.yaml`](03-relationships.yaml) | The relationship shapes you'll reach for most: `belongsTo` (via foreign-key shorthand), `belongsToMany`, `hasMany`, `hasOne`, and polymorphic `morphMany`/`morphTo`. |
| [`04-events-jobs-notifications.yaml`](04-events-jobs-notifications.yaml) | Fanning one controller action out to a queued job (`dispatch`), an event (`fire`), and a notification (`send`), alongside `validate`/`save`. |
| [`05-advanced-model-features.yaml`](05-advanced-model-features.yaml) | Model-column features beyond the basics: UUID and ULID primary keys, `softDeletes`, and an `enum` column with a default value. |

Each file is commented inline with what it generates and why. They were
written against (and verified with) Blueprint's own parser/analyzer, so
the YAML in every example is valid, current grammar -- not aspirational
syntax.

## Where these came from

As suggested in the issue thread, these build on the patterns already
exercised in [`tests/fixtures/drafts`](../tests/fixtures/drafts), which
contains well over a hundred small, single-purpose draft files used by
Blueprint's own test suite. That folder is the deepest source of truth
for Blueprint's grammar -- these examples are a curated, documented,
beginner-facing entry point into that same syntax, not a replacement for
it.

## Adding your own

If you have a draft file that shows off a feature not covered here (a
livewire component, a seeder, `extending` statements, custom traits and
interfaces, etc.), contributions are welcome. Keep each file:

- Focused on one or two related features.
- Commented at the top with what it demonstrates and what running
  `blueprint:build` against it produces.
- Verified to parse and build successfully against the current `master`.
