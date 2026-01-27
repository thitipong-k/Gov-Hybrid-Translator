<?php
/**
 * Term Integration Class
 * 
 * จัดการการแปลชื่อ Categories และ Tags
 * ใช้ term_meta เก็บคำแปล (ไม่สร้าง term ใหม่)
 * 
 * @package GovHybridTranslator
 * @since 1.0.0
 * @modified 1.7.0 - ใช้ TermTranslationMeta class
 */
namespace GovHybridTranslator\Integrations;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Frontend\LanguageSwitcher;
use GovHybridTranslator\Core\TermTranslationMeta;

class Term {

    public function register() {
        add_filter('get_term', [$this, 'translate_term'], 10, 2);
        add_filter('get_terms', [$this, 'translate_terms'], 10, 2);
    }

    /**
     * แปลชื่อ Term เดียว
     */
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

        // ใช้ TermTranslationMeta ดึงคำแปล
        $translated_name = TermTranslationMeta::get_name($term->term_id, $lang);
        if (!empty($translated_name)) {
            $term->name = $translated_name;
        }

        // แปล description ด้วย
        $translated_desc = TermTranslationMeta::get_description($term->term_id, $lang);
        if (!empty($translated_desc)) {
            $term->description = $translated_desc;
        }

        return $term;
    }

    /**
     * แปลชื่อหลาย Terms
     */
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
            
            $translated_name = TermTranslationMeta::get_name($term->term_id, $lang);
            if (!empty($translated_name)) {
                $term->name = $translated_name;
            }
        }

        return $terms;
    }

    /**
     * บันทึกคำแปลสำหรับ Term
     * 
     * @param int $term_id Term ID
     * @param string $lang รหัสภาษา
     * @param string $name ชื่อแปล
     * @param string $description คำอธิบายแปล (optional)
     */
    public function save_translation($term_id, $lang, $name, $description = '') {
        TermTranslationMeta::save($term_id, $lang, $name, $description);
    }

    /**
     * ตรวจสอบว่า Term มีคำแปลหรือไม่
     */
    public function has_translation($term_id, $lang) {
        return TermTranslationMeta::has_translation($term_id, $lang);
    }

    /**
     * ดึงรายการภาษาที่แปลแล้ว
     */
    public function get_translated_languages($term_id) {
        return TermTranslationMeta::get_languages($term_id);
    }
}
