<?php
/**
 * Frontend Visual Editor Module
 * 
 * เพิ่มปุ่ม "Edit Translation" บน Admin Bar และจัดการหน้าจอแก้ไขด้านหน้าเว็บ
 * 
 * @package GovHybridTranslator
 * @since 2.4.0
 */

namespace GovHybridTranslator\Modules;

use GovHybridTranslator\Core\TranslationMeta;
use GovHybridTranslator\Core\Capabilities;
use GovHybridTranslator\Routing\Router;

class FrontendEditor {

    /**
     * Initialize the module
     * เริ่มต้นการทำงานของ Module
     */
    public function run() {
        // Add Edit Button to Admin Bar (เพิ่มปุ่มแก้ไขบน Admin Bar)
        add_action('admin_bar_menu', [$this, 'add_admin_bar_button'], 100);

        // Enqueue Assets (Scripts & Styles) (โหลด Script และ CSS)
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Add "Edit Translation" button to Admin Bar
     * เพิ่มปุ่ม "Edit Translation" บน Admin Bar ด้านบน
     * 
     * แสดงเฉพาะเมื่อ:
     * 1. ผู้ใช้ Login และมีสิทธิ์แปลได้ (translate cap)
     * 2. หน้าปัจจุบันเป็นหน้าภาษาต่างประเทศ (เช่น /en/)
     * 3. หน้าปัจจุบันเป็น Page หรือ Post (singular)
     * 
     * @param \WP_Admin_Bar $wp_admin_bar
     */
    public function add_admin_bar_button($wp_admin_bar) {
        // check capability
        if (!current_user_can('manage_options') && !current_user_can('edit_posts')) {
            return;
        }

        // check if viewing a translated page
        $current_lang = Router::get_current_lang();
        if ($current_lang === 'th') {
            return;
        }

        // check if singular post/page
        if (!is_singular()) {
            return;
        }

        global $post;
        if (!$post) return;

        // Add Button
        $wp_admin_bar->add_node([
            'id'    => 'ght-edit-translation',
            'title' => '<span class="ab-icon dashicons dashicons-translation"></span> Edit Translation',
            'href'  => '#',
            'meta'  => [
                'class' => 'ght-trigger-editor',
                'title' => 'Edit current translation'
            ]
        ]);
    }

    /**
     * Enqueue JS/CSS for Frontend Editor
     * โหลดไฟล์ JavaScript และ CSS สำหรับ Frontend Editor
     */
    public function enqueue_assets() {
        // Load only if button condition matches
        $current_lang = Router::get_current_lang();
        if ($current_lang === 'th' || !is_singular() || (!current_user_can('manage_options') && !current_user_can('edit_posts'))) {
            return;
        }

        global $post;

        // CSS
        wp_enqueue_style(
            'ght-frontend-editor',
            GOV_HYBRID_TRANSLATOR_URL . 'assets/css/frontend-editor.css',
            [],
            GOV_HYBRID_TRANSLATOR_VERSION
        );

        // JS
        wp_enqueue_script(
            'ght-frontend-editor',
            GOV_HYBRID_TRANSLATOR_URL . 'assets/js/frontend-editor.js',
            ['jquery'],
            GOV_HYBRID_TRANSLATOR_VERSION,
            true
        );

        // Retrieve current translation data
        $translation_data = TranslationMeta::get($post->ID, $current_lang);
        
        // Prepare localized data
        wp_localize_script('ght-frontend-editor', 'ghtEditorData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('ght_save_translation'),
            'postId'  => $post->ID,
            'postId'  => $post->ID,
            'lang'    => $current_lang,
            'canApprove' => Capabilities::can_approve_translation(),
            'currentData' => [
                'title'   => $translation_data['title'] ?? '',
                'content' => $translation_data['content'] ?? '',
                'status'  => $translation_data['status'] ?? 'published'
            ],
            'originalData' => [
                'title'   => get_the_title($post->ID),
                'content' => $post->post_content // Raw content
            ],
            'i18n' => [
                'save' => __('Save Changes', 'gov-hybrid-translator'),
                'cancel' => __('Cancel', 'gov-hybrid-translator'),
                'saving' => __('Saving...', 'gov-hybrid-translator'),
                'success' => __('Saved successfully!', 'gov-hybrid-translator'),
                'error' => __('Error saving translation.', 'gov-hybrid-translator'),
                'confirm_close' => __('You have unsaved changes. Are you sure you want to close?', 'gov-hybrid-translator')
            ]
        ]);
    }
}
