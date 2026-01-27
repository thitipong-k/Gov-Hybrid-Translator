<?php
namespace GovHybridTranslator\Service;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Frontend\LanguageSwitcher;

class MenuTranslator {

    public function register() {
        add_filter('wp_nav_menu_objects', [$this, 'translate_menu_items'], 10, 2);
    }

    public function translate_menu_items($items, $args) {
        if (is_admin()) {
            return $items;
        }

        $lang = LanguageSwitcher::get_current_language();
        if ($lang === 'th') {
            return $items;
        }

        foreach ($items as $item) {
            $translated_title = get_post_meta($item->ID, '_gov_translator_' . $lang . '_title', true);
            if (!empty($translated_title)) {
                $item->title = $translated_title;
            }
        }

        return $items;
    }

    public function save_translation($menu_item_id, $lang, $translation) {
        update_post_meta($menu_item_id, '_gov_translator_' . $lang . '_title', sanitize_text_field($translation));
    }
}
