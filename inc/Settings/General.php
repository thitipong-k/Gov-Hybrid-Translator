<?php
namespace GovHybridTranslator\Settings;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

/**
 * General Settings Handler
 * Manages source language, target languages, and translation mode.
 */
class General {

    /**
     * Get default values for general settings.
     *
     * @return array
     */
    public function get_defaults() {
        return [
            'source_language' => 'th',
            'target_languages' => ['en'],
            'translation_mode' => 'hybrid',
        ];
    }

    /**
     * Sanitize general settings.
     *
     * @param array $settings Raw settings data.
     * @return array Sanitized settings.
     */
    public function sanitize($settings) {
        $sanitized = [];

        $sanitized['source_language'] = isset($settings['source_language']) 
            ? sanitize_text_field($settings['source_language']) 
            : 'th';

        $sanitized['target_languages'] = isset($settings['target_languages']) 
            ? array_map('sanitize_text_field', (array)$settings['target_languages']) 
            : ['en'];

        $sanitized['translation_mode'] = isset($settings['translation_mode']) 
            ? sanitize_text_field($settings['translation_mode']) 
            : 'hybrid';

        return $sanitized;
    }

    /**
     * Get setting keys managed by this class.
     *
     * @return array
     */
    public function get_keys() {
        return ['source_language', 'target_languages', 'translation_mode'];
    }
}
