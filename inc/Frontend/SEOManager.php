<?php
/**
 * SEO Manager Class
 * 
 * จัดการ SEO tags สำหรับเนื้อหาหลายภาษา
 * เพิ่ม hreflang tags ใน <head> อัตโนมัติ
 * 
 * hreflang tags ช่วยให้ Google เข้าใจว่า:
 * - หน้าไหนเป็นภาษาอะไร
 * - หน้าไหนเป็นเวอร์ชันแปลของกันและกัน
 * 
 * @package GovHybridTranslator
 * @since 1.5.0
 */
namespace GovHybridTranslator\Frontend;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Modules\Settings;
use GovHybridTranslator\Core\TranslationStatus;

class SEOManager {

    /**
     * @var Settings ออบเจ็กต์สำหรับดึงค่า settings
     */
    private $settings;

    /**
     * @var TranslationStatus ออบเจ็กต์สำหรับดึง translations
     */
    private $translation_status;

    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = new Settings();
        $this->translation_status = new TranslationStatus();
    }

    /**
     * ลงทะเบียน hooks
     * 
     * Hooks ที่ใช้:
     * - wp_head: เพิ่ม hreflang tags
     */
    public function register() {
        add_action('wp_head', [$this, 'render_hreflang_tags'], 1);
    }

    /**
     * แสดง hreflang tags ใน <head>
     * 
     * ขั้นตอน:
     * 1. ตรวจสอบว่าเป็น singular page หรือไม่
     * 2. ดึง translation group
     * 3. สร้าง hreflang tag สำหรับแต่ละภาษา
     * 4. เพิ่ม x-default สำหรับภาษาหลัก
     * 
     * Output ตัวอย่าง:
     * <link rel="alternate" hreflang="th" href="https://site.go.th/page/" />
     * <link rel="alternate" hreflang="en" href="https://site.go.th/en/page/" />
     * <link rel="alternate" hreflang="x-default" href="https://site.go.th/page/" />
     */
    public function render_hreflang_tags() {
        // ตรวจสอบว่าเป็น singular page (post, page, custom post type)
        if (!is_singular()) {
            return;
        }

        $post_id = get_queried_object_id();
        if (!$post_id) {
            return;
        }

        // ดึงข้อมูล translation
        $group_id = get_post_meta($post_id, '_gov_translator_group_id', true);
        $current_lang = get_post_meta($post_id, '_gov_translator_lang', true);
        $original_id = get_post_meta($post_id, '_gov_translator_original_id', true);
        $source_lang = $this->settings->get_setting('source_language', 'th');

        // ถ้าไม่มี current_lang ถือว่าเป็นภาษาต้นฉบับ
        if (empty($current_lang)) {
            $current_lang = $source_lang;
        }

        // ถ้าไม่มี group_id แสดงว่ายังไม่มี translations
        if (empty($group_id)) {
            // แสดงเฉพาะ self-referencing hreflang
            $this->output_hreflang_tag($current_lang, get_permalink($post_id));
            $this->output_hreflang_tag('x-default', get_permalink($post_id));
            return;
        }

        // === รวบรวม URLs ทุกภาษา ===
        $language_urls = [];

        // ถ้าเป็น Translation → ดึง Original ด้วย
        if (!empty($original_id)) {
            $original_post = get_post($original_id);
            if ($original_post) {
                $original_lang = get_post_meta($original_id, '_gov_translator_lang', true);
                if (empty($original_lang)) {
                    $original_lang = $source_lang;
                }
                $language_urls[$original_lang] = get_permalink($original_id);
            }
            // ใช้ original_id เพื่อดึง translations ทั้งหมด
            $translations = $this->translation_status->get_translated_posts($original_id);
        } else {
            // เป็น Original → เพิ่ม URL ของตัวเอง
            $language_urls[$current_lang] = get_permalink($post_id);
            $translations = $this->translation_status->get_translated_posts($post_id);
        }

        // เพิ่ม URLs ของ translations
        foreach ($translations as $translation) {
            $trans_lang = get_post_meta($translation->ID, '_gov_translator_lang', true);
            if (!empty($trans_lang)) {
                $language_urls[$trans_lang] = get_permalink($translation->ID);
            }
        }

        // === แสดง hreflang tags ===
        echo "\n<!-- Gov Hybrid Translator: hreflang tags -->\n";
        
        foreach ($language_urls as $lang => $url) {
            $this->output_hreflang_tag($lang, $url);
        }

        // เพิ่ม x-default (ใช้ภาษาต้นฉบับ)
        if (isset($language_urls[$source_lang])) {
            $this->output_hreflang_tag('x-default', $language_urls[$source_lang]);
        }

        echo "<!-- /Gov Hybrid Translator -->\n\n";
    }

    /**
     * แสดง hreflang tag เดี่ยว
     * 
     * @param string $lang รหัสภาษา (th, en, x-default)
     * @param string $url URL ของหน้า
     */
    private function output_hreflang_tag($lang, $url) {
        printf(
            '<link rel="alternate" hreflang="%s" href="%s" />' . "\n",
            esc_attr($lang),
            esc_url($url)
        );
    }

    /**
     * ดึง canonical URL สำหรับ SEO
     * 
     * @param int $post_id Post ID
     * @return string URL
     */
    public function get_canonical_url($post_id) {
        return get_permalink($post_id);
    }
}
