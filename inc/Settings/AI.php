<?php
/**
 * AI Settings Handler
 * 
 * จัดการการตั้งค่า AI Provider และ API Keys
 * รองรับ: Google, OpenAI, DeepL, Azure, Claude
 * 
 * @package GovHybridTranslator
 * @since 1.0.0
 * @updated 1.6.0 - เพิ่ม API Keys แยกตาม provider
 */
namespace GovHybridTranslator\Settings;

// ป้องกันการเข้าถึงไฟล์โดยตรง
if (!defined('ABSPATH')) exit;

class AI {

    /**
     * Get default values for AI settings.
     *
     * @return array
     */
    public function get_defaults() {
        return [
            'ai_provider' => 'google',
            'api_key' => '',
            'api_secret' => '',
            // API Keys แยกตาม provider
            'google_api_key' => '',
            'openai_api_key' => '',
            'openai_model' => 'gpt-3.5-turbo',
            'deepl_api_key' => '',
            'deepl_plan' => 'free',
            'azure_api_key' => '',
            'azure_region' => 'southeastasia',
            'claude_api_key' => '',
            'claude_model' => 'claude-3-sonnet-20240229',
            // Quality settings
            'quality_level' => 'high',
            'auto_detect' => true,
            'preserve_html' => true,
            'preserve_shortcodes' => true,
            'credit_limit' => 50000,
            'alert_threshold' => 80,
            'auto_pause' => true,
        ];
    }

    /**
     * Sanitize AI settings.
     *
     * @param array $settings Raw settings data.
     * @return array Sanitized settings.
     */
    public function sanitize($settings) {
        $sanitized = [];

        $sanitized['ai_provider'] = isset($settings['ai_provider']) 
            ? sanitize_text_field($settings['ai_provider']) 
            : 'google';

        $sanitized['api_key'] = isset($settings['api_key']) 
            ? sanitize_text_field($settings['api_key']) 
            : '';

        $sanitized['api_secret'] = isset($settings['api_secret']) 
            ? sanitize_text_field($settings['api_secret']) 
            : '';

        // === API Keys แยกตาม provider ===
        $sanitized['google_api_key'] = isset($settings['google_api_key']) 
            ? sanitize_text_field($settings['google_api_key']) 
            : '';

        $sanitized['openai_api_key'] = isset($settings['openai_api_key']) 
            ? sanitize_text_field($settings['openai_api_key']) 
            : '';

        $sanitized['openai_model'] = isset($settings['openai_model']) 
            ? sanitize_text_field($settings['openai_model']) 
            : 'gpt-3.5-turbo';

        $sanitized['deepl_api_key'] = isset($settings['deepl_api_key']) 
            ? sanitize_text_field($settings['deepl_api_key']) 
            : '';

        $sanitized['deepl_plan'] = isset($settings['deepl_plan']) 
            ? sanitize_text_field($settings['deepl_plan']) 
            : 'free';

        $sanitized['azure_api_key'] = isset($settings['azure_api_key']) 
            ? sanitize_text_field($settings['azure_api_key']) 
            : '';

        $sanitized['azure_region'] = isset($settings['azure_region']) 
            ? sanitize_text_field($settings['azure_region']) 
            : 'southeastasia';

        $sanitized['claude_api_key'] = isset($settings['claude_api_key']) 
            ? sanitize_text_field($settings['claude_api_key']) 
            : '';

        $sanitized['claude_model'] = isset($settings['claude_model']) 
            ? sanitize_text_field($settings['claude_model']) 
            : 'claude-3-sonnet-20240229';

        // === Quality settings ===
        $sanitized['quality_level'] = isset($settings['quality_level']) 
            ? sanitize_text_field($settings['quality_level']) 
            : 'high';

        $sanitized['auto_detect'] = isset($settings['auto_detect']) 
            ? (bool)$settings['auto_detect'] 
            : true;

        $sanitized['preserve_html'] = isset($settings['preserve_html']) 
            ? (bool)$settings['preserve_html'] 
            : true;

        $sanitized['preserve_shortcodes'] = isset($settings['preserve_shortcodes']) 
            ? (bool)$settings['preserve_shortcodes'] 
            : true;

        $sanitized['credit_limit'] = isset($settings['credit_limit']) 
            ? absint($settings['credit_limit']) 
            : 50000;

        $sanitized['alert_threshold'] = isset($settings['alert_threshold']) 
            ? absint($settings['alert_threshold']) 
            : 80;

        $sanitized['auto_pause'] = isset($settings['auto_pause']) 
            ? (bool)$settings['auto_pause'] 
            : true;

        return $sanitized;
    }

    /**
     * Get setting keys managed by this class.
     *
     * @return array
     */
    public function get_keys() {
        return [
            'ai_provider',
            'api_key',
            'api_secret',
            // API Keys แยกตาม provider
            'google_api_key',
            'openai_api_key',
            'openai_model',
            'deepl_api_key',
            'deepl_plan',
            'azure_api_key',
            'azure_region',
            'claude_api_key',
            'claude_model',
            // Quality settings
            'quality_level',
            'auto_detect',
            'preserve_html',
            'preserve_shortcodes',
            'credit_limit',
            'alert_threshold',
            'auto_pause',
        ];
    }
}
