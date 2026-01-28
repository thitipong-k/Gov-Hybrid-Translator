<?php
namespace GovHybridTranslator\Modules;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Settings\General;
use GovHybridTranslator\Settings\AI;
use GovHybridTranslator\Settings\Content;
use GovHybridTranslator\Settings\LanguageSwitcher;
use GovHybridTranslator\Settings\Advanced;
use GovHybridTranslator\Modules\ActivityLogger;

/**
 * Settings Module
 * Aggregates and manages all plugin settings using specialized setting classes.
 */
class Settings {

    private $option_name = 'gov_hybrid_translator_settings';
    private $general;
    private $ai;
    private $content;
    private $language_switcher;
    private $advanced;

    public function __construct() {
        $this->general = new General();
        $this->ai = new AI();
        $this->content = new Content();
        $this->language_switcher = new LanguageSwitcher();
        $this->advanced = new Advanced();
    }

    public function register() {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_ajax_ght_save_settings', [$this, 'save_settings']);
    }

    public function register_settings() {
        register_setting('gov_hybrid_translator', $this->option_name);
    }

    public function save_settings() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ght_save_settings')) {
            wp_send_json_error(['message' => 'Invalid security token']);
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }

        // Get posted data
        // Support both JSON 'settings' param and direct POST values (from serialize())
        if (isset($_POST['settings'])) {
            $settings_json = $_POST['settings'];
            $settings = json_decode(stripslashes($settings_json), true);
        } else {
            // Direct POST (exclude WP system fields)
            $settings = $_POST;
            unset($settings['action']);
            unset($settings['nonce']);
            unset($settings['_wp_http_referer']);
        }

        // Sanitize settings using specialized classes
        $sanitized_settings = $this->sanitize_settings($settings);

        // Save to database
        update_option($this->option_name, $sanitized_settings);

        // === บันทึก Site Identity Translations ===
        // เก็บ blogname และ blogdescription แปลใน options แยกต่างหาก (ไม่รวมกับ settings หลัก)
        $supported_langs = ['en', 'zh', 'ja', 'ko', 'de', 'fr'];
        $site_identity_count = 0;
        
        foreach ($supported_langs as $lang) {
            // บันทึกชื่อเว็บ (blogname)
            $blogname_key = 'ght_blogname_' . $lang;
            if (isset($settings[$blogname_key])) {
                $value = sanitize_text_field($settings[$blogname_key]);
                if (!empty($value)) {
                    update_option($blogname_key, $value);
                    $site_identity_count++;
                } else {
                    delete_option($blogname_key);
                }
            }
            
            // บันทึก Tagline (blogdescription)
            $blogdesc_key = 'ght_blogdescription_' . $lang;
            if (isset($settings[$blogdesc_key])) {
                $value = sanitize_text_field($settings[$blogdesc_key]);
                if (!empty($value)) {
                    update_option($blogdesc_key, $value);
                    $site_identity_count++;
                } else {
                    delete_option($blogdesc_key);
                }
            }
        }

        // === บันทึก Permissions (สิทธิ์ผู้ใช้) ===
        // ใช้ Capabilities class เพื่อ grant/revoke สิทธิ์ให้ roles
        $permissions_count = 0;
        if (isset($settings['permissions']) && is_array($settings['permissions'])) {
            foreach ($settings['permissions'] as $role_slug => $caps) {
                if (!is_array($caps)) continue;
                
                $role = get_role($role_slug);
                if (!$role) continue;
                
                foreach ($caps as $cap => $granted) {
                    // Sanitize cap name
                    $cap = sanitize_text_field($cap);
                    
                    // ตรวจสอบว่าเป็น cap ของ plugin หรือไม่
                    if (strpos($cap, 'ght_') !== 0) continue;
                    
                    // ป้องกัน admin ไม่ให้ถูกถอดสิทธิ์สำคัญ
                    if ($role_slug === 'administrator' && 
                        in_array($cap, ['ght_manage_settings', 'ght_view_dashboard'])) {
                        $role->add_cap($cap, true);
                        continue;
                    }
                    
                    // Grant หรือ revoke สิทธิ์
                    if ($granted === true || $granted === 'true' || $granted === 1 || $granted === '1') {
                        $role->add_cap($cap, true);
                        $permissions_count++;
                    } else {
                        $role->remove_cap($cap);
                    }
                }
            }
        }

        wp_send_json_success([
            'message' => 'Settings saved successfully',
            'settings' => $sanitized_settings,
            'site_identity_count' => $site_identity_count,
            'permissions_count' => $permissions_count,
        ]);

        // Log activity
        (new ActivityLogger())->log('settings_updated', 'settings', '', [
            'count' => count($sanitized_settings),
            'site_identity' => $site_identity_count > 0,
            'permissions' => $permissions_count
        ]);
    }

    /**
     * Sanitize settings by delegating to specialized classes.
     *
     * @param array $settings Raw settings data.
     * @return array Sanitized settings.
     */
    private function sanitize_settings($settings) {
        $sanitized = [];

        // Delegate sanitization to each settings class
        $sanitized = array_merge($sanitized, $this->general->sanitize($settings));
        $sanitized = array_merge($sanitized, $this->ai->sanitize($settings));
        $sanitized = array_merge($sanitized, $this->content->sanitize($settings));
        $sanitized = array_merge($sanitized, $this->language_switcher->sanitize($settings));
        $sanitized = array_merge($sanitized, $this->advanced->sanitize($settings));

        return $sanitized;
    }

    /**
     * Get all settings with defaults.
     *
     * @return array
     */
    public function get_settings() {
        $defaults = [];

        // Aggregate defaults from each settings class
        $defaults = array_merge($defaults, $this->general->get_defaults());
        $defaults = array_merge($defaults, $this->ai->get_defaults());
        $defaults = array_merge($defaults, $this->content->get_defaults());
        $defaults = array_merge($defaults, $this->language_switcher->get_defaults());
        $defaults = array_merge($defaults, $this->advanced->get_defaults());

        $saved = get_option($this->option_name, []);
        return wp_parse_args($saved, $defaults);
    }

    /**
     * Get a specific setting value.
     *
     * @param string $key Setting key.
     * @param mixed $default Default value if not found.
     * @return mixed
     */
    public function get_setting($key, $default = null) {
        $settings = $this->get_settings();
        return isset($settings[$key]) ? $settings[$key] : $default;
    }
}
