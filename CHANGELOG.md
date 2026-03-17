# Changelog

All notable changes to this project will be documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0] — 2026-03-16

### Added

- Initial release.
- Button that copies the current page's raw Markdown to the visitor's clipboard.
- Automatic Twig tag resolution for pages that use `process: twig: true` — Twig expressions are evaluated before copying so the result is clean Markdown, not template syntax.
- `include_title` option (default `true`) — prepends the page title as an `# H1` heading to the copied content, providing full context when pasting into AI chats or editors.
- `include_frontmatter` option (default `false`) — optionally prepends the YAML frontmatter block.
- `button_position` option — place the button at the `top`, `bottom` (default), or `both` ends of the content.
- `show_icon` option — toggles a clipboard SVG icon inside the button.
- `page_types` option — restricts the button to specific page templates; empty list means all pages.
- 2-second visual feedback ("Copied!") after a successful clipboard write.
- Modern `navigator.clipboard.writeText()` API with `execCommand('copy')` fallback for HTTP contexts and older browsers.
- Internationalisation via `languages.yaml` with support for **English, Spanish, French, Portuguese, Italian, and German**. Button label and feedback text are auto-translated based on the active site language. Plain custom strings bypass translation transparently.
- Full Admin panel integration via `blueprints.yaml` with translated field labels.
- CSS namespaced under `.smb-*` to prevent theme collisions.
- Assets (CSS + JS) are loaded only on pages where the button is displayed.
