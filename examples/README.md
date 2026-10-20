# Examples

Real-world drafts you can build as they are or use as a starting point.
Each one models a small, recognizable app rather than a single feature, so
you can see how models, relationships and controllers fit together. For
syntax coverage, see [`tests/fixtures/drafts`](../tests/fixtures/drafts).

Every file is a complete, independently buildable draft -- copy one into
your Laravel app as `draft.yaml` and run:

```sh
php artisan blueprint:build
```

(or point at a specific file: `php artisan blueprint:build path/to/example.yaml`)

## Index

| File | App |
| --- | --- |
| [`01-blog-crud.yaml`](01-blog-crud.yaml) | A blog with a full resource controller for posts. |
| [`02-api-resource.yaml`](02-api-resource.yaml) | A JSON API for a shop with products, orders and order items. |
| [`03-project-tracker.yaml`](03-project-tracker.yaml) | A project tracker with tasks, members, briefs and polymorphic comments, using UUID and ULID keys, soft deletes and an enum status. |
| [`04-events-jobs-mail.yaml`](04-events-jobs-mail.yaml) | A post submission that saves, emails the author, queues a job and fires an event. |

Each file is commented at the top with the app it models and what you get
from building it. Every example was built with `blueprint:build` to confirm
it generates the files described.

## Adding your own

Contributions are welcome. Keep each example:

- A believable app or workflow, not a list of unrelated features.
- Commented at the top with the app it models and what running
  `blueprint:build` against it produces.
- Verified to build successfully against the current `master`.
