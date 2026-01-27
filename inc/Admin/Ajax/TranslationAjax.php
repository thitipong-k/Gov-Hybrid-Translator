<?php
/**
 * Translation AJAX Handlers
 * 
 * จัดการ AJAX requests สำหรับการแปล:
 * - แปล Pages/Posts
 * - แปลไปยังภาษาเป้าหมาย
 * - แปล Categories/Menus
 * 
 * @package GovHybridTranslator
 * @since 1.0.0
 * @updated 1.5.0 - ใช้ Custom Capabilities แทน edit_posts/manage_options
 */

namespace GovHybridTranslator\Admin\Ajax;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Integrations\Post;
use GovHybridTranslator\Integrations\Term;
use GovHybridTranslator\Integrations\Menu;
use GovHybridTranslator\Core\Capabilities;
use GovHybridTranslator\Core\TranslationMeta;

class TranslationAjax {

    /**
     * ลงทะเบียน AJAX actions ทั้งหมด
     */
    public function register() {
        add_action('wp_ajax_ght_save_page_translation', [$this, 'save_page_translation']);
        add_action('wp_ajax_ght_translate_post', [$this, 'translate_post']);
        add_action('wp_ajax_ght_translate_to_language', [$this, 'translate_to_language']);
        add_action('wp_ajax_ght_save_term_translation', [$this, 'save_term_translation']);
        add_action('wp_ajax_ght_save_menu_translation', [$this, 'save_menu_translation']);
        
        // === New Actions for Manual Management ===
        add_action('wp_ajax_ght_delete_translation', [$this, 'delete_translation']);
        add_action('wp_ajax_ght_save_full_translation', [$this, 'save_full_translation']);
    }

    /**
     * บันทึกชื่อภาษาอังกฤษสำหรับ Page/Post
     * ใช้ TranslationMeta class สำหรับ meta key ใหม่ (_ght_title_en)
     * 
     * === IMPORTANT: Preserve existing content ===
     * เมื่อ update เฉพาะ title ต้อง preserve content และ excerpt ที่มีอยู่
     */
    public function save_page_translation() {
        check_ajax_referer('ght_save_translation', 'nonce');

        // ตรวจสอบสิทธิ์ - ต้องมี ght_translate
        if (!Capabilities::can_translate()) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $page_id = intval($_POST['page_id']);
        $translation = sanitize_text_field($_POST['translation']);
        $lang = isset($_POST['lang']) ? sanitize_text_field($_POST['lang']) : 'en';

        // === Preserve existing content, excerpt AND STATUS ===
        // ดึง content, excerpt และ status ที่มีอยู่เดิมก่อนบันทึก
        $existing_content = TranslationMeta::get_content($page_id, $lang);
        $existing_excerpt = TranslationMeta::get_excerpt($page_id, $lang);
        $existing_data = TranslationMeta::get($page_id, $lang);
        $existing_status = $existing_data['status'] ?? 'published';

        // ใช้ TranslationMeta class (meta key: _ght_title_{lang})
        TranslationMeta::save(
            $page_id, 
            $lang, 
            $translation, 
            $existing_content ?? '', 
            $existing_excerpt ?? '',
            $existing_status // Preserve status
        );

        wp_send_json_success(['message' => 'Saved']);
    }

    /**
     * แปล Post ไปเป็นภาษาอังกฤษ (legacy)
     */
    public function translate_post() {
        check_ajax_referer('ght_save_translation', 'nonce');

        // ตรวจสอบสิทธิ์ - ต้องมี ght_translate
        if (!Capabilities::can_translate()) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $post_id = intval($_POST['post_id']);
        $custom_title = isset($_POST['custom_title']) ? sanitize_text_field($_POST['custom_title']) : null;

        // ใช้ Meta-based แทน Clone
        $translator = new Post();
        $result = $translator->translate_to_meta($post_id, 'en', $custom_title);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'message' => 'Translation saved!',
            'post_id' => $post_id
        ]);
    }

    /**
     * แปล Post/Page ไปยังภาษาเป้าหมายที่กำหนด
     * 
     * === Meta-based Architecture ===
     * ใช้ translate_to_meta() แทน clone_to_language()
     * - ไม่สร้าง Post ใหม่
     * - เก็บ title + content ใน post_meta
     * 
     * @since 1.8.0 - เปลี่ยนจาก Clone เป็น Meta-based
     */
    public function translate_to_language() {
        check_ajax_referer('ght_translate_to_language', 'nonce');

        // ตรวจสอบสิทธิ์ - ต้องมี ght_translate
        if (!Capabilities::can_translate()) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $post_id = intval($_POST['post_id']);
        $target_lang = sanitize_text_field($_POST['target_lang']);
        $custom_title = isset($_POST['custom_title']) ? sanitize_text_field($_POST['custom_title']) : null;

        if (empty($post_id) || empty($target_lang)) {
            wp_send_json_error(['message' => 'Missing parameters']);
        }

        // === ใช้ Meta-based แทน Clone ===
        // Translate to Draft status for review (Advanced Workflow)
        // กำหนดสถานะเริ่มต้นเป็น 'draft' เพื่อให้ Admin ตรวจสอบก่อนเผยแพร่
        $translator = new Post();
        $status = 'draft';
        $result = $translator->translate_to_meta($post_id, $target_lang, $custom_title, $status);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'message' => 'Translation drafted! Please review before publishing.',
            'post_id' => $post_id,
            'target_lang' => $target_lang,
            'status' => $status
        ]);
    }

    /**
     * บันทึกคำแปลสำหรับ Term (Category/Tag)
     */
    public function save_term_translation() {
        check_ajax_referer('ght_save_translation', 'nonce');
        
        // ตรวจสอบสิทธิ์ - ต้องมี ght_translate
        if (!Capabilities::can_translate()) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $term_id = intval($_POST['term_id']);
        $lang = sanitize_text_field($_POST['lang']);
        $translation = sanitize_text_field($_POST['translation']);

        $translator = new Term();
        $translator->save_translation($term_id, $lang, $translation);

        wp_send_json_success(['message' => 'Saved']);
    }

    /**
     * บันทึกคำแปลสำหรับ Menu Item
     */
    public function save_menu_translation() {
        check_ajax_referer('ght_save_translation', 'nonce');

        // ตรวจสอบสิทธิ์ - ต้องมี ght_translate
        if (!Capabilities::can_translate()) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $menu_item_id = intval($_POST['menu_item_id']);
        $lang = sanitize_text_field($_POST['lang']);
        $translation = sanitize_text_field($_POST['translation']);

        $translator = new Menu();
        $translator->save_translation($menu_item_id, $lang, $translation);

        wp_send_json_success(['message' => 'Saved']);
    }


    /**
     * ลบคำแปลของภาษาที่ระบุ (Delete Translation)
     * 
     * ลบ meta data ทั้งหมดที่เกี่ยวข้องกับภาษานั้นๆ
     * - _ght_title_{lang}
     * - _ght_content_{lang}
     * - _ght_excerpt_{lang}
     * 
     * @since 2.3.1
     */
    public function delete_translation() {
        check_ajax_referer('ght_save_translation', 'nonce');

        // ตรวจสอบสิทธิ์ - ต้องมี ght_translate
        if (!Capabilities::can_translate()) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $post_id = intval($_POST['post_id']);
        $lang = sanitize_text_field($_POST['lang']);

        if (!$post_id || !$lang) {
            wp_send_json_error(['message' => 'Missing parameters']);
        }

        // เรียกใช้ TranslationMeta::delete เพื่อลบข้อมูลจริง
        $result = TranslationMeta::delete($post_id, $lang);

        if ($result) {
            wp_send_json_success(['message' => 'Translation deleted successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to delete translation']);
        }
    }

    /**
     * บันทึกคำแปลครบทุกฟิลด์ (Manual Edit)
     * รองรับ Title, Content, Excerpt
     * 
     * **สำคัญ:** Content จะถูก sanitize ด้วย wp_kses_post เพื่อเก็บ HTML Tags ได้
     * 
     * @since 2.3.1
     */
    public function save_full_translation() {
        check_ajax_referer('ght_save_translation', 'nonce');

        // ตรวจสอบสิทธิ์
        if (!Capabilities::can_translate()) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $post_id = intval($_POST['post_id']);
        $lang = isset($_POST['lang']) ? sanitize_text_field($_POST['lang']) : 'en';
        
        $title = sanitize_text_field($_POST['title']);
        $excerpt = sanitize_textarea_field($_POST['excerpt']);
        
        // ใช้ wp_kses_post สำหรับ content เพื่อให้รองรับ HTML (เช่น <b>, <p>, <ul>)
        // แต่ยังป้องกัน XSS scripts
        $content = wp_kses_post($_POST['content']);

        if (!$post_id) {
            wp_send_json_error(['message' => 'Invalid Post ID']);
        }

        // รับค่า status (draft/published) หรือ default เป็น published
        // สถานะ 'draft' จะถูกซ่อนจากหน้าเว็บ (ยกเว้น Admin)
        // สถานะ 'published' จะแสดงให้ทุกคนเห็น
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'published';

        // บันทึกผ่าน TranslationMeta Class
        $result = TranslationMeta::save(
            $post_id,
            $lang,
            $title,
            $content,
            $excerpt,
            $status
        );

        if ($result) {
            wp_send_json_success(['message' => 'All translation fields saved successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to save translation']);
        }
    }
}


