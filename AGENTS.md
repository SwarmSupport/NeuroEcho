# Repository Guidelines

## Project Structure & Module Organization

This repository contains the NeuroEcho Book Gallery WordPress package, usable as both a plugin and a theme wrapper. The main plugin logic is in `neuroecho-book-gallery.php`. Theme bootstrap and layout files live at the root: `functions.php`, `style.css`, `header.php`, `footer.php`, `index.php`, and `comments.php`. Reader, archive, and library page templates are in `templates/`. Frontend behavior and shared UI styles are in `assets/neuroecho-book-gallery.js` and `assets/neuroecho-book-gallery.css`. Local Quicksand font files are stored in `assets/fonts/`. The distributable archive is `neuroecho-book-gallery.zip`; do not edit the zip directly.

## Build, Test, and Development Commands

- `node --check assets/neuroecho-book-gallery.js`: validates JavaScript syntax.
- `php -l neuroecho-book-gallery.php`: validates PHP syntax when PHP is installed. Repeat for changed PHP files.
- `rg -n "https?://|cdn|api" --glob '!*.zip' .`: checks that no external API/CDN dependency was added.
- Rebuild the package after changes:

```sh
rm -f neuroecho-book-gallery.zip
repo_dir=$(pwd)
tmpdir=$(mktemp -d)
mkdir -p "$tmpdir/neuroecho-book-gallery"
rsync -a --exclude='*.zip' ./ "$tmpdir/neuroecho-book-gallery/"
(cd "$tmpdir" && zip -qr "$repo_dir/neuroecho-book-gallery.zip" neuroecho-book-gallery)
```

Adjust the final zip path if running outside the repository root.

## Coding Style & Naming Conventions

Use WordPress PHP conventions: tabs for indentation, escaped output (`esc_html`, `esc_url`, `esc_attr`), sanitized request data, and translation functions with the `neuroecho-book-gallery` text domain. Keep the public "Book" wording even when normal posts are displayed as books. Use the existing prefixes: `ne_` for CSS/HTML hooks, `ne_book_` for request/meta fields, `ne_book` for the custom post type, and `ne_book_author` for the taxonomy. Store library metadata with the `_ne_book_` meta prefix.

## Testing Guidelines

There is no automated test suite in this package. For each change, run the syntax checks above and manually verify the gallery, search, reader page, comments, and responsive behavior in WordPress on desktop and mobile widths.

## Commit & Pull Request Guidelines

Use Conventional Commits, matching the existing history, for example `fix(search): include normal posts in book results` or `feat(reader): improve mobile controls`. Pull requests should describe user-visible changes, list verification steps, include screenshots for UI changes, and mention whether the zip was rebuilt.

## Security & Configuration Tips

Do not add remote font, CDN, or API dependencies. Keep assets local for Mainland China compatibility. Preserve WordPress nonce, capability, escaping, and sanitization checks when editing admin or request-handling code.
