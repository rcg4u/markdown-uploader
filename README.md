# Markdown Uploader Plugin

A WordPress plugin that lets you paste or upload Markdown and insert it as blocks in Gutenberg or as HTML in the Classic Editor. Includes a toolbar for quick snippets, live preview, and a one‑click “Get HTML” action to copy or download the converted HTML.

## Features

- Paste Markdown directly into a meta box on any public post type (posts, pages, CPTs)
- Upload a `.md`, `.markdown`, or `.txt` file to populate the Markdown field
- Convert Markdown to HTML using Parsedown (server‑side via admin‑ajax)
- Insert as Gutenberg blocks (block editor) or as HTML (Classic Editor)
- Live Preview toggle and one‑click Preview of rendered HTML
- Get HTML: convert current Markdown and show a copyable textarea, plus buttons to Copy and Download `.html`
- Load Current: pull current editor content (Gutenberg/Classic), convert from HTML to Markdown (via Turndown), and load into the textarea
- Resubmit: replace entire post content with the converted result (clears blocks then inserts, or overwrites Classic content)
- Advanced toolbar with Markdown/HTML snippet buttons (bold, italic, heading, link, image, code, lists, etc.)
- Clear the Markdown field with one click
- Clear all Gutenberg blocks with one click
- Simple, modern UI

## Usage

1. Edit or create any public post type (post, page, or custom post type).
2. In the “Markdown Uploader” meta box:
   - Paste Markdown or upload a Markdown file.
   - Use the toolbar buttons to insert Markdown/HTML snippets.
   - Click **Preview** (or enable **Live Preview**) to see rendered HTML.
   - Click **Convert & Insert** to insert as blocks (Gutenberg) or HTML (Classic).
   - Click **Get HTML** to convert and open the HTML Output panel (Copy or Download `.html`).
   - Click **Load Current** to pull the post’s current content and convert it to Markdown into the textarea.
   - Click **Resubmit** to replace the entire post content with the converted result.
   - Use **Clear** to empty the Markdown field; use **Clear Gutenberg** to remove all blocks (Gutenberg only).

## Requirements

- WordPress 5.0 or higher
- Parsedown library (included in the plugin folder)
- Admin network access to `admin-ajax.php` for conversion calls
- (Optional) Turndown JS (loaded via CDN in the editor) for the “Load Current” HTML→Markdown feature

## Installation

1. Copy the plugin folder to `wp-content/plugins/` (or `mu-plugins/` for must-use).
2. Ensure `parsedown/Parsedown.php` is present in the plugin directory.
3. Activate the plugin from the WordPress admin (unless using as a must‑use plugin).

## Credits

- [Parsedown](https://parsedown.org/) for Markdown parsing
- [Turndown](https://github.com/mixmark-io/turndown) for HTML→Markdown conversion in “Load Current”
- Developed by Narcolepticnerd
- Toolbar/UX enhancements by GitHub Copilot

## License

MIT
