<?php
/**
 * Design Tabs AJAX Handler
 * 
 * จัดการ AJAX requests สำหรับ Design Tabs Translation:
 * - Get translations
 * - Save translations
 * - Auto-translate via AI
 * 
 * @package GovHybridTranslator
 * @since 2.1.1
 */
namespace GovHybridTranslator\Admin\Ajax;

// ป้องกันการเข้าถึงไฟล์โดยตรง
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Integrations\DesignTabsIntegration;
use GovHybridTranslator\Service\AIService;

class DesignTabsAjax {

    /**
     * @var DesignTabsIntegration
     */
    private $integration;

    /**
     * ลงทะเบียน AJAX actions
     */
    public function register() {
        $this->integration = new DesignTabsIntegration();

        // Get translations
        add_action('wp_ajax_ght_get_design_tab_translations', [$this, 'get_translations']);

        // Save translations
        add_action('wp_ajax_ght_save_design_tab_translations', [$this, 'save_translations']);

        // Auto-translate via AI
        add_action('wp_ajax_ght_auto_translate_design_tabs', [$this, 'auto_translate']);
    }

    /**
     * ดึง translations สำหรับ Tab Group
     */
    public function get_translations() {
        // ตรวจสอบ nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ght_design_tabs')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
        }

        // ตรวจสอบ capability
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $group_id = absint($_POST['group_id'] ?? 0);
        $lang = sanitize_text_field($_POST['lang'] ?? 'en');

        if (empty($group_id)) {
            wp_send_json_error(['message' => 'Missing group_id']);
        }

        $translations = $this->integration->get_translations($group_id, $lang);

        wp_send_json_success([
            'translations' => $translations ?: []
        ]);
    }

    /**
     * บันทึก translations สำหรับ Tab Group
     */
    public function save_translations() {
        // ตรวจสอบ nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ght_design_tabs')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
        }

        // ตรวจสอบ capability
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $group_id = absint($_POST['group_id'] ?? 0);
        $lang = sanitize_text_field($_POST['lang'] ?? 'en');
        $translations_json = $_POST['translations'] ?? '[]';

        if (empty($group_id) || empty($lang)) {
            wp_send_json_error(['message' => 'Missing required fields']);
        }

        // Decode JSON
        $translations = json_decode(stripslashes($translations_json), true);
        if (!is_array($translations)) {
            wp_send_json_error(['message' => 'Invalid translations format']);
        }

        // บันทึก
        $result = $this->integration->save_translations($group_id, $lang, $translations);

        if ($result) {
            wp_send_json_success([
                'message' => 'Translations saved successfully'
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to save translations']);
        }
    }

    /**
     * Auto-translate Tab titles และ content ด้วย AI
     */
    public function auto_translate() {
        // ตรวจสอบ nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ght_design_tabs')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
        }

        // ตรวจสอบ capability
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $group_id = absint($_POST['group_id'] ?? 0);
        $lang = sanitize_text_field($_POST['lang'] ?? 'en');
        $items_json = $_POST['items'] ?? '[]';

        if (empty($group_id) || empty($lang)) {
            wp_send_json_error(['message' => 'Missing required fields']);
        }

        // Decode JSON
        $items = json_decode(stripslashes($items_json), true);
        if (!is_array($items) || empty($items)) {
            wp_send_json_error(['message' => 'No items to translate']);
        }

        // สร้าง AI Service
        $ai_service = new AIService();

        // แปลแต่ละ item
        $translated = [];
        foreach ($items as $item) {
            $trans_item = [
                'type' => $item['type'] ?? 'tab', // เพิ่ม type เพื่อระบุว่าเป็น group_title หรือ tab
                'title' => '',
                'content' => ''
            ];

            // แปล Title
            $title = $item['title'] ?? '';
            if (!empty($title)) {
                $result = $ai_service->translate_text($title, $lang, 'th');
                $trans_item['title'] = $result ?: $title;
            }

            // แปล Content
            $content = $item['content'] ?? '';
            if (!empty($content)) {
                $result = $ai_service->translate_text($content, $lang, 'th');
                $trans_item['content'] = $result ?: $content;
            }

            $translated[] = $trans_item;
        }

        wp_send_json_success([
            'translations' => $translated
        ]);
    }
}
