<?php
/**
 * Content Review AJAX Handlers
 * 
 * จัดการ AJAX requests สำหรับ Content Review:
 * - ดึงเนื้อหาสำหรับ review พร้อม glossary terms
 * - แปลเนื้อหาพร้อม custom terms
 * - รองรับหลายภาษาเป้าหมาย
 * 
 * @package GovHybridTranslator
 * @since 1.4.0
 * @updated 1.5.0 - ใช้ Custom Capabilities แทน manage_options
 * @updated 2.0.0 - เพิ่ม target_lang parameter, ปรับปรุง response format
 */

namespace GovHybridTranslator\Admin\Ajax;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Modules\ContentReviewer;
use GovHybridTranslator\Core\Capabilities;

class ContentReviewAjax {

    /**
     * ลงทะเบียน AJAX actions
     */
    public function register() {
        add_action('wp_ajax_ght_get_post_content', [$this, 'get_post_content']);
        add_action('wp_ajax_ght_translate_with_terms', [$this, 'translate_with_terms']);
        add_action('wp_ajax_ght_get_translated_content', [$this, 'get_translated_content']);
        add_action('wp_ajax_ght_delete_translation', [$this, 'delete_translation']);
    }

    /**
     * ดึงเนื้อหาสำหรับ review
     * 
     * ส่งกลับ:
     * - content: เนื้อหา HTML
     * - found_terms: คำศัพท์ที่พบในเนื้อหา
     * - title: ชื่อ Post
     * - total_glossary_terms: จำนวน glossary terms ทั้งหมด
     */
    public function get_post_content() {
        check_ajax_referer('ght_save_translation', 'nonce');

        // ตรวจสอบสิทธิ์ - ต้องมี ght_translate
        if (!Capabilities::can_translate()) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        
        if (!$post_id) {
            wp_send_json_error(['message' => 'Invalid post ID']);
        }

        $reviewer = new ContentReviewer();
        $result = $reviewer->get_content($post_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'content' => $result['content'],
            'found_terms' => $result['found_terms'],
            'total_glossary_terms' => $result['total_glossary_terms'] ?? 0,
            'title' => get_the_title($post_id)
        ]);
    }

    /**
     * แปลเนื้อหาพร้อม custom terms
     * 
     * Request parameters:
     * - post_id: ID ของ Post ที่จะแปล
     * - custom_terms: Array ของ custom terms
     * - target_lang: ภาษาเป้าหมาย (default: 'en')
     * 
     * ขั้นตอน:
     * 1. บันทึก custom terms ลง Glossary
     * 2. แปลเนื้อหาพร้อม glossary replacement
     * 3. บันทึกผลลง post_meta
     */
    public function translate_with_terms() {
        check_ajax_referer('ght_save_translation', 'nonce');

        // ตรวจสอบสิทธิ์ - ต้องมี ght_translate
        if (!Capabilities::can_translate()) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $custom_terms = isset($_POST['custom_terms']) ? $_POST['custom_terms'] : [];
        // รองรับ target language จาก request
        $target_lang = isset($_POST['target_lang']) ? sanitize_text_field($_POST['target_lang']) : 'en';

        if (!$post_id) {
            wp_send_json_error(['message' => 'Invalid post ID']);
        }

        // Sanitize custom terms
        $sanitized_terms = [];
        if (is_array($custom_terms)) {
            foreach ($custom_terms as $term) {
                if (!empty($term['thai']) && !empty($term['english'])) {
                    $sanitized_terms[] = [
                        'thai' => sanitize_text_field($term['thai']),
                        'english' => sanitize_text_field($term['english']),
                        'category' => sanitize_text_field($term['category'] ?? 'other'),
                    ];
                }
            }
        }

        $reviewer = new ContentReviewer();
        $result = $reviewer->translate_with_custom_terms($post_id, $sanitized_terms, $target_lang);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'message' => sprintf('แปลเนื้อหาเป็นภาษา %s สำเร็จ!', strtoupper($target_lang)),
            'post_id' => $post_id,
            'target_lang' => $target_lang,
            'terms_processed' => count($sanitized_terms),
        ]);
    }

    /**
     * ดึงเนื้อหาที่แปลแล้ว
     * 
     * ใช้สำหรับแสดงผลใน Review Content modal
     * ให้ผู้ใช้สามารถดูเนื้อหาที่แปลแล้วได้
     * 
     * @since 2.2.0
     */
    public function get_translated_content() {
        check_ajax_referer('ght_save_translation', 'nonce');

        // ตรวจสอบสิทธิ์
        if (!Capabilities::can_translate()) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $lang = isset($_POST['lang']) ? sanitize_text_field($_POST['lang']) : 'en';

        if (!$post_id) {
            wp_send_json_error(['message' => 'Invalid post ID']);
        }

        $reviewer = new ContentReviewer();
        $result = $reviewer->get_translated_content($post_id, $lang);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success($result);
    }

    /**
     * ลบ Translation ที่มีอยู่
     * 
     * ลบ translated content จาก post_meta
     * 
     * @since 2.3.0
     */
    public function delete_translation() {
        check_ajax_referer('ght_save_translation', 'nonce');

        // ตรวจสอบสิทธิ์
        if (!Capabilities::can_translate()) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $lang = isset($_POST['lang']) ? sanitize_text_field($_POST['lang']) : 'en';

        if (!$post_id) {
            wp_send_json_error(['message' => 'Invalid post ID']);
        }

        // ใช้ TranslationMeta เพื่อลบ (static method)
        $result = \GovHybridTranslator\Core\TranslationMeta::delete($post_id, $lang);

        if ($result) {
            wp_send_json_success([
                'message' => sprintf('ลบ Translation ภาษา %s สำเร็จ!', strtoupper($lang)),
                'post_id' => $post_id,
                'lang' => $lang
            ]);
        } else {
            wp_send_json_error(['message' => 'ไม่พบ Translation หรือเกิดข้อผิดพลาดในการลบ']);
        }
    }
}

