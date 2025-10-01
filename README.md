# Markdown Uploader Plugin

A WordPress plugin that allows you to paste or upload Markdown and insert it as blocks in Gutenberg or as HTML in the Classic Editor.

## Features
- Paste Markdown directly into a textarea meta box on posts and pages
- Upload a `.md`, `.markdown`, or `.txt` file to populate the Markdown field
- Convert Markdown to HTML using Parsedown
- Insert as Gutenberg blocks if using the block editor
- Insert as HTML if using the Classic Editor
- Clear the Markdown field with one click
- Clear all Gutenberg blocks with one click
- Simple, modern UI

## Usage
1. Edit or create a post or page.
2. Use the "Markdown Uploader" meta box:
   - Paste Markdown or upload a Markdown file.
   - Click **Convert & Insert** to add content to the editor.
   - Use **Clear** to empty the Markdown field.
   - Use **Clear Gutenberg** to remove all blocks from the post (Gutenberg only).

## Requirements
- WordPress 5.0 or higher
- Parsedown library (included in the plugin folder)

## Installation
1. Copy the plugin folder to `wp-content/plugins/` (or `mu-plugins/` for must-use).
2. Ensure `parsedown/Parsedown.php` is present in the plugin directory.
3. Activate the plugin from the WordPress admin (unless using as a must-use plugin).

## Credits
- [Parsedown](https://parsedown.org/) for Markdown parsing
- Developed by Narcolepticnerd

## License
MIT
