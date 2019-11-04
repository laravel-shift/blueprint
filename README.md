# Blueprint
A new open-source tool to rapidly generate multiple Laravel components using an expressive, human readable syntax.

Follow along with the development of Blueprint by [watching live streams](https://www.youtube.com/playlist?list=PLmwAMIdrAmK5q0c0JUqzW3u9tb0AqW95w) or [reviewing issues](https://github.com/laravel-shift/blueprint/issues).

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
