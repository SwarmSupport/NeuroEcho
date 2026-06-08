# NeuroEcho Book Gallery

A WordPress plugin that adds a searchable book gallery, multi-author support, and a reader-focused single book view with native comments.

## Features

- `Books` custom post type at `/books/`
- `Book Authors` taxonomy, so one book can have multiple authors
- Falls back to the WordPress post author when no Book Author term is set
- Searchable gallery shortcode with author filtering; shows all shared books by default
- Normal WordPress posts are included in the gallery as books, using the post title, content, author, comments, and featured image
- Share-site metadata: subtitle, cover URL, format label, reading time, and an optional share note
- Search across book title/content, subtitle, format label, share note, Book Author terms, WordPress user authors, and approved comments
- No-results searches show the available user-authored books as compact fallback results
- Reader template for Books and normal posts with progress bar, table of contents, theme controls, text size, line width controls, and mobile-friendly layout
- Theme wrapper supports desktop and mobile layouts for the gallery, reader, header, footer, and comments
- Native WordPress comments, compatible with comment-form CAPTCHA plugins such as Simple CAPTCHA Alternative with Cloudflare Turnstile
- No external font API or CDN dependency; the original Quicksand style is packaged locally

## Usage

1. Put this folder in `wp-content/plugins/neuroecho-book-gallery`.
2. Activate **NeuroEcho Book Gallery** in WordPress.
3. Add books from **Books** in the WordPress admin.
4. Optionally add one or more authors in the **Authors** taxonomy box. If you leave it empty, the WordPress post author is shown.
5. Use the archive at `/books/`, or place this shortcode on any page:

```text
[neuroecho_book_gallery]
```

Optional shortcode attributes:

```text
[neuroecho_book_gallery limit="9" author="octavia-butler" heading="Reading Room"]
```

Omit `limit` or use `limit="all"` to show every shared book.

## Comments and Turnstile

Book posts support standard WordPress comments. The reader template calls WordPress' native comments template, so plugins that hook into the normal comment form can inject verification without custom integration code.
