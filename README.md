# Blueprint
A new open-source tool to rapidly generate multiple Laravel components using an expressive, human readable syntax.

Follow along with the development of Blueprint by [watching live streams](https://www.youtube.com/playlist?list=PLmwAMIdrAmK5q0c0JUqzW3u9tb0AqW95w) or [reviewing issues](https://github.com/laravel-shift/blueprint/issues).

_**v0.1 Tagged**: A beta release of Blueprint is now available which supports generating components using `models` definitions._

---

**Example Syntax**
```yaml
models:
  Post:
    title: string:400
    content: bigtext
    published_at: nullable timestamp

controllers:
  PostController:
    index:
      query: all posts
      render: post.index with posts

    store:
      validate: title, content
      save: post
      send: ReviewNotification to post.author
      queue: SyncMedia
      flash: post.title
      redirect: post.index
```

**Generated Components**
- Migration
- Model
- Route
- Controller
- Form Request
- Mailable
- Job
- View (stub)


## Contributing
Contributions should be submitted to the `master` branch. Any submissions should be complete with tests and adhere to the [PSR-2 code style](). You may also contribute by [opening an issue](https://github.com/laravel-shift/blueprint/issues).
