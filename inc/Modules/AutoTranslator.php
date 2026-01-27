<?php
/**
 * Auto Translator Module
 * 
 * โมดูลสำหรับแปลอัตโนมัติเมื่อ Publish post/page
 * ทำงานโดยอัตโนมัติเมื่อผู้ใช้กด Publish
 * 
 * คุณสมบัติ:
 * - เปิด/ปิดการแปลอัตโนมัติได้จากหน้า Settings
 * - เลือกภาษาเป้าหมายได้หลายภาษา (EN, ZH, JA, etc.)
 * - เลือกประเภทเนื้อหาได้ (Posts, Pages, Custom Post Types)
 * - ตัวเลือกแปลเฉพาะ Publish ครั้งแรก (ไม่แปลซ้ำเมื่อ Update)
 * - แสดง Admin Notice เมื่อแปลสำเร็จ
 * 
 * Hooks ที่ใช้:
 * - transition_post_status: ตรวจจับการเปลี่ยนสถานะ Post
 * - admin_notices: แสดง notification หลังแปลเสร็จ
 * 
 * @package GovHybridTranslator
 * @since 1.9.0
 */
namespace GovHybridTranslator\Modules;

// Prevent direct file access
if (!defined('ABSPATH')) exit;
use GovHybridTranslator\Core\TranslationMeta;

class AutoTranslator {

    /**
     * @var array Settings สำหรับ auto-translate
     * 
     * โครงสร้าง:
     * - enabled: bool - เปิด/ปิด feature
     * - target_languages: array - ภาษาเป้าหมาย เช่น ['en', 'zh']
     * - post_types: array - ประเภทเนื้อหา เช่น ['post', 'page']
     * - first_publish_only: bool - แปลเฉพาะ Publish ครั้งแรก
     */
    private $settings;

    /**
     * Constructor
     * 
     * โหลด settings จาก database หรือใช้ค่า default
     */
    public function __construct() {
        // โหลด settings จาก database
        $saved_settings = get_option('ght_auto_translate_settings', []);
        
        // รวมกับค่า default
        $this->settings = wp_parse_args($saved_settings, [
            'enabled' => false,
            'target_languages' => ['en'],
            'post_types' => ['post', 'page'],
            'first_publish_only' => true,
        ]);
    }

    /**
     * ลงทะเบียน hooks ทั้งหมด
     * 
     * เรียกใช้จาก Loader.php เมื่อ plugin โหลด
     */
    public function register() {
        // === Hook หลัก: ตรวจจับการเปลี่ยนสถานะ Post ===
        // Priority 20 เพื่อให้ทำงานหลังจาก hooks อื่นๆ
        add_action('transition_post_status', [$this, 'on_status_change'], 20, 3);
        
        // === Admin Notice: แสดงผลลัพธ์การแปล ===
        add_action('admin_notices', [$this, 'show_translation_notice']);
        
        // === AJAX: บันทึก settings ===
        add_action('wp_ajax_ght_save_auto_translate_settings', [$this, 'save_settings']);
    }

    /**
     * ตรวจจับการเปลี่ยนสถานะ Post
     * 
     * ทำงานเมื่อ Post เปลี่ยนสถานะ เช่น draft → publish
     * ตรวจสอบเงื่อนไขทั้งหมดก่อนเรียก auto_translate()
     * 
     * เงื่อนไขที่ต้องผ่าน:
     * 1. Feature ต้องเปิดใช้งาน (enabled = true)
     * 2. สถานะใหม่ต้องเป็น 'publish'
     * 3. Post type ต้องอยู่ในรายการที่รองรับ
     * 4. ถ้าเลือก first_publish_only ต้องไม่ใช่การ Update
     * 
     * @param string $new_status สถานะใหม่ของ Post
     * @param string $old_status สถานะเดิมของ Post
     * @param WP_Post $post Post object
     */
    public function on_status_change($new_status, $old_status, $post) {
        // === เงื่อนไขที่ 1: ตรวจสอบว่าเปิดใช้งานหรือไม่ ===
        if (empty($this->settings['enabled'])) {
            return;
        }

        // === เงื่อนไขที่ 2: ตรวจสอบว่าเป็นการ Publish หรือไม่ ===
        // รองรับเฉพาะ status 'publish'
        if ($new_status !== 'publish') {
            return;
        }

        // === เงื่อนไขที่ 3: ตรวจสอบว่า Post Type รองรับหรือไม่ ===
        $allowed_types = $this->settings['post_types'] ?? ['post', 'page'];
        if (!in_array($post->post_type, $allowed_types, true)) {
            return;
        }

        // === เงื่อนไขที่ 4: ตรวจสอบ First Publish Only ===
        // ถ้าเลือก "เฉพาะ Publish ครั้งแรก" และ old_status = publish
        // แสดงว่าเป็นการ Update ไม่ใช่ Publish ใหม่
        if (!empty($this->settings['first_publish_only'])) {
            if ($old_status === 'publish') {
                return;
            }
        }

        // === ผ่านทุกเงื่อนไข: เริ่มแปลอัตโนมัติ ===
        $this->auto_translate($post->ID);
    }

    /**
     * แปล Post ไปยังภาษาเป้าหมายทั้งหมด
     * 
     * วนลูปแปลไปยังทุกภาษาที่เลือกไว้ใน settings
     * ข้ามภาษาที่มี translation อยู่แล้ว
     * 
     * @param int $post_id Post ID ที่ต้องการแปล
     */
    private function auto_translate($post_id) {
        // ดึงรายการภาษาเป้าหมาย
        $target_languages = $this->settings['target_languages'] ?? ['en'];
        
        // สร้าง Translator instance
        $translator = new Post();
        
        // เก็บรายการภาษาที่แปลสำเร็จ
        $translated = [];
        $errors = [];

        // วนลูปแปลแต่ละภาษา
        foreach ($target_languages as $lang) {
            // ข้ามถ้ามี translation อยู่แล้ว
            if (TranslationMeta::has_translation($post_id, $lang)) {
                continue;
            }

            try {
                // เรียก translate_to_meta() เพื่อแปลและบันทึกลง meta
                $result = $translator->translate_to_meta($post_id, $lang);
                
                if (is_wp_error($result)) {
                    $errors[] = $lang . ': ' . $result->get_error_message();
                } else {
                    $translated[] = strtoupper($lang);
                }
            } catch (\Exception $e) {
                // จับ Exception เพื่อไม่ให้ block การ Publish
                $errors[] = $lang . ': ' . $e->getMessage();
            }
        }

        // === บันทึกผลลัพธ์ไว้แสดง Admin Notice ===
        if (!empty($translated)) {
            // ใช้ transient เก็บผลลัพธ์ชั่วคราว (60 วินาที)
            set_transient(
                'ght_auto_translated_' . get_current_user_id(), 
                $translated, 
                60
            );
        }

        // เก็บ errors ถ้ามี
        if (!empty($errors)) {
            set_transient(
                'ght_auto_translate_errors_' . get_current_user_id(),
                $errors,
                60
            );
        }
    }

    /**
     * แสดง Admin Notice หลังแปลเสร็จ
     * 
     * แสดง notification บนหน้า Admin หลังจากแปลอัตโนมัติสำเร็จ
     * ใช้ transient เพื่อส่งข้อมูลระหว่าง requests
     */
    public function show_translation_notice() {
        // ดึงผลลัพธ์จาก transient
        $translated = get_transient('ght_auto_translated_' . get_current_user_id());
        $errors = get_transient('ght_auto_translate_errors_' . get_current_user_id());
        
        // แสดง success notice
        if ($translated) {
            // ลบ transient หลังใช้งาน
            delete_transient('ght_auto_translated_' . get_current_user_id());

            printf(
                '<div class="notice notice-success is-dismissible"><p>✅ <strong>Gov Translator:</strong> แปลอัตโนมัติสำเร็จไปยัง %s</p></div>',
                esc_html(implode(', ', $translated))
            );
        }

        // แสดง error notice
        if ($errors) {
            delete_transient('ght_auto_translate_errors_' . get_current_user_id());

            printf(
                '<div class="notice notice-warning is-dismissible"><p>⚠️ <strong>Gov Translator:</strong> บางภาษาแปลไม่สำเร็จ: %s</p></div>',
                esc_html(implode('; ', $errors))
            );
        }
    }

    /**
     * บันทึก Settings ผ่าน AJAX
     * 
     * รับข้อมูลจาก Settings page และบันทึกลง database
     * ตรวจสอบ nonce และ capability ก่อนบันทึก
     */
    public function save_settings() {
        // ตรวจสอบ nonce
        check_ajax_referer('ght_save_settings', 'nonce');

        // ตรวจสอบสิทธิ์
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        // รับและ sanitize ข้อมูล
        $settings = [
            'enabled' => isset($_POST['enabled']) && $_POST['enabled'] === '1',
            'target_languages' => isset($_POST['target_languages']) 
                ? array_map('sanitize_text_field', (array)$_POST['target_languages'])
                : ['en'],
            'post_types' => isset($_POST['post_types'])
                ? array_map('sanitize_text_field', (array)$_POST['post_types'])
                : ['post', 'page'],
            'first_publish_only' => isset($_POST['first_publish_only']) && $_POST['first_publish_only'] === '1',
        ];

        // บันทึกลง database
        update_option('ght_auto_translate_settings', $settings);

        wp_send_json_success(['message' => 'บันทึกสำเร็จ']);
    }

    /**
     * ดึง Settings ปัจจุบัน
     * 
     * ใช้สำหรับแสดงใน Settings page
     * 
     * @return array Settings array
     */
    public function get_settings() {
        return $this->settings;
    }

    /**
     * ดึงรายการภาษาที่รองรับ
     * 
     * @return array รายการภาษาพร้อมชื่อ
     */
    public static function get_available_languages() {
        return [
            'en' => 'English (EN)',
            'zh' => '中文 (ZH)',
            'ja' => '日本語 (JA)',
            'ko' => '한국어 (KO)',
            'vi' => 'Tiếng Việt (VI)',
            'my' => 'မြန်မာ (MY)',
            'lo' => 'ລາວ (LO)',
            'km' => 'ខ្មែរ (KM)',
            'ms' => 'Bahasa Melayu (MS)',
            'id' => 'Bahasa Indonesia (ID)',
        ];
    }

    /**
     * ดึงรายการ Post Types ที่รองรับ
     * 
     * @return array รายการ Post Types
     */
    public static function get_available_post_types() {
        // ดึง public post types
        $post_types = get_post_types(['public' => true], 'objects');
        
        $result = [];
        foreach ($post_types as $type) {
            // ข้าม attachment
            if ($type->name === 'attachment') {
                continue;
            }
            $result[$type->name] = $type->label;
        }
        
        return $result;
    }
}
