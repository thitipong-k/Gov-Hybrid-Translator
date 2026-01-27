<?php
/**
 * Menu Integration Class
 * 
 * จัดการการแปลชื่อ Menu Items
 * ใช้ post_meta เก็บคำแปล (ไม่สร้าง menu item ใหม่)
 * 
 * @package GovHybridTranslator
 * @since 1.0.0
 * @modified 1.7.0 - ใช้ TranslationMeta class
 */
namespace GovHybridTranslator\Integrations;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Frontend\LanguageSwitcher;
use GovHybridTranslator\Core\TranslationMeta;

class Menu {

    public function register() {
        add_filter('wp_nav_menu_objects', [$this, 'translate_menu_items'], 10, 2);
    }

    /**
     * แปลชื่อ Menu Items
     */
    public function translate_menu_items($items, $args) {
        if (is_admin()) {
            return $items;
        }

        $lang = LanguageSwitcher::get_current_language();
        if ($lang === 'th') {
            return $items;
        }

        foreach ($items as $item) {
            // ใช้ TranslationMeta ดึงคำแปล
            $translated_title = TranslationMeta::get_title($item->ID, $lang);
            if (!empty($translated_title)) {
                $item->title = $translated_title;
            }
        }

        return $items;
    }

    /**
     * บันทึกคำแปลสำหรับ Menu Item
     * 
     * @param int $menu_item_id Menu Item ID
     * @param string $lang รหัสภาษา
     * @param string $title ชื่อแปล
     */
    public function save_translation($menu_item_id, $lang, $title) {
        TranslationMeta::save($menu_item_id, $lang, $title, '');
    }

    /**
     * ตรวจสอบว่า Menu Item มีคำแปลหรือไม่
     */
    public function has_translation($menu_item_id, $lang) {
        return TranslationMeta::has_translation($menu_item_id, $lang);
    }
}
