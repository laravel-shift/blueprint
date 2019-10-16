# blueprint
A new open-source tool being developed to provide rapidly generate multiple Laravel components using a expressive, human readable syntax.

---

**Example Syntax**
```yaml
models:
  Post:
    id
    title: string
    content: bigtext
    published_at: nullable timestamp
    timestamps

controllers:
  PostController:
    index:
      query: all posts
      render: post.index with posts

    store:
      validate: title, content
      save: post
      send: ReviewNotifcation to post.author
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

