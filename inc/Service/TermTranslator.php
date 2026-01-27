<?php
namespace GovHybridTranslator\Service;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Frontend\LanguageSwitcher;

class TermTranslator {

    public function register() {
        add_filter('get_term', [$this, 'translate_term'], 10, 2);
        add_filter('get_terms', [$this, 'translate_terms'], 10, 2);
    }

    public function translate_term($term, $taxonomy = null) {
        if (is_admin() && !wp_doing_ajax()) {
            return $term;
        }

        if (!is_object($term)) {
            return $term;
        }

        $lang = LanguageSwitcher::get_current_language();
        if ($lang === 'th') {
            return $term;
        }

        $translated_name = get_term_meta($term->term_id, '_gov_translator_' . $lang . '_name', true);
        if (!empty($translated_name)) {
            $term->name = $translated_name;
        }

        return $term;
    }

    public function translate_terms($terms, $taxonomy = null) {
        if (is_admin() && !wp_doing_ajax()) {
            return $terms;
        }

        $lang = LanguageSwitcher::get_current_language();
        if ($lang === 'th') {
            return $terms;
        }

        foreach ($terms as $term) {
            if (!is_object($term)) continue;
            $translated_name = get_term_meta($term->term_id, '_gov_translator_' . $lang . '_name', true);
            if (!empty($translated_name)) {
                $term->name = $translated_name;
            }
        }

        return $terms;
    }

    public function save_translation($term_id, $lang, $translation) {
        update_term_meta($term_id, '_gov_translator_' . $lang . '_name', sanitize_text_field($translation));
    }
}
