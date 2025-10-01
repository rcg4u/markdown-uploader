<?php
/*
Plugin Name: Ultimate Markdown Uploader
Description: Paste or upload Markdown to insert as blocks in Gutenberg or as HTML in Classic Editor.
Version: 2.0
Author: Narcolepticnerd & Copilot
*/

// Add meta box to post/page editor
add_action('add_meta_boxes', function() {
    add_meta_box('markdown_uploader', 'Markdown Uploader', 'markdown_uploader_meta_box', ['post', 'page'], 'normal', 'high');
});

function markdown_uploader_meta_box($post) {
    echo '<label for="markdown_content">Paste Markdown:</label><br />';
    echo '<textarea id="markdown_content" rows="10" style="width:100%"></textarea><br /><br />';
    echo '<label for="markdown_file">Or upload Markdown file:</label><br />';
    echo '<input type="file" id="markdown_file" accept=".md,.markdown,.txt" /><br />';
    echo '<button type="button" id="markdown_insert_btn" style="margin:10px 0;">Convert & Insert</button>';
    echo '<button type="button" id="markdown_clear_btn" style="margin:10px 5px;">Clear</button>';
    echo '<button type="button" id="markdown_clear_gb_btn" style="margin:10px 0;">Clear Gutenberg</button>';
    echo '<span id="markdown_status" style="margin-left:10px;"></span>';
    ?>
    <script type="text/javascript">
    if (typeof ajaxurl === 'undefined') {
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    }
    // File upload to textarea
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('markdown_file').addEventListener('change', function(e) {
            var file = e.target.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function(evt) {
                document.getElementById('markdown_content').value = evt.target.result;
            };
            reader.readAsText(file);
        });
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
