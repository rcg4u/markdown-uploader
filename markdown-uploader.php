<?php
/*
Plugin Name: Markdown Uploader
Description: Allows uploading or pasting Markdown to posts/pages and renders it as HTML.
Version: 1.0
Author: Narcolepticnerd
*/

// Include Parsedown library
if ( ! class_exists( 'Parsedown' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'parsedown/Parsedown.php';
}

// Add meta box to post/page editor
add_action('add_meta_boxes', function() {
    add_meta_box('markdown_uploader', 'Markdown Uploader', 'markdown_uploader_meta_box', ['post', 'page'], 'normal', 'high');
});

function markdown_uploader_meta_box($post) {
    $markdown = get_post_meta($post->ID, '_markdown_content', true);
    echo '<label for="markdown_content">Paste Markdown:</label><br />';
    echo '<textarea id="markdown_content" name="markdown_content" rows="10" style="width:100%">' . esc_textarea($markdown) . '</textarea><br />';
    echo '<button type="button" id="submit_markdown" style="margin-top:8px;">Submit Markdown</button><br /><br />';
    echo '<div id="markdown_success" style="color:green; display:none;">Submission successful</div>';
    echo '<label for="markdown_file">Or upload Markdown file:</label><br />';
    echo '<input type="file" id="markdown_file" name="markdown_file" accept=".md,.markdown,.txt" /><br />';
    // Remove the old message
    // Add JS for AJAX submit and UI feedback
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var submitBtn = document.getElementById('submit_markdown');
        var textarea = document.getElementById('markdown_content');
        var successMsg = document.getElementById('markdown_success');
        if (submitBtn && textarea && successMsg) {
            submitBtn.addEventListener('click', function() {
                var data = new FormData();
                data.append('action', 'markdown_uploader_submit');
                data.append('post_id', <?php echo (int)$post->ID; ?>);
                data.append('markdown_content', textarea.value);
                fetch(ajaxurl, {
                    method: 'POST',
                    body: data,
                    credentials: 'same-origin'
                })
                .then(r => r.json())
                .then(function(resp) {
                    if (resp.success && resp.blocks) {
                        // Replace all blocks in Gutenberg with the new blocks
                        if (window.wp && wp.data && wp.data.dispatch && wp.blocks && wp.blocks.parse) {
                            var blocks = wp.blocks.parse(resp.blocks);
                            wp.data.dispatch('core/block-editor').resetBlocks(blocks);
                        } else if (window.wp && wp.data && wp.data.dispatch) {
                            // Fallback: set post_content
                            wp.data.dispatch('core/editor').editPost({ content: resp.blocks });
                        }
                        textarea.value = '';
                        successMsg.style.display = 'block';
                        setTimeout(function(){ successMsg.style.display = 'none'; }, 2000);
                    }
                });
            });
        }
    });
    </script>
    <?php
    // Add JS to clear inputs after successful upload/paste
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var form = document.getElementById('post');
        if (form) {
            form.addEventListener('submit', function(e) {
                // Wait for the post to be saved, then clear the inputs
                setTimeout(function() {
                    var textarea = document.getElementById('markdown_content');
                    var fileInput = document.getElementById('markdown_file');
                    if (textarea) textarea.value = '';
                    if (fileInput) fileInput.value = '';
                }, 1000); // Delay to allow save
            });
        }
    });
    </script>
    <?php
}

// Save Markdown when post is saved (for file upload or normal post save)
add_action('save_post', function($post_id) {
    $markdown = '';
    if (isset($_POST['markdown_content'])) {
        $markdown = sanitize_textarea_field($_POST['markdown_content']);
        update_post_meta($post_id, '_markdown_content', $markdown);
    }
    // Handle file upload
    if (!empty($_FILES['markdown_file']['tmp_name'])) {
        $content = file_get_contents($_FILES['markdown_file']['tmp_name']);
        $markdown = sanitize_textarea_field($content);
        update_post_meta($post_id, '_markdown_content', $markdown);
    }
    // If we have markdown, convert to Gutenberg blocks and update post_content
    if (!empty($markdown)) {
        if ( ! class_exists( 'Parsedown' ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'parsedown/Parsedown.php';
        }
        $Parsedown = new Parsedown();
        $html = $Parsedown->text($markdown);
        // Simple HTML to Gutenberg block conversion
        // Paragraphs
        $blocks = preg_replace('/<p>(.*?)<\/p>/is', "<!-- wp:paragraph -->\n<p>$1</p>\n<!-- /wp:paragraph -->", $html);
        // Headings (h1-h6)
        for ($i = 1; $i <= 6; $i++) {
            $blocks = preg_replace("/<h$i>(.*?)<\/h$i>/is", "<!-- wp:heading {\"level\":$i} -->\n<h$i>$1</h$i>\n<!-- /wp:heading -->", $blocks);
        }
        // Unordered lists
        $blocks = preg_replace('/<ul>(.*?)<\/ul>/is', "<!-- wp:list -->\n<ul>$1</ul>\n<!-- /wp:list -->", $blocks);
        // Ordered lists
        $blocks = preg_replace('/<ol>(.*?)<\/ol>/is', "<!-- wp:list {\"ordered\":true} -->\n<ol>$1</ol>\n<!-- /wp:list -->", $blocks);
        // Blockquotes
        $blocks = preg_replace('/<blockquote>(.*?)<\/blockquote>/is', "<!-- wp:quote -->\n<blockquote>$1</blockquote>\n<!-- /wp:quote -->", $blocks);
        // Images
        $blocks = preg_replace('/<img(.*?)>/is', "<!-- wp:image --><figure class=\"wp-block-image\"><img$1 /></figure><!-- /wp:image -->", $blocks);
        // Code blocks
        $blocks = preg_replace('/<pre><code>(.*?)<\/code><\/pre>/is', "<!-- wp:code -->\n<pre><code>$1</code></pre>\n<!-- /wp:code -->", $blocks);
        // Remove extra newlines
        $blocks = preg_replace("/\n{3,}/", "\n\n", $blocks);
        // Update post_content with blocks
        remove_action('save_post', __FUNCTION__); // Prevent infinite loop
        wp_update_post([
            'ID' => $post_id,
            'post_content' => $blocks
        ]);
        add_action('save_post', __FUNCTION__); // Re-add action
    }
});

// AJAX handler for markdown submission
add_action('wp_ajax_markdown_uploader_submit', function() {
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Permission denied');
    }
    $post_id = intval($_POST['post_id'] ?? 0);
    $markdown = sanitize_textarea_field($_POST['markdown_content'] ?? '');
    if ($post_id && $markdown) {
        update_post_meta($post_id, '_markdown_content', $markdown);
        if ( ! class_exists( 'Parsedown' ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'parsedown/Parsedown.php';
        }
        $Parsedown = new Parsedown();
        $html = $Parsedown->text($markdown);
        // Simple HTML to Gutenberg block conversion
        $blocks = preg_replace('/<p>(.*?)<\/p>/is', "<!-- wp:paragraph -->\n<p>$1</p>\n<!-- /wp:paragraph -->", $html);
        for ($i = 1; $i <= 6; $i++) {
            $blocks = preg_replace("/<h$i>(.*?)<\/h$i>/is", "<!-- wp:heading {\"level\":$i} -->\n<h$i>$1</h$i>\n<!-- /wp:heading -->", $blocks);
        }
        $blocks = preg_replace('/<ul>(.*?)<\/ul>/is', "<!-- wp:list -->\n<ul>$1</ul>\n<!-- /wp:list -->", $blocks);
        $blocks = preg_replace('/<ol>(.*?)<\/ol>/is', "<!-- wp:list {\"ordered\":true} -->\n<ol>$1</ol>\n<!-- /wp:list -->", $blocks);
        $blocks = preg_replace('/<blockquote>(.*?)<\/blockquote>/is', "<!-- wp:quote -->\n<blockquote>$1</blockquote>\n<!-- /wp:quote -->", $blocks);
        $blocks = preg_replace('/<img(.*?)>/is', "<!-- wp:image --><figure class=\"wp-block-image\"><img$1 /></figure><!-- /wp:image -->", $blocks);
        $blocks = preg_replace('/<pre><code>(.*?)<\/code><\/pre>/is', "<!-- wp:code -->\n<pre><code>$1</code></pre>\n<!-- /wp:code -->", $blocks);
        $blocks = preg_replace("/\n{3,}/", "\n\n", $blocks);
        wp_update_post([
            'ID' => $post_id,
            'post_content' => $blocks
        ]);
        wp_send_json_success([ 'blocks' => $blocks ]);
    } else {
        wp_send_json_error('Missing data');
    }
});

// Render Markdown on the front end
add_filter('the_content', function($content) {
    global $post;
    $markdown = get_post_meta($post->ID, '_markdown_content', true);
    if ($markdown) {
        if ( ! class_exists( 'Parsedown' ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'parsedown/Parsedown.php';
        }
        $Parsedown = new Parsedown();
        $html = $Parsedown->text($markdown);
        return $html . $content;
    }
    return $content;
});
