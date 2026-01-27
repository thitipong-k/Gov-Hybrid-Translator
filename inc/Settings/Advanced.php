<?php
/**
 * Advanced Settings Handler
 * 
 * จัดการการตั้งค่าขั้นสูง: Cache, Access Control, Security, Logging
 * 
 * @package GovHybridTranslator
 * @since 1.0.0
 */
namespace GovHybridTranslator\Settings;

// ป้องกันการเข้าถึงไฟล์โดยตรง
if (!defined('ABSPATH')) exit;

class Advanced {

    /**
     * Get default values for advanced settings.
     *
     * @return array
     */
    public function get_defaults() {
        return [
            'enable_cache' => true,
            'cache_duration' => 24,
            'restrict_access' => 'editor',
            'api_encryption' => true,
            'audit_log' => true,
            'debug_mode' => false,
            'log_level' => 'warnings',
        ];
    }

    /**
     * Sanitize advanced settings.
     *
     * @param array $settings Raw settings data.
     * @return array Sanitized settings.
     */
    public function sanitize($settings) {
        $sanitized = [];

        $sanitized['enable_cache'] = isset($settings['enable_cache']) 
            ? (bool)$settings['enable_cache'] 
            : true;

        $sanitized['cache_duration'] = isset($settings['cache_duration']) 
            ? absint($settings['cache_duration']) 
            : 24;

        $sanitized['restrict_access'] = isset($settings['restrict_access']) 
            ? sanitize_text_field($settings['restrict_access']) 
            : 'editor';

        $sanitized['api_encryption'] = isset($settings['api_encryption']) 
            ? (bool)$settings['api_encryption'] 
            : true;

        $sanitized['audit_log'] = isset($settings['audit_log']) 
            ? (bool)$settings['audit_log'] 
            : true;

        $sanitized['debug_mode'] = isset($settings['debug_mode']) 
            ? (bool)$settings['debug_mode'] 
            : false;

        $sanitized['log_level'] = isset($settings['log_level']) 
            ? sanitize_text_field($settings['log_level']) 
            : 'warnings';

        return $sanitized;
    }

    /**
     * Get setting keys managed by this class.
     *
     * @return array
     */
    public function get_keys() {
        return [
            'enable_cache',
            'cache_duration',
            'restrict_access',
            'api_encryption',
            'audit_log',
            'debug_mode',
            'log_level',
        ];
    }
}
