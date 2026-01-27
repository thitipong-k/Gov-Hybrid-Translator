<?php
/**
 * Admin Assets Handler
 * 
 * โหลด CSS และ JavaScript สำหรับ Admin Dashboard ของ Plugin
 * 
 * @package GovHybridTranslator
 * @since 1.0.0
 */
namespace GovHybridTranslator\Admin;

// ป้องกันการเข้าถึงไฟล์โดยตรง
if (!defined('ABSPATH')) exit;

class Assets {

    public function register() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        // Hook can be 'toplevel_page_gov-hybrid-translator' or contain 'gov-hybrid-translator'
        if (strpos($hook, 'gov-hybrid-translator') === false && strpos($hook, 'gov-translator') === false) {
            return;
        }

        $plugin_url = plugin_dir_url(\GOV_HYBRID_TRANSLATOR_FILE);
        $version = GOV_HYBRID_TRANSLATOR_VERSION; // ใช้ plugin version เพื่อ cache busting อัตโนมัติ

        // Enqueue CSS files
        wp_enqueue_style(
            'ght-admin',
            $plugin_url . 'inc/assets/css/admin.css',
            [],
            $version
        );

        wp_enqueue_style(
            'ght-tasks',
            $plugin_url . 'inc/assets/css/tasks.css',
            ['ght-admin'],
            $version
        );

        wp_enqueue_style(
            'ght-translated',
            $plugin_url . 'inc/assets/css/translated.css',
            ['ght-admin'],
            $version
        );

        wp_enqueue_style(
            'ght-overview',
            $plugin_url . 'inc/assets/css/overview.css',
            ['ght-admin'],
            $version
        );

        wp_enqueue_style(
            'ght-settings',
            $plugin_url . 'inc/assets/css/settings.css',
            ['ght-admin'],
            $version
        );

        // Enqueue JavaScript files (load in header for inline onclick to work)
        wp_enqueue_script(
            'ght-admin',
            $plugin_url . 'inc/assets/js/admin.js',
            ['jquery'],
            $version,
            false  // Load in header
        );

        wp_enqueue_script(
            'ght-tasks',
            $plugin_url . 'inc/assets/js/tasks.js',
            ['jquery', 'ght-admin'],
            $version,
            false  // Load in header
        );

        wp_enqueue_script(
            'ght-translated',
            $plugin_url . 'inc/assets/js/translated.js',
            ['jquery', 'ght-admin'],
            $version,
            false  // Load in header
        );

        wp_enqueue_script(
            'ght-settings',
            $plugin_url . 'inc/assets/js/settings.js',
            ['jquery', 'ght-admin'],
            $version,
            false  // Load in header
        );

        // Localize script with nonce and other data
        wp_localize_script('ght-admin', 'ghtData', [
            'nonce' => wp_create_nonce('ght_save_translation'),
            'settingsNonce' => wp_create_nonce('ght_save_settings'),
            'ajaxUrl' => admin_url('admin-ajax.php')
        ]);
    }
}
