# Grav Plugin — Share Markdown Button

**Share Markdown Button** adds a one-click button to your [Grav](https://getgrav.org) pages that copies the page content as clean Markdown to the visitor's clipboard — ready to paste into an AI chat, a note-taking app, or any Markdown editor.

Because Grav stores content as `.md` files natively, there is no lossy HTML-to-Markdown conversion: what gets copied is the actual source of the page.

---

## Features

- Copies the raw Markdown source of the current page to the clipboard
- Automatically resolves Twig tags in pages that use `process: twig: true`
- Optionally prepends the page title as an `# H1` heading (great for AI context)
- Optionally includes the YAML frontmatter
- Configurable button position: top, bottom, or both
- 2-second visual feedback after a successful copy
- Keyboard accessible and screen-reader friendly
- Internationalized: EN, ES, FR, PT, IT, DE (auto-detects the site language)
- Lightweight: one small CSS file + one small deferred JS file, only loaded when needed
- Full Admin panel support via blueprints

---

## Requirements

- Grav **1.7** or higher
- PHP **7.4** or higher

---

## Installation

### Via GPM (recommended)

```bash
bin/gpm install share-markdown-button
```

### Manual

1. Download or clone this repository.
2. Place the folder under `user/plugins/` and rename it to `share-markdown-button`.
3. The final path must be `user/plugins/share-markdown-button/share-markdown-button.php`.

---

## Configuration

The plugin works out-of-the-box. To customize, copy `share-markdown-button.yaml` to
`user/config/plugins/share-markdown-button.yaml` and edit it:

```yaml
enabled: true
button_position: bottom    # top | bottom | both
button_text: "PLUGIN_SHARE_MARKDOWN_BUTTON.BUTTON_TEXT"  # or any plain string
show_icon: true
copied_text: "PLUGIN_SHARE_MARKDOWN_BUTTON.COPIED_TEXT"  # or any plain string
include_title: true
include_frontmatter: false
page_types: []             # empty = all types; e.g. [default, post]
```

| Option | Default | Description |
|---|---|---|
| `enabled` | `true` | Enable or disable the plugin globally |
| `button_position` | `bottom` | Where to render the button: `top`, `bottom`, or `both` |
| `button_text` | *(translated)* | Button label. Use a plain string to override the translation |
| `show_icon` | `true` | Show the clipboard SVG icon inside the button |
| `copied_text` | *(translated)* | Feedback label shown for 2 s after a successful copy |
| `include_title` | `true` | Prepend `# Page Title` to the copied content |
| `include_frontmatter` | `false` | Prepend the YAML frontmatter block to the copied content |
| `page_types` | `[]` | Limit the button to specific page templates; empty = all pages |

### Per-page override

Any option can be overridden per page via the page's frontmatter:

```yaml
---
title: My Post
share_markdown_button:
    button_position: top
    include_frontmatter: true
---
```

---

## Internationalisation

The button label and copied feedback text are automatically translated based on the active site language. Supported languages:

| Code | Language | Button | Feedback |
|---|---|---|---|
| `en` | English | Copy as Markdown | Copied! |
| `es` | Spanish | Copiar como Markdown | ¡Copiado! |
| `fr` | French | Copier en Markdown | Copié ! |
| `pt` | Portuguese | Copiar como Markdown | Copiado! |
| `it` | Italian | Copia in Markdown | Copiato! |
| `de` | German | Als Markdown kopieren | Kopiert! |

To use a plain custom string instead of the auto-translated one, set `button_text` or `copied_text` to any value that is not an `ALLCAPS.KEY` translation key.

---

## How it works

1. **`onPageContentProcessed`** — after Grav processes the page, the plugin builds the Markdown payload and renders the button partial, injecting it into the content HTML at the configured position.

2. **Markdown building** — `$page->rawMarkdown()` retrieves the source. If the page has `process: twig: true`, the raw content is first passed through `$twig->processString()` so all Twig expressions are resolved — the result is still valid Markdown, never HTML. The title and/or frontmatter are then optionally prepended.

3. **Button partial** — a `<textarea>` visually hidden off-screen holds the full Markdown string (safer than a `data-` attribute for large or special-character content). A `<button>` references it by ID.

4. **JavaScript** (deferred, ~600 bytes) — on click, reads the textarea value and calls `navigator.clipboard.writeText()`. Falls back to `document.execCommand('copy')` for non-HTTPS contexts or older browsers. Switches the button to a "Copied!" state for 2 seconds.

---

## Styling

All CSS classes are prefixed with `.smb-` to avoid theme conflicts. Override any rule in your theme's custom CSS file:

```css
/* Match your theme's primary colour */
.smb-button {
    color: #fff;
    background-color: #6366f1;
    border-color: #6366f1;
}
.smb-button:hover {
    background-color: #4f46e5;
}
/* Copied state */
.smb-button--copied {
    background-color: #d1fae5;
    color: #065f46;
}
```

---

## Contributing

Bug reports and pull requests are welcome on the [GitHub repository](https://github.com/tucho235/grav-plugin-share-markdown-button).

---

## License

MIT — see [LICENSE](LICENSE).
