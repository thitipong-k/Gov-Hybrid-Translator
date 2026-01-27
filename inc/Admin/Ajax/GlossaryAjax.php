<?php
/**
 * Glossary AJAX Handlers
 * 
 * จัดการ AJAX requests สำหรับ Glossary:
 * - ดึงรายการคำศัพท์
 * - ค้นหาคำศัพท์
 * - สร้าง/แก้ไข/ลบคำศัพท์
 * 
 * @package GovHybridTranslator
 * @since 1.0.0
 * @updated 1.5.0 - ใช้ Custom Capabilities แทน manage_options
 */

namespace GovHybridTranslator\Admin\Ajax;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Modules\GlossaryManager;
use GovHybridTranslator\Core\Capabilities;

class GlossaryAjax {

    /**
     * ลงทะเบียน AJAX actions ทั้งหมด
     */
    public function register() {
        add_action('wp_ajax_ght_get_glossary_terms', [$this, 'get_glossary_terms']);
        add_action('wp_ajax_ght_search_glossary', [$this, 'search_glossary']);
        add_action('wp_ajax_ght_create_glossary_term', [$this, 'create_glossary_term']);
        add_action('wp_ajax_ght_update_glossary_term', [$this, 'update_glossary_term']);
        add_action('wp_ajax_ght_delete_glossary_term', [$this, 'delete_glossary_term']);
        add_action('wp_ajax_ght_get_glossary_categories', [$this, 'get_glossary_categories']);
    }

    /**
     * ดึงรายการคำศัพท์ทั้งหมด
     * รองรับ pagination และ filter ตาม category
     */
    public function get_glossary_terms() {
        check_ajax_referer('ght_save_translation', 'nonce');

        // ตรวจสอบสิทธิ์ - ต้องมี ght_manage_glossary
        if (!Capabilities::can_manage_glossary()) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $manager = new GlossaryManager();
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';

        $args = ['paged' => $page];
        if (!empty($category)) {
            $args['category'] = $category;
        }

        $result = $manager->get_glossary_terms($args);
        wp_send_json_success($result);
    }

    public function search_glossary() {
        check_ajax_referer('ght_save_translation', 'nonce');

        // ตรวจสอบสิทธิ์ - ต้องมี ght_manage_glossary
        if (!Capabilities::can_manage_glossary()) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;

        if (empty($query)) {
            wp_send_json_error(['message' => 'Search query is required']);
        }

        $manager = new GlossaryManager();
        $result = $manager->search_terms($query, 20, $page);
        wp_send_json_success($result);
    }

    public function create_glossary_term() {
        check_ajax_referer('ght_save_translation', 'nonce');

        // ตรวจสอบสิทธิ์ - ต้องมี ght_manage_glossary
        if (!Capabilities::can_manage_glossary()) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $data = [
            'thai_term' => isset($_POST['thai_term']) ? sanitize_text_field($_POST['thai_term']) : '',
            'english_term' => isset($_POST['english_term']) ? sanitize_text_field($_POST['english_term']) : '',
            'category' => isset($_POST['category']) ? sanitize_text_field($_POST['category']) : 'other',
        ];

        $manager = new GlossaryManager();
        $result = $manager->create_term($data);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'message' => 'Glossary term created successfully',
            'term_id' => $result
        ]);
    }

    public function update_glossary_term() {
        check_ajax_referer('ght_save_translation', 'nonce');

        // ตรวจสอบสิทธิ์ - ต้องมี ght_manage_glossary
        if (!Capabilities::can_manage_glossary()) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $term_id = isset($_POST['term_id']) ? absint($_POST['term_id']) : 0;
        $data = [
            'thai_term' => isset($_POST['thai_term']) ? sanitize_text_field($_POST['thai_term']) : '',
            'english_term' => isset($_POST['english_term']) ? sanitize_text_field($_POST['english_term']) : '',
            'category' => isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '',
        ];

        $manager = new GlossaryManager();
        $result = $manager->update_term($term_id, $data);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success(['message' => 'Glossary term updated successfully']);
    }

    public function delete_glossary_term() {
        check_ajax_referer('ght_save_translation', 'nonce');

        // ตรวจสอบสิทธิ์ - ต้องมี ght_manage_glossary
        if (!Capabilities::can_manage_glossary()) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $term_id = isset($_POST['term_id']) ? absint($_POST['term_id']) : 0;

        $manager = new GlossaryManager();
        $result = $manager->delete_term($term_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success(['message' => 'Glossary term deleted successfully']);
    }

    public function get_glossary_categories() {
        check_ajax_referer('ght_save_translation', 'nonce');

        // ตรวจสอบสิทธิ์ - ต้องมี ght_manage_glossary
        if (!Capabilities::can_manage_glossary()) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $manager = new GlossaryManager();
        $categories = $manager->get_categories();

        wp_send_json_success(['categories' => $categories]);
    }
}
