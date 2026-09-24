# Blueprint

Blueprint generates Laravel components (models, migrations, factories, controllers, routes, form requests, views, events, jobs, mailables, notifications, resources, policies, seeders, and tests) from a YAML draft file.

## Workflow

- Write the draft to `draft.yaml` in the project root (`php artisan blueprint:new` creates an empty one).
- Run `php artisan blueprint:validate` to check the draft before building. Fix every error and warning it reports.
- Run `php artisan blueprint:build` to generate the components. Pass a path to build a different draft file.
- Run `php artisan blueprint:erase` to delete the files from the last build before rebuilding with a corrected draft.
- Run `php artisan blueprint:trace` so new drafts can reference existing models without redefining them.
- Use `--only` or `--skip` with `blueprint:build` to limit output by type (e.g. `--only=models,migrations`).
- Always review the generated code after building. Validation catches common mistakes, but a draft that validates can still generate code that needs adjustment.

## Drafts

- A draft has up to four top-level sections: `models`, `controllers`, `seeders`, and `config`.
- Model keys are column names with a `<type>[:attributes] [modifiers]` definition (e.g. `decimal:8,2 nullable`).
- Controller keys are action names containing statements (e.g. `query`, `validate`, `save`, `render`, `redirect`), or `resource` for standard CRUD actions.
- Use the `blueprint-drafts` skill for the full draft syntax before writing or editing a draft.

@verbatim
<code-snippet name="Example draft.yaml" lang="yaml">
models:
  Post:
    title: string:400
    content: longtext
    published_at: nullable timestamp
    user_id: id foreign
    relationships:
      hasMany: Comment

controllers:
  Post:
    resource: web
</code-snippet>
@endverbatim
