<?php
/**
 * Content Settings Handler
 * 
 * จัดการการตั้งค่าเนื้อหา: Auto-translation, URL structure, SEO
 * 
 * @package GovHybridTranslator
 * @since 1.0.0
 */
namespace GovHybridTranslator\Settings;

// ป้องกันการเข้าถึงไฟล์โดยตรง
if (!defined('ABSPATH')) exit;

class Content {

    /**
     * Get default values for content settings.
     *
     * @return array
     */
    public function get_defaults() {
        return [
            'auto_translate_types' => ['pages', 'posts'],
            'url_structure' => 'subdomain',
            'seo_hreflang' => true,
            'seo_canonical' => true,
            'seo_sitemap' => true,
            'auto_translate_slugs' => true,
        ];
    }

    /**
     * Sanitize content settings.
     *
     * @param array $settings Raw settings data.
     * @return array Sanitized settings.
     */
    public function sanitize($settings) {
        $sanitized = [];

        $sanitized['auto_translate_types'] = isset($settings['auto_translate_types']) 
            ? array_map('sanitize_text_field', (array)$settings['auto_translate_types']) 
            : ['pages', 'posts'];

        $sanitized['url_structure'] = isset($settings['url_structure']) 
            ? sanitize_text_field($settings['url_structure']) 
            : 'subdomain';

        $sanitized['seo_hreflang'] = isset($settings['seo_hreflang']) 
            ? (bool)$settings['seo_hreflang'] 
            : true;

        $sanitized['seo_canonical'] = isset($settings['seo_canonical']) 
            ? (bool)$settings['seo_canonical'] 
            : true;

        $sanitized['seo_sitemap'] = isset($settings['seo_sitemap']) 
            ? (bool)$settings['seo_sitemap'] 
            : true;

        $sanitized['auto_translate_slugs'] = isset($settings['auto_translate_slugs']) 
            ? (bool)$settings['auto_translate_slugs'] 
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
            'auto_translate_types',
            'url_structure',
            'seo_hreflang',
            'seo_canonical',
            'seo_sitemap',
            'auto_translate_slugs',
        ];
    }
}
