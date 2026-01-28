<?php
namespace GovHybridTranslator\Modules;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

/**
 * Glossary Manager Module
 * Handles CRUD operations for glossary terms.
 */
class GlossaryManager {

    /**
     * Get glossary terms with pagination and filtering.
     *
     * @param array $args Query arguments.
     * @return array
     */
    public function get_glossary_terms($args = []) {
        $defaults = [
            'posts_per_page' => 20,
            'paged' => 1,
            'post_type' => 'gov_glossary',
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        ];

        $args = wp_parse_args($args, $defaults);

        // Add taxonomy filter if provided
        if (!empty($args['category'])) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'gov_glossary_type',
                    'field' => 'slug',
                    'terms' => $args['category'],
                ],
            ];
            unset($args['category']);
        }

        $query = new \WP_Query($args);
        $terms = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $categories = wp_get_post_terms($post_id, 'gov_glossary_type');
                $category_name = !empty($categories) ? $categories[0]->name : 'Other';
                
                $terms[] = [
                    'id' => $post_id,
                    'thai_term' => get_the_title(),
                    'english_term' => get_post_meta($post_id, '_gov_glossary_en_term', true),
                    'category' => $category_name,
                    'category_slug' => !empty($categories) ? $categories[0]->slug : 'other',
                    'created_date' => get_the_date('Y-m-d H:i:s'),
                    'modified_date' => get_the_modified_date('Y-m-d H:i:s'),
                ];
            }
            wp_reset_postdata();
        }

        return [
            'terms' => $terms,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'current_page' => $args['paged'],
        ];
    }

    /**
     * Search glossary terms.
     *
     * @param string $query Search query.
     * @param int $per_page Results per page.
     * @param int $paged Current page.
     * @return array
     */
    public function search_terms($query, $per_page = 20, $paged = 1) {
        global $wpdb;

        // Search in both title and meta
        $search_query = $wpdb->prepare(
            "SELECT DISTINCT p.ID 
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'gov_glossary'
            AND p.post_status = 'publish'
            AND (
                p.post_title LIKE %s
                OR (pm.meta_key = '_gov_glossary_en_term' AND pm.meta_value LIKE %s)
            )
            ORDER BY p.post_title ASC",
            '%' . $wpdb->esc_like($query) . '%',
            '%' . $wpdb->esc_like($query) . '%'
        );

        $post_ids = $wpdb->get_col($search_query);

        if (empty($post_ids)) {
            return [
                'terms' => [],
                'total' => 0,
                'pages' => 0,
                'current_page' => $paged,
            ];
        }

        return $this->get_glossary_terms([
            'post__in' => $post_ids,
            'posts_per_page' => $per_page,
            'paged' => $paged,
        ]);
    }

    /**
     * Get single glossary term by ID.
     *
     * @param int $id Post ID.
     * @return array|null
     */
    public function get_term_by_id($id) {
        $post = get_post($id);

        if (!$post || $post->post_type !== 'gov_glossary') {
            return null;
        }

        $categories = wp_get_post_terms($id, 'gov_glossary_type');
        $category_name = !empty($categories) ? $categories[0]->name : 'Other';

        return [
            'id' => $id,
            'thai_term' => $post->post_title,
            'english_term' => get_post_meta($id, '_gov_glossary_en_term', true),
            'category' => $category_name,
            'category_slug' => !empty($categories) ? $categories[0]->slug : 'other',
            'created_date' => get_the_date('Y-m-d H:i:s', $id),
            'modified_date' => get_the_modified_date('Y-m-d H:i:s', $id),
        ];
    }

    /**
     * Create new glossary term.
     *
     * @param array $data Term data.
     * @return int|\WP_Error Post ID or error.
     */
    public function create_term($data) {
        // Validate required fields
        if (empty($data['thai_term']) || empty($data['english_term'])) {
            return new \WP_Error('missing_data', 'Thai term and English term are required.');
        }

        // Create post
        $post_id = wp_insert_post([
            'post_title' => sanitize_text_field($data['thai_term']),
            'post_type' => 'gov_glossary',
            'post_status' => 'publish',
        ]);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // Save English term
        update_post_meta($post_id, '_gov_glossary_en_term', sanitize_text_field($data['english_term']));

        // Set category
        if (!empty($data['category'])) {
            wp_set_object_terms($post_id, $data['category'], 'gov_glossary_type');
        }

        // Log activity
        // บันทึกกิจกรรมการเพิ่มคำศัพท์
        (new ActivityLogger())->log('glossary_added', 'glossary', $post_id, [
            'term' => $data['thai_term'],
            'en_term' => $data['english_term']
        ]);

        return $post_id;
    }


    /**
     * Update existing glossary term.
     *
     * @param int $id Post ID.
     * @param array $data Term data.
     * @return bool|\WP_Error
     */
    public function update_term($id, $data) {
        // Verify post exists
        $post = get_post($id);
        if (!$post || $post->post_type !== 'gov_glossary') {
            return new \WP_Error('invalid_post', 'Glossary term not found.');
        }

        // Update post title if provided
        if (!empty($data['thai_term'])) {
            wp_update_post([
                'ID' => $id,
                'post_title' => sanitize_text_field($data['thai_term']),
            ]);
        }

        // Update English term if provided
        if (!empty($data['english_term'])) {
            update_post_meta($id, '_gov_glossary_en_term', sanitize_text_field($data['english_term']));
        }

        // Update category if provided
        if (!empty($data['category'])) {
            wp_set_object_terms($id, $data['category'], 'gov_glossary_type');
        }

        // Log activity
        // บันทึกกิจกรรมการแก้ไขคำศัพท์
        (new ActivityLogger())->log('glossary_updated', 'glossary', $id, [
            'changes' => $data
        ]);

        return true;
    }


    /**
     * Delete glossary term.
     *
     * @param int $id Post ID.
     * @return bool|\WP_Error
     */
    public function delete_term($id) {
        // Verify post exists
        $post = get_post($id);
        if (!$post || $post->post_type !== 'gov_glossary') {
            return new \WP_Error('invalid_post', 'Glossary term not found.');
        }

        // เก็บชื่อไว้ก่อนลบ เพื่อนำไปบันทึก Log
        $term_name = $post->post_title;
        $term_en = get_post_meta($id, '_gov_glossary_en_term', true);

        $result = wp_delete_post($id, true);

        if (!$result) {
            return new \WP_Error('delete_failed', 'Failed to delete glossary term.');
        }

        // Log activity
        // บันทึกกิจกรรมการลบคำศัพท์ (ระบุชื่อคำศัพท์ที่ลบไป)
        (new ActivityLogger())->log('glossary_deleted', 'glossary', $id, [
            'term' => $term_name,
            'en_term' => $term_en
        ]);

        return true;
    }


    /**
     * Get all glossary categories.
     *
     * @return array
     */
    public function get_categories() {
        $terms = get_terms([
            'taxonomy' => 'gov_glossary_type',
            'hide_empty' => false,
        ]);

        if (is_wp_error($terms)) {
            return [];
        }

        $categories = [];
        foreach ($terms as $term) {
            $categories[] = [
                'slug' => $term->slug,
                'name' => $term->name,
                'count' => $term->count,
            ];
        }

        return $categories;
    }

    /**
     * Ensure default categories exist.
     */
    public function ensure_default_categories() {
        $defaults = [
            'person' => 'บุคคล (Person)',
            'position' => 'ตำแหน่ง (Position)',
            'unit' => 'หน่วยงาน (Unit)',
            'other' => 'อื่นๆ (Other)',
        ];

        foreach ($defaults as $slug => $name) {
            if (!term_exists($slug, 'gov_glossary_type')) {
                wp_insert_term($name, 'gov_glossary_type', ['slug' => $slug]);
            }
        }
    }
}
