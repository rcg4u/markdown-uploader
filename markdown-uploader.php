<?php
/*
Plugin Name: Ultimate Markdown Uploader
Description: Paste or upload Markdown to insert as blocks in Gutenberg or as HTML in Classic Editor.
Version: 2.0
Author: Narcolepticnerd & Copilot
*/

// Add meta box to post/page editor
add_action('add_meta_boxes', function() {
    // Add meta box to all public post types
    $post_types = get_post_types(['public' => true], 'names');
    foreach ($post_types as $pt) {
        add_meta_box('markdown_uploader', 'Markdown Uploader', 'markdown_uploader_meta_box', $pt, 'normal', 'high');
    }
});

function markdown_uploader_meta_box($post) {
    echo '<label for="markdown_content">Paste Markdown:</label><br />';
    // Advanced toolbar
    echo '<div id="markdown_toolbar" style="margin-bottom:6px;">';
    $buttons = [
        // Markdown
        ['Bold', '**bold**', 'markdown'],
        ['Italic', '*italic*', 'markdown'],
        ['Heading', '# Heading', 'markdown'],
        ['Link', '[text](url)', 'markdown'],
        ['Image', '![alt](url)', 'markdown'],
        ['Code', '`code`', 'markdown'],
        ['Blockquote', '> quote', 'markdown'],
        ['UL', '- List item', 'markdown'],
        ['OL', '1. List item', 'markdown'],
        ['HR', '---', 'markdown'],
        // HTML
        ['<b>', '<b>bold</b>', 'html'],
        ['<i>', '<i>italic</i>', 'html'],
        ['<h2>', '<h2>Heading</h2>', 'html'],
        ['<a>', '<a href="url">text</a>', 'html'],
        ['<img>', '<img src="url" alt="alt" />', 'html'],
        ['<code>', '<code>code</code>', 'html'],
        ['<blockquote>', '<blockquote>quote</blockquote>', 'html'],
        ['<ul>', '<ul>\n  <li>Item</li>\n</ul>', 'html'],
        ['<ol>', '<ol>\n  <li>Item</li>\n</ol>', 'html'],
        ['<hr>', '<hr />', 'html'],
    ];
    foreach ($buttons as $btn) {
        $label = htmlspecialchars($btn[0]);
        $snippet = htmlspecialchars($btn[1]);
        $type = $btn[2];
        echo "<button type='button' class='md-toolbar-btn' data-snippet='{$snippet}' data-type='{$type}' style='margin-right:2px;margin-bottom:2px;'>{$label}</button>";
    }
    echo '</div>';
    echo '<textarea id="markdown_content" rows="10" style="width:100%"></textarea><br /><br />';
    echo '<label for="markdown_file">Or upload Markdown file:</label><br />';
    echo '<input type="file" id="markdown_file" accept=".md,.markdown,.txt" /><br />';
    // New controls: Load current post content and Resubmit (replace content)
    echo '<button type="button" id="markdown_load_btn" style="margin:10px 5px;">Load Current</button>';
    echo '<button type="button" id="markdown_insert_btn" style="margin:10px 0;">Convert & Insert</button>';
    echo '<button type="button" id="markdown_clear_btn" style="margin:10px 5px;">Clear</button>';
    echo '<button type="button" id="markdown_clear_gb_btn" style="margin:10px 0;">Clear Gutenberg</button>';
    echo '<button type="button" id="markdown_resubmit_btn" style="margin:10px 5px;">Resubmit</button>';
    echo '<button type="button" id="markdown_preview_btn" style="margin:10px 5px;">Preview</button>';
    echo '<label style="margin-left:8px;"><input type="checkbox" id="markdown_live_preview" /> Live Preview</label>';
    echo '<span id="markdown_status" style="margin-left:10px;"></span>';
    // Preview panel container
    echo '<div id="markdown_preview_container" style="margin-top:12px;border:1px solid #ddd;border-radius:4px;background:#fafafa;">'
        . '<div style="padding:8px 10px;border-bottom:1px solid #eee;font-weight:600;">Preview</div>'
        . '<div id="markdown_preview" style="padding:10px;min-height:120px;overflow:auto;background:#fff;"></div>'
        . '</div>';
    ?>
    <!-- HTML -> Markdown converter for Load Current -->
    <script src="https://cdn.jsdelivr.net/npm/turndown@7.1.2/dist/turndown.min.js"></script>
    <script type="text/javascript">
    if (typeof ajaxurl === 'undefined') {
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    }
    // Toolbar button logic
    function insertAtCursor(textarea, snippet) {
        if (!textarea) return;
        var scrollPos = textarea.scrollTop;
        var caretPos = textarea.selectionStart;
        var endPos = textarea.selectionEnd;
        var value = textarea.value;
        textarea.value = value.substring(0, caretPos) + snippet + value.substring(endPos, value.length);
        textarea.selectionStart = textarea.selectionEnd = caretPos + snippet.length;
        textarea.focus();
        textarea.scrollTop = scrollPos;
    }
    document.addEventListener('DOMContentLoaded', function() {
        // Toolbar button events
        document.querySelectorAll('.md-toolbar-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var snippet = btn.getAttribute('data-snippet');
                var textarea = document.getElementById('markdown_content');
                insertAtCursor(textarea, snippet);
            });
        });
        document.getElementById('markdown_file').addEventListener('change', function(e) {
            var file = e.target.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function(evt) {
                document.getElementById('markdown_content').value = evt.target.result;
                // Update preview if live preview is enabled
                var live = document.getElementById('markdown_live_preview');
                if (live && live.checked) {
                    if (typeof renderPreview === 'function') renderPreview();
                }
            };
            reader.readAsText(file);
        });
        // Load Current: pull existing post content (Gutenberg or Classic) and convert to Markdown
        var getCurrentPostHTML = function() {
            try {
                if (window.wp && wp.data && wp.data.select && typeof wp.data.select === 'function') {
                    var editorSel = wp.data.select('core/editor');
                    if (editorSel && typeof editorSel.getEditedPostContent === 'function') {
                        // Serialized blocks HTML
                        return editorSel.getEditedPostContent() || '';
                    }
                    if (editorSel && typeof editorSel.getBlocks === 'function' && wp.blocks && typeof wp.blocks.serialize === 'function') {
                        var blks = editorSel.getBlocks();
                        return wp.blocks.serialize(blks) || '';
                    }
                }
                // Classic editor (TinyMCE or textarea)
                if (window.tinymce && tinymce.get && tinymce.get('content')) {
                    return tinymce.get('content').getContent({ format: 'raw' }) || '';
                }
                var classic = document.getElementById('content');
                if (classic) return classic.value || '';
            } catch (e) {
                console.warn('Failed to get current post HTML', e);
            }
            return '';
        };
        var htmlToMarkdown = function(html) {
            try {
                if (window.TurndownService) {
                    var td = new TurndownService({ headingStyle: 'atx', codeBlockStyle: 'fenced' });
                    return td.turndown(html || '');
                }
            } catch (e) {
                console.warn('Turndown conversion failed', e);
            }
            // Fallback: return original HTML if converter unavailable
            return html || '';
        };
        document.getElementById('markdown_load_btn').addEventListener('click', function() {
            var status = document.getElementById('markdown_status');
            status.textContent = 'Loading current content...';
            var html = getCurrentPostHTML();
            if (!html) {
                status.textContent = 'No content found.';
                return;
            }
            var md = htmlToMarkdown(html);
            document.getElementById('markdown_content').value = md;
            status.textContent = 'Loaded into textarea.';
            // Update preview if live preview is enabled
            var live = document.getElementById('markdown_live_preview');
            if (live && live.checked) {
                if (typeof renderPreview === 'function') renderPreview();
            }
        });
        // --- Preview logic ---
        function debounce(fn, delay) {
            var t;
            return function() {
                var ctx = this, args = arguments;
                clearTimeout(t);
                t = setTimeout(function(){ fn.apply(ctx, args); }, delay);
            };
        }
        function renderPreview() {
            var md = document.getElementById('markdown_content').value || '';
            var preview = document.getElementById('markdown_preview');
            var status = document.getElementById('markdown_status');
            if (!md.trim()) { preview.innerHTML = ''; return; }
            var xhr = new XMLHttpRequest();
            xhr.open('POST', ajaxurl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    preview.innerHTML = xhr.responseText || '';
                    if (status) status.textContent = 'Preview updated.';
                } else {
                    preview.innerHTML = '<em>Preview error: ' + xhr.status + '</em>';
                }
            };
            xhr.onerror = function() {
                preview.innerHTML = '<em>Network error while rendering preview.</em>';
            };
            xhr.send('action=markdown_uploader_convert&markdown=' + encodeURIComponent(md));
        }
        var debouncedPreviewHandler = null;
        function enableLivePreview() {
            var ta = document.getElementById('markdown_content');
            if (!debouncedPreviewHandler) debouncedPreviewHandler = debounce(renderPreview, 400);
            ta.addEventListener('input', debouncedPreviewHandler);
            renderPreview();
        }
        function disableLivePreview() {
            var ta = document.getElementById('markdown_content');
            if (debouncedPreviewHandler) ta.removeEventListener('input', debouncedPreviewHandler);
        }
        var liveToggle = document.getElementById('markdown_live_preview');
        if (liveToggle) {
            liveToggle.addEventListener('change', function() {
                if (liveToggle.checked) {
                    enableLivePreview();
                } else {
                    disableLivePreview();
                }
            });
        }
        var previewBtn = document.getElementById('markdown_preview_btn');
        if (previewBtn) {
            previewBtn.addEventListener('click', function() {
                renderPreview();
            });
        }
        document.getElementById('markdown_insert_btn').addEventListener('click', function() {
            var md = document.getElementById('markdown_content').value;
            var status = document.getElementById('markdown_status');
            status.textContent = 'Converting...';
            var xhr = new XMLHttpRequest();
            xhr.open('POST', ajaxurl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var html = xhr.responseText;
                    // Gutenberg
                    if (window.wp && wp.data && wp.data.dispatch && wp.blocks && wp.blocks.rawHandler) {
                        var blocks = wp.blocks.rawHandler({HTML: html});
                        if (blocks && blocks.length) {
                            wp.data.dispatch('core/editor').insertBlocks(blocks);
                            status.textContent = 'Inserted as blocks!';
                            return;
                        }
                    }
                    // Classic Editor
                    var classic = document.getElementById('content');
                    if (classic) {
                        if (typeof classic.selectionStart === 'number') {
                            var start = classic.selectionStart;
                            var end = classic.selectionEnd;
                            var text = classic.value;
                            classic.value = text.slice(0, start) + html + text.slice(end);
                        } else {
                            classic.value += html;
                        }
                        status.textContent = 'Inserted as HTML!';
                        return;
                    }
                    status.textContent = 'Could not insert. Please copy and paste.';
                } else {
                    status.textContent = 'Error: ' + xhr.status;
                }
            };
            xhr.onerror = function() {
                status.textContent = 'Network error.';
            };
            xhr.send('action=markdown_uploader_convert&markdown=' + encodeURIComponent(md));
        });
        document.getElementById('markdown_clear_btn').addEventListener('click', function() {
            document.getElementById('markdown_content').value = '';
        });
        document.getElementById('markdown_clear_gb_btn').addEventListener('click', function() {
            if (window.wp && wp.data && wp.data.dispatch) {
                wp.data.dispatch('core/editor').resetBlocks([]);
            } else {
                alert('Gutenberg editor not detected.');
            }
        });
        // Resubmit: replace entire post content with converted Markdown
        document.getElementById('markdown_resubmit_btn').addEventListener('click', function() {
            var md = document.getElementById('markdown_content').value;
            var status = document.getElementById('markdown_status');
            status.textContent = 'Resubmitting...';
            var xhr = new XMLHttpRequest();
            xhr.open('POST', ajaxurl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var html = xhr.responseText;
                    // Gutenberg: clear and insert as blocks
                    if (window.wp && wp.data && wp.data.dispatch && wp.blocks && wp.blocks.rawHandler) {
                        try {
                            wp.data.dispatch('core/editor').resetBlocks([]);
                            var blocks = wp.blocks.rawHandler({ HTML: html });
                            if (blocks && blocks.length) {
                                wp.data.dispatch('core/editor').insertBlocks(blocks);
                                status.textContent = 'Replaced post content (blocks)!';
                                return;
                            }
                        } catch (e) {
                            console.warn('Gutenberg resubmit failed', e);
                        }
                    }
                    // Classic editor: overwrite content
                    var classic = document.getElementById('content');
                    if (window.tinymce && tinymce.get && tinymce.get('content')) {
                        tinymce.get('content').setContent(html || '');
                        status.textContent = 'Replaced post content (classic)!';
                        return;
                    } else if (classic) {
                        classic.value = html || '';
                        status.textContent = 'Replaced post content (classic)!';
                        return;
                    }
                    status.textContent = 'Could not replace content. Copy & paste manually.';
                } else {
                    status.textContent = 'Error: ' + xhr.status;
                }
            };
            xhr.onerror = function() {
                status.textContent = 'Network error.';
            };
            xhr.send('action=markdown_uploader_convert&markdown=' + encodeURIComponent(md));
        });
    });
    </script>
    <?php
}

// AJAX handler for Markdown conversion
add_action('wp_ajax_markdown_uploader_convert', 'markdown_uploader_ajax_convert');
if (!function_exists('markdown_uploader_ajax_convert')) {
function markdown_uploader_ajax_convert() {
    if (!current_user_can('edit_posts')) die('');
    if ( ! class_exists( 'Parsedown' ) ) {
        require_once plugin_dir_path( __FILE__ ) . 'parsedown/Parsedown.php';
    }
    $markdown = isset($_POST['markdown']) ? wp_unslash($_POST['markdown']) : '';
    $Parsedown = new Parsedown();
    $html = $Parsedown->text($markdown);
    echo $html;
    wp_die();
}
}
