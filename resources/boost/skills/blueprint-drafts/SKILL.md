---
name: blueprint-drafts
description: >
  Use this skill when writing, editing, or reviewing a Blueprint `draft.yaml` file, or when generating
  Laravel models, migrations, factories, controllers, routes, form requests, or tests with
  `php artisan blueprint:build`. Covers the full draft syntax for models, columns, relationships,
  controllers, and controller statements, along with common mistakes that produce invalid code.
---

# Writing Blueprint Drafts

## Workflow

1. Run `php artisan blueprint:trace` if the draft will reference existing models.
2. Write the draft to `draft.yaml` in the project root.
3. Run `php artisan blueprint:validate` and fix every error and warning it reports.
4. Run `php artisan blueprint:build` and review the list of generated files.
5. Review the generated code. Validation catches common mistakes, but not every statement that generates incorrect code.
6. To correct a draft, run `php artisan blueprint:erase`, fix the draft, and build again.

`blueprint:validate` reports each problem as `file:line: severity: message`. Errors mean `blueprint:build` will fail, and the command exits with `1`. Warnings mean the build will succeed, but generate invalid code or ignore part of the draft.

Write the explicit form of each key (e.g. `softDeletes: true`, `resource: web`) rather than a bare shorthand line, which is not standard YAML. Run `php artisan blueprint:validate --expand` to rewrite any existing shorthand.

Build options:

- `php artisan blueprint:build path/to/draft.yaml` builds a different draft file.
- `--only=models,migrations` or `--skip=tests` limits output. Types are `models`, `migrations`, `factories`, `controllers`, `routes`, `requests`, `resources`, `views`, `policies`, `seeders`, and `tests`.
- `--overwrite-migrations` updates existing migrations for the same table instead of creating new ones.

Models, controllers, factories, seeders, and tests are overwritten if they exist. Form requests, API resources, views, events, jobs, mailables, and notifications are skipped if they exist.

## Top-Level Sections

```yaml
models:       # Eloquent models keyed by class name
controllers:  # controllers keyed by name (without the "Controller" suffix)
seeders: Post, Comment   # comma-separated models to generate seeders for
config:       # overrides for config/blueprint.php during this build
```

Nest classes in subdirectories with `/` (e.g. `Admin/User`).

## Models

Every key under a model is a column, except the reserved keys: `id`, `timestamps`, `timestampsTz`, `softDeletes`, `softDeletesTz`, `relationships`, `indexes`, and `meta`.

```yaml
models:
  Post:
    title: string:400
    slug: string unique
    content: longtext
    status: enum:draft,published,archived default:draft
    price: decimal:8,2 nullable
    published_at: nullable timestamp
    user_id: id foreign
    softDeletes: true
    relationships:
      hasMany: Comment
      belongsToMany: Tag
```

Models get an `id` primary key and timestamps by default:

- `id: false` removes the primary key. `id: uuid primary` customizes it.
- `timestamps: false` removes timestamps. `timestamps: precision:6` sets their precision.
- `softDeletes: true` adds `deleted_at` and the `SoftDeletes` trait.

### Columns

Column definitions follow `<type>[:attributes] [modifier[:value]] ...`. The type and modifiers are separated by spaces, and attributes are separated by commas. Case is ignored.

- Common types: `string`, `text`, `longText`, `integer`, `bigInteger`, `unsignedInteger`, `boolean`, `decimal:8,2`, `float`, `date`, `dateTime`, `timestamp`, `json`, `enum:a,b,c`, `uuid`, `ulid`, `id`.
- Modifiers: `nullable`, `unique`, `index`, `primary`, `unsigned`, `default:value`, `comment:value`, `useCurrent`, `useCurrentOnUpdate`, `autoIncrement`, `charset:value`, `collation:value`, `foreign[:table[.column]]`, `onDelete:action`, `onUpdate:action`.
- A column with no type defaults to `string`, or to `id` when it has `foreign`.
- Quote values containing spaces: `default:'Not Set'`.

### Foreign Keys

- `user_id: id` creates an unsigned big integer and a `belongsTo` relationship to `User`.
- `author_id: id:user` references a model that doesn't match the column name.
- `user_id: id foreign` also adds a foreign key constraint. Use `foreign:users.id` for a non-standard table or column.
- `onDelete:cascade|restrict|null|no_action` sets the constraint action.

### Relationships

```yaml
relationships:
  belongsTo: Team
  hasOne: Profile
  hasMany: Comment, Like
  belongsToMany: Tag, Role:&Membership   # custom pivot model
  morphTo: commentable
  morphMany: Image
  hasManyThrough: Deployment:Environment   # Target:Intermediate
```

- `Model:alias` sets a custom relationship name (e.g. `belongsTo: User:owner` generates `owner()` using `owner_id`).
- Use a fully qualified name for models outside the models namespace (e.g. `\Spatie\Permission\Models\Role`).
- `belongsTo` relationships are inferred from `id` columns, so they don't need to be declared twice.
- `belongsToMany` generates a pivot table migration.

### Indexes and Meta

```yaml
    indexes:
      - unique: author_id, slug
      - index: published_at
    meta:
      table: blog_posts
      plural: people
      connection: tenant
      pivot: true
      extends: \App\Models\BaseModel
      traits: \App\Concerns\HasSlug
      implements: \App\Contracts\Publishable
```

## Controllers

Controller keys are action names. Each action contains statements that are generated in order.

```yaml
controllers:
  Post:
    resource: web           # or: api, or a subset: index, show, store
    dashboard:              # custom action
      query: all:posts
      render: post.dashboard with:posts
```

- `resource: web` generates `index`, `create`, `store`, `show`, `edit`, `update`, and `destroy` with views, form requests, and routes.
- `resource: api` generates `index`, `store`, `show`, `update`, and `destroy`, returning API resources.
- A subset such as `resource: index, show` or `resource: api.index, api.show` generates only those actions.
- Actions listed alongside `resource` replace the generated action with the same name.
- `invokable: true` generates a single action `__invoke` controller.
- `show`, `edit`, `update`, and `destroy` receive the controller's model through route model binding (e.g. `Post $post`).
- A custom action which finds the controller's model (`find: id`, `find: post.id`, or `find: post`) also receives it through route model binding, and is routed as `GET /posts/{post}/{action}`. Other custom actions are routed as `GET /posts/{action}` with no parameters. Custom routes are named `posts.{action}`.

Controller `meta`:

```yaml
    meta:
      policies: true          # or a subset: index, show, update
      parent: User            # adds a User $user parameter to each action
      store: user.posts       # save through a relationship
      extends: \App\Http\Controllers\AdminController
      traits: \App\Concerns\Auditable
      implements: \App\Contracts\Loggable
```

### Statements

| Statement | Example | Generates |
|---|---|---|
| `query` | `query: all` | `$posts = Post::all();` |
| | `query: where:title order:published_at limit:5` | `Post::where('title', $title)->orderBy('published_at')->limit(5)->get()` |
| | `query: where:post.title pluck:post.id` | `$post_ids = Post::where('title', $post->title)->pluck('id');` |
| `find` | `find: id` or `find: post.id` | Route model binding (`Post $post`) when finding the controller's model |
| `validate` | `validate: title, content` | A form request with rules based on the model's column definitions |
| | `validate: post` | A form request for every fillable column |
| `save` | `save: post` | `Post::create($request->validated())` in `store`; `$post->save()` elsewhere |
| `update` | `update: post` | `$post->update($request->validated())` |
| | `update: title, content` | `$post->update($request->only('title', 'content'))`, or `$request->safe()->only(...)` with `validate` |
| | `update: published_at` | `$post->update($request->only('published_at'))` when it's a column of the controller's model |
| `delete` | `delete: post` | `$post->delete();` |
| `render` | `render: post.show with:post` | `return view('post.show', ['post' => $post]);` and the Blade view |
| `inertia` | `inertia: Post/Show with:post` | `return Inertia::render('Post/Show', [...])` and the page |
| `redirect` | `redirect: posts.show with:post` | `return redirect()->route('posts.show', [$post]);` |
| `respond` | `respond: 204` | `return response()->noContent();` |
| `resource` | `resource: post` | `return new PostResource($post);` and the resource class |
| | `resource: collection:posts` or `paginate:posts` | A resource collection |
| `flash` | `flash: post.title` | `$request->session()->flash('post.title', $post->title);` |
| `store` | `store: post.id` | `$request->session()->store('post.id', $post->id);` |
| `fire` | `fire: PostPublished with:post` | An event class and `PostPublished::dispatch($post);` |
| | `fire: post.published` | `event('post.published');` for a lowercase event name |
| `dispatch` | `dispatch: SyncMedia with:post` | A job class and `SyncMedia::dispatch($post);` |
| `send` | `send: ReviewPost to:post.author with:post` | A mailable and `Mail::to(...)->send(...)` |
| | `send: ReviewNotification to:post.author with:post` | A notification when the name ends with `Notification` |
| `notify` | `notify: post.author ReviewPost with:post` | A notification and `$post->author->notify(...)` |

- Only `render`, `inertia`, `redirect`, `fire`, `dispatch`, `send`, and `notify` accept `with:`. Its values are comma-separated variable names (e.g. `with:post,comments`).
- Each statement can appear once per action. Suffix a label to repeat `fire`, `dispatch`, `send`, or `notify` (e.g. `fire-1`, `fire-2`).
- Variables used by a statement must exist in the action. They come from route-model binding, `find`, `query`, `save` in `store`, or `validate`.

## Common Mistakes

- `save: post with:published_at` is invalid. `save` only takes a model reference. Use `update: published_at` to set specific columns.
- `find: post_id` generates `PostId::find($post_id)`. Use `find: id` or `find: post.id`.
- `find` for a model other than the controller's (e.g. `find: user.id` in a `Post` controller) generates `User::find($id)`, and `$id` must be added by hand.
- `render: posts.index` looks for `resources/views/posts/index.blade.php`, while `redirect: posts.index` refers to the `posts.index` route name.
- Query clauses such as `where:title` pass a variable of the same name (`$title`), which must exist in the action. Use `where:post.title` to reference a property of an existing variable.
