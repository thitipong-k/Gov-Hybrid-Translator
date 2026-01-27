<?php
namespace GovHybridTranslator\Modules;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

class EditorEnhancer {

    public function register() {
        add_action('add_meta_boxes', [$this, 'add_original_content_meta_box']);
    }

    /**
     * Add a meta box to show the original Thai content.
     */
    public function add_original_content_meta_box() {
        $screens = ['post', 'page'];
        foreach ($screens as $screen) {
            add_meta_box(
                'ght_original_content',           // Unique ID
                'Original Thai Content (Reference)', // Box title
                [$this, 'render_original_content_box'], // Content callback
                $screen,                          // Post type
                'side',                           // Context (side or normal)
                'high'                            // Priority
            );
        }
    }

    /**
     * Render the meta box content.
     */
    public function render_original_content_box($post) {
        // 1. Check if this is an English translation
        $lang = get_post_meta($post->ID, '_gov_translator_lang', true);
        if ($lang !== 'en') {
            echo '<p class="description">This feature is only available for English translations.</p>';
            return;
        }

        // 2. Get the Group ID
        $group_id = get_post_meta($post->ID, '_gov_translator_group_id', true);
        if (empty($group_id)) {
            echo '<p class="description">No original content linked.</p>';
            return;
        }

        // 3. Find the original Thai post (same group, lang = th or empty)
        // Note: Original post might not have 'th' explicitly set if it was the source, 
        // but our logic sets it. Let's search by group ID and exclude current ID.
        $args = [
            'post_type' => $post->post_type,
            'meta_query' => [
                ['key' => '_gov_translator_group_id', 'value' => $group_id],
            ],
            'post__not_in' => [$post->ID],
            'posts_per_page' => 1
        ];

        $query = new \WP_Query($args);

        if ($query->have_posts()) {
            $original_post = $query->posts[0];
            
            echo '<div style="max-height: 400px; overflow-y: auto; background: #f9f9f9; padding: 10px; border: 1px solid #ddd;">';
            
            echo '<strong>Title:</strong>';
            echo '<p style="margin-top: 5px; margin-bottom: 15px;">' . esc_html($original_post->post_title) . '</p>';
            
            echo '<strong>Content:</strong>';
            echo '<div style="margin-top: 5px; font-size: 0.9em; line-height: 1.5;">';
            echo wp_kses_post($original_post->post_content); 
            echo '</div>';
            
            echo '</div>';
            
            echo '<p style="margin-top: 10px;"><a href="' . esc_url(get_edit_post_link($original_post->ID)) . '" target="_blank">Edit Original Post</a></p>';

        } else {
            echo '<p>Original post not found.</p>';
        }
    }
}
