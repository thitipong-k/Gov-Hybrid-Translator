<?php
/**
 * Settings AJAX Handlers
 * 
 * จัดการ AJAX requests สำหรับ Settings:
 * - ทดสอบ AI Connection
 * - บันทึกการกำหนดสิทธิ์ (Permissions)
 * - จัดการ API Keys (save, delete)
 * - ดูและล้าง Debug Logs
 * 
 * @package GovHybridTranslator
 * @since 1.5.0
 * @modified 1.6.0 - เพิ่ม API Key management พร้อมการเข้ารหัส
 * @modified 2.1.0 - เพิ่ม get_debug_logs และ clear_debug_logs
 */

namespace GovHybridTranslator\Admin\Ajax;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Service\AIService;
use GovHybridTranslator\Core\Capabilities;
use GovHybridTranslator\Core\APIKeyManager;

class SettingsAjax {

    /**
     * ลงทะเบียน AJAX actions
     * เรียกจาก Loader.php
     */
    public function register() {
        // ทดสอบ AI connection
        add_action('wp_ajax_ght_test_ai_connection', [$this, 'test_ai_connection']);
        
        // บันทึกการกำหนดสิทธิ์
        add_action('wp_ajax_ght_save_permissions', [$this, 'save_permissions']);

        // === API Key Management ===
        add_action('wp_ajax_ght_save_api_key', [$this, 'save_api_key']);
        add_action('wp_ajax_ght_delete_api_key', [$this, 'delete_api_key']);

        // === Auto-Translate Settings ===
        // บันทึกการตั้งค่า Auto-Translate on Publish
        add_action('wp_ajax_ght_save_auto_translate_settings', [$this, 'save_auto_translate_settings']);
        
        // === Site Identity Translation ===
        // บันทึกชื่อเว็บและ Tagline แปลสำหรับ Header
        add_action('wp_ajax_ght_save_site_identity', [$this, 'save_site_identity']);

        // === Debug Logs Management ===
        // ดูและล้าง debug logs
        add_action('wp_ajax_ght_get_debug_logs', [$this, 'get_debug_logs']);
        add_action('wp_ajax_ght_clear_debug_logs', [$this, 'clear_debug_logs']);
    }

    /**
     * ทดสอบ AI Connection
     * ตรวจสอบว่า API Key ที่ใส่สามารถเชื่อมต่อได้หรือไม่
     */
    public function test_ai_connection() {
        // ตรวจสอบ nonce
        check_ajax_referer('ght_save_settings', 'nonce');

        // ตรวจสอบสิทธิ์ - ต้องมี ght_manage_settings
        if (!Capabilities::can_manage_settings()) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        // รับค่าจาก request
        $provider = sanitize_text_field($_POST['provider'] ?? 'google');
        
        // ดึง API Key จาก field ที่เกี่ยวข้อง
        $api_key = '';
        switch ($provider) {
            case 'google':
                $api_key = sanitize_text_field($_POST['google_api_key'] ?? '');
                break;
            case 'openai':
                $api_key = sanitize_text_field($_POST['openai_api_key'] ?? '');
                break;
            case 'deepl':
                $api_key = sanitize_text_field($_POST['deepl_api_key'] ?? '');
                break;
            case 'azure':
                $api_key = sanitize_text_field($_POST['azure_api_key'] ?? '');
                break;
            case 'claude':
                $api_key = sanitize_text_field($_POST['claude_api_key'] ?? '');
                break;
            case 'simulator':
                wp_send_json_success(['message' => 'Simulator mode - no connection needed!']);
                return;
        }

        // Fallback: ใช้ api_key field เดิม
        if (empty($api_key) && !empty($_POST['api_key'])) {
            $api_key = sanitize_text_field($_POST['api_key']);
        }

        if (empty($api_key)) {
            wp_send_json_error(['message' => 'Please enter an API Key']);
            return;
        }
        
        // ทดสอบ connection
        $service = new AIService($provider, $api_key);
        $result = $service->test_connection();

        if ($result) {
            wp_send_json_success(['message' => 'Connection successful! ✅']);
        } else {
            $error = $service->get_last_error();
            $msg = 'Connection failed. ' . ($error ? $error : 'Check your API Key.');
            wp_send_json_error(['message' => $msg]);
        }
    }

    /**
     * บันทึก API Key
     * เข้ารหัสก่อนบันทึกเพื่อความปลอดภัย
     */
    public function save_api_key() {
        // ตรวจสอบ nonce
        check_ajax_referer('ght_save_settings', 'nonce');

        // ตรวจสอบสิทธิ์
        if (!Capabilities::can_manage_settings()) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        // รับค่าจาก request
        $provider = sanitize_text_field($_POST['provider'] ?? '');
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');

        if (empty($provider)) {
            wp_send_json_error(['message' => 'Provider is required']);
            return;
        }

        if (empty($api_key)) {
            wp_send_json_error(['message' => 'API Key is required']);
            return;
        }

        // รวบรวม extra data ตาม provider
        $extra = [];
        switch ($provider) {
            case 'openai':
                $extra['model'] = sanitize_text_field($_POST['openai_model'] ?? 'gpt-3.5-turbo');
                break;
            case 'deepl':
                $extra['plan'] = sanitize_text_field($_POST['deepl_plan'] ?? 'free');
                break;
            case 'azure':
                $extra['region'] = sanitize_text_field($_POST['azure_region'] ?? 'southeastasia');
                break;
            case 'claude':
                $extra['model'] = sanitize_text_field($_POST['claude_model'] ?? 'claude-3-sonnet-20240229');
                break;
        }

        // บันทึกด้วยการเข้ารหัส
        APIKeyManager::save($provider, $api_key, $extra);

        wp_send_json_success([
            'message' => 'API Key saved and encrypted successfully! 🔐',
            'provider' => $provider,
        ]);
    }

    /**
     * ลบ API Key
     */
    public function delete_api_key() {
        // ตรวจสอบ nonce
        check_ajax_referer('ght_save_settings', 'nonce');

        // ตรวจสอบสิทธิ์
        if (!Capabilities::can_manage_settings()) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        // รับค่าจาก request
        $provider = sanitize_text_field($_POST['provider'] ?? '');

        if (empty($provider)) {
            wp_send_json_error(['message' => 'Provider is required']);
            return;
        }

        // ลบ API Key
        APIKeyManager::delete($provider);

        wp_send_json_success([
            'message' => 'API Key deleted successfully!',
            'provider' => $provider,
        ]);
    }

    /**
     * บันทึกการกำหนดสิทธิ์ (Permissions)
     * รับข้อมูล permissions จาก Permissions tab ใน Settings
     */
    public function save_permissions() {
        // ตรวจสอบ nonce
        check_ajax_referer('ght_save_settings', 'nonce');

        // ตรวจสอบสิทธิ์ - ต้องเป็น Administrator เท่านั้น
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Only administrators can change permissions']);
        }

        // รับข้อมูล permissions
        $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];
        
        // Sanitize และแปลงค่า
        $sanitized = [];
        foreach ($permissions as $role => $caps) {
            $role_slug = sanitize_text_field($role);
            $sanitized[$role_slug] = [];
            
            foreach ($caps as $cap => $value) {
                $cap_name = sanitize_text_field($cap);
                // แปลง string 'true'/'false' เป็น boolean
                $sanitized[$role_slug][$cap_name] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }
        }

        // อัพเดท permissions
        $result = Capabilities::update_permissions($sanitized);

        if ($result) {
            wp_send_json_success(['message' => 'Permissions saved successfully!']);
        } else {
            wp_send_json_error(['message' => 'Failed to save permissions']);
        }
    }

    /**
     * บันทึกการตั้งค่า Auto-Translate on Publish
     * 
     * รับข้อมูลจาก Settings page และบันทึกลง database
     * ตรวจสอบ nonce และ capability ก่อนบันทึก
     * 
     * Settings ที่รับ:
     * - enabled: bool - เปิด/ปิด feature
     * - target_languages: array - ภาษาเป้าหมาย
     * - post_types: array - ประเภทเนื้อหา
     * - first_publish_only: bool - แปลเฉพาะ Publish ครั้งแรก
     * 
     * @since 1.9.0
     */
    public function save_auto_translate_settings() {
        // ตรวจสอบ nonce
        check_ajax_referer('ght_save_settings', 'nonce');

        // ตรวจสอบสิทธิ์ - ต้องเป็น Administrator
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

        // === บันทึก Site Identity Translations (ถ้ามี) ===
        // เก็บ blogname และ blogdescription แปลสำหรับแต่ละภาษา
        $supported_langs = ['en', 'zh', 'ja', 'ko', 'de', 'fr'];
        foreach ($supported_langs as $lang) {
            $blogname_key = 'ght_blogname_' . $lang;
            $blogdesc_key = 'ght_blogdescription_' . $lang;
            
            if (isset($_POST[$blogname_key])) {
                $value = sanitize_text_field($_POST[$blogname_key]);
                if (!empty($value)) {
                    update_option($blogname_key, $value);
                } else {
                    delete_option($blogname_key);
                }
            }
            
            if (isset($_POST[$blogdesc_key])) {
                $value = sanitize_text_field($_POST[$blogdesc_key]);
                if (!empty($value)) {
                    update_option($blogdesc_key, $value);
                } else {
                    delete_option($blogdesc_key);
                }
            }
        }

        // บันทึกลง database
        update_option('ght_auto_translate_settings', $settings);

        wp_send_json_success([
            'message' => 'บันทึกการตั้งค่า Auto-translate สำเร็จ!',
            'settings' => $settings,
        ]);
    }

    /**
     * บันทึก Site Identity Translations
     * 
     * รับ: ght_blogname_{lang}, ght_blogdescription_{lang}
     * เก็บใน wp_options
     * 
     * @since 2.0.0
     */
    public function save_site_identity() {
        check_ajax_referer('ght_save_settings', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $supported_langs = ['en', 'zh', 'ja', 'ko', 'de', 'fr'];
        $saved_count = 0;

        foreach ($supported_langs as $lang) {
            // ชื่อเว็บ
            $blogname_key = 'ght_blogname_' . $lang;
            if (isset($_POST[$blogname_key])) {
                $value = sanitize_text_field($_POST[$blogname_key]);
                if (!empty($value)) {
                    update_option($blogname_key, $value);
                    $saved_count++;
                } else {
                    delete_option($blogname_key);
                }
            }
            
            // Tagline
            $blogdesc_key = 'ght_blogdescription_' . $lang;
            if (isset($_POST[$blogdesc_key])) {
                $value = sanitize_text_field($_POST[$blogdesc_key]);
                if (!empty($value)) {
                    update_option($blogdesc_key, $value);
                    $saved_count++;
                } else {
                    delete_option($blogdesc_key);
                }
            }
        }

        wp_send_json_success([
            'message' => "บันทึก Site Identity สำเร็จ! ({$saved_count} รายการ)",
        ]);
    }

    /**
     * ดึง Debug Logs
     * 
     * อ่าน debug.log จาก wp-content และกรองเฉพาะ [GHT] entries
     * 
     * @since 2.0.0
     */
    public function get_debug_logs() {
        check_ajax_referer('ght_save_settings', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        // หา debug.log file
        $log_file = WP_CONTENT_DIR . '/debug.log';
        
        if (!file_exists($log_file)) {
            wp_send_json_success([
                'logs' => 'No debug.log file found.\nEnable WP_DEBUG_LOG in wp-config.php to see logs.',
                'line_count' => 0,
                'size' => '0 KB',
            ]);
            return;
        }

        // อ่านไฟล์ (จำกัด 500KB)
        $max_size = 500 * 1024;
        $file_size = filesize($log_file);
        
        if ($file_size > $max_size) {
            // อ่านแค่ส่วนท้าย
            $fp = fopen($log_file, 'r');
            fseek($fp, -$max_size, SEEK_END);
            $content = fread($fp, $max_size);
            fclose($fp);
            $content = "... (showing last 500KB) ...\n\n" . $content;
        } else {
            $content = file_get_contents($log_file);
        }

        // กรองเฉพาะบรรทัดที่มี [GHT] หรือ GovHybridTranslator
        $lines = explode("\n", $content);
        $filtered_lines = array_filter($lines, function($line) {
            return stripos($line, '[GHT]') !== false 
                || stripos($line, 'GovHybridTranslator') !== false
                || stripos($line, 'ght_') !== false;
        });

        // ถ้าไม่มี GHT logs แสดงทั้งหมด (100 บรรทัดสุดท้าย)
        if (empty($filtered_lines)) {
            $filtered_lines = array_slice($lines, -100);
            $note = "[No GHT-specific logs found. Showing last 100 lines of debug.log]\n\n";
        } else {
            $note = '';
        }

        $log_output = $note . implode("\n", $filtered_lines);
        
        // Format file size
        if ($file_size >= 1048576) {
            $size_str = round($file_size / 1048576, 2) . ' MB';
        } else {
            $size_str = round($file_size / 1024, 2) . ' KB';
        }

        wp_send_json_success([
            'logs' => trim($log_output) ?: 'No logs found.',
            'line_count' => count($filtered_lines),
            'size' => $size_str,
        ]);
    }

    /**
     * ล้าง Debug Logs
     * 
     * ลบเนื้อหาใน debug.log (ไม่ลบไฟล์)
     * 
     * @since 2.0.0
     */
    public function clear_debug_logs() {
        check_ajax_referer('ght_save_settings', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $log_file = WP_CONTENT_DIR . '/debug.log';
        
        if (!file_exists($log_file)) {
            wp_send_json_success(['message' => 'No log file to clear']);
            return;
        }

        // ล้างไฟล์ (truncate)
        $result = file_put_contents($log_file, '');
        
        if ($result !== false) {
            wp_send_json_success(['message' => 'Debug logs cleared successfully!']);
        } else {
            wp_send_json_error(['message' => 'Failed to clear logs. Check file permissions.']);
        }
    }
}

