<?php
/**
 * Design Tabs Integration
 * 
 * จัดการการแปล Tab titles สำหรับ Design Tabs plugin
 * 
 * คุณสมบัติ:
 * - บันทึกและดึง translations ของ Tab titles
 * - Filter แสดงผล translations บน frontend
 * - ดึง Tab Groups ที่ยังแปลไม่ครบ
 * 
 * @package GovHybridTranslator
 * @since 2.1.1
 */
namespace GovHybridTranslator\Integrations;

// ป้องกันการเข้าถึงไฟล์โดยตรง
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Frontend\LanguageSwitcher;

class DesignTabsIntegration {

    /**
     * Meta key prefix สำหรับเก็บ tab translations
     * Format: _ght_dt_tabs_en => ['Tab 1 EN', 'Tab 2 EN', ...]
     */
    const META_KEY_PREFIX = '_ght_dt_tabs_';

    /**
     * Design Tabs CPT name
     */
    const CPT_NAME = 'design_tab_group';

    /**
     * ลงทะเบียน hooks
     */
    public function register() {
        // ใช้ init hook เพื่อให้มั่นใจว่า Design Tabs CPT ถูก register แล้ว
        add_action('init', [$this, 'register_filter'], 20);
    }

    /**
     * Register metadata filter - เรียกใน init hook
     */
    public function register_filter() {
        // ตรวจสอบว่า Design Tabs plugin active อยู่หรือไม่
        if (!$this->is_design_tabs_active()) {
            return;
        }

        // Filter แสดง translated tab titles บน frontend
        // ใช้ get_post_metadata (ไม่ใช่ get_post_meta) เพื่อ short-circuit return value
        add_filter('get_post_metadata', [$this, 'filter_tabs_data'], 10, 5);

        // Filter แปล Group Title (หัวข้อหลัก)
        add_filter('the_title', [$this, 'filter_group_title'], 10, 2);
    }

    /**
     * ตรวจสอบว่า Design Tabs plugin active อยู่
     * ใช้ตรวจสอบว่า constant ของ plugin ถูก define หรือไม่
     * (post_type_exists อาจยังไม่ทำงาน ณ เวลา register)
     * 
     * @return bool
     */
    private function is_design_tabs_active() {
        // ตรวจสอบว่า constant DESIGN_TABS_PATH defined (ถูก define ใน design-tabs.php)
        return defined('DESIGN_TABS_PATH');
    }

    /**
     * Filter post metadata เพื่อแทนที่ tab titles ด้วย translations
     * 
     * WordPress get_post_metadata filter:
     * - ถ้า return null: จะดึงค่าจาก database ปกติ
     * - ถ้า return non-null: จะใช้ค่านั้นแทน (short-circuit)
     * 
     * @param null|array $value Null to use database value, or array to short-circuit
     * @param int $object_id Post ID
     * @param string $meta_key Meta key
     * @param bool $single Single value or array
     * @param string $meta_type Meta type ('post' in this case)
     * @return null|array Modified value or null
     */
    public function filter_group_title($title, $id = null) {
        if (!$id || is_admin() && !wp_doing_ajax()) {
            return $title;
        }

        // ตรวจสอบว่าเป็น Design Tab Group หรือไม่
        if (get_post_type($id) !== self::CPT_NAME) {
            return $title;
        }

        // ดึงภาษาปัจจุบัน
        $lang = LanguageSwitcher::get_current_language();
        if ($lang === 'th') {
            return $title;
        }

        // ดึง translations
        $translations = $this->get_translations($id, $lang);
        if (!empty($translations) && isset($translations['_group_title']) && !empty($translations['_group_title'])) {
            return $translations['_group_title'];
        }

        return $title;
    }

    /**
     * Filter post metadata เพื่อแทนที่ tab titles ด้วย translations
     */
    public function filter_tabs_data($value, $object_id, $meta_key, $single, $meta_type = 'post') {
        // ตรวจสอบว่าเป็น Design Tabs data หรือไม่
        if ($meta_key !== '_dt_tabs_data') {
            return null; // Return null เพื่อให้ WP ดึงจาก database ปกติ
        }

        // ข้าม admin pages (ยกเว้น AJAX)
        if (is_admin() && !wp_doing_ajax()) {
            return null;
        }

        // ดึงภาษาปัจจุบัน
        $lang = LanguageSwitcher::get_current_language();
        
        // ถ้าเป็นภาษาไทย (default) ไม่ต้องแปล
        if ($lang === 'th') {
            return null;
        }

        // ดึง translations สำหรับภาษานี้
        $translations = $this->get_translations($object_id, $lang);
        if (empty($translations)) {
            return null;
        }

        // ดึง original tabs data โดยใช้ direct database query
        // เพื่อหลีกเลี่ยง infinite loop จาก get_post_meta
        global $wpdb;
        $tabs_data_raw = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s",
            $object_id,
            '_dt_tabs_data'
        ));

        if (empty($tabs_data_raw)) {
            return null;
        }

        $tabs = maybe_unserialize($tabs_data_raw);
        if (!is_array($tabs)) {
            return null;
        }

        // แทนที่ titles และ content ด้วย translations
        foreach ($tabs as $index => &$tab) {
            if (isset($translations[$index])) {
                $trans = $translations[$index];
                
                // รองรับข้อมูลทั้งรูปแบบเก่า (string) และใหม่ (array)
                if (is_array($trans)) {
                    if (isset($trans['title']) && !empty($trans['title'])) {
                        $tab['title'] = $trans['title'];
                    }
                    if (isset($trans['content']) && !empty($trans['content'])) {
                        $tab['content'] = $trans['content'];
                    }
                } else {
                    // รูปแบบเก่า: $trans คือ string title
                    if (!empty($trans)) {
                        $tab['title'] = $trans;
                    }
                }
            }
        }

        // Return ค่าที่แก้ไขแล้ว (short-circuit)
        // Format: ถ้า $single = true -> [$tabs] (จะถูก unwrap เป็น $tabs)
        //         ถ้า $single = false -> [[$tabs]] (จะถูก unwrap เป็น [$tabs])
        return [$tabs];
    }

    /**
     * บันทึก translations สำหรับ Tab Group
     * 
     * @param int $group_id Tab Group ID
     * @param string $lang รหัสภาษา
     * @param array $translations Array of translations (strings for titles or arrays for title+content)
     * @return bool
     */
    public function save_translations($group_id, $lang, $translations) {
        if (empty($group_id) || empty($lang) || !is_array($translations)) {
            return false;
        }

        $meta_key = self::META_KEY_PREFIX . sanitize_text_field($lang);
        
        // Sanitize data
        $sanitized = [];
        foreach ($translations as $index => $item) {
            if ($index === '_group_title') {
                $sanitized['_group_title'] = sanitize_text_field($item);
                continue;
            }

            if (is_array($item)) {
                $sanitized[$index] = [
                    'title' => isset($item['title']) ? sanitize_text_field($item['title']) : '',
                    'content' => isset($item['content']) ? wp_kses_post($item['content']) : ''
                ];
            } else {
                // สำหรับ backward compatibility หรือถ้าส่งมาแค่ string
                $sanitized[$index] = sanitize_text_field($item);
            }
        }

        // ลบ meta เก่าก่อน
        delete_post_meta($group_id, $meta_key);
        
        // บันทึกใหม่
        $result = add_post_meta($group_id, $meta_key, $sanitized, true);

        return $result !== false;
    }

    /**
     * ดึง translations สำหรับ Tab Group
     * 
     * @param int $group_id Tab Group ID
     * @param string $lang รหัสภาษา
     * @return array|null Array of translated titles หรือ null
     */
    public function get_translations($group_id, $lang) {
        $meta_key = self::META_KEY_PREFIX . sanitize_text_field($lang);
        $translations = get_post_meta($group_id, $meta_key, true);

        if (!empty($translations) && is_array($translations)) {
            return $translations;
        }

        return null;
    }

    /**
     * ดึงรายการภาษาที่มี translations แล้ว
     * 
     * @param int $group_id Tab Group ID
     * @return array รายการรหัสภาษา
     */
    public function get_translated_languages($group_id) {
        global $wpdb;

        $results = $wpdb->get_col($wpdb->prepare(
            "SELECT meta_key FROM {$wpdb->postmeta} 
             WHERE post_id = %d 
             AND meta_key LIKE %s",
            $group_id,
            self::META_KEY_PREFIX . '%'
        ));

        $languages = [];
        foreach ($results as $meta_key) {
            $lang = str_replace(self::META_KEY_PREFIX, '', $meta_key);
            if (!empty($lang)) {
                $languages[] = $lang;
            }
        }

        return $languages;
    }

    /**
     * ดึง Tab Groups ทั้งหมด พร้อมข้อมูล translations
     * 
     * @return array
     */
    public function get_all_tab_groups() {
        global $wpdb;
        
        $groups = get_posts([
            'post_type' => self::CPT_NAME,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        $result = [];
        
        // ดึง target languages
        $settings = new \GovHybridTranslator\Modules\Settings();
        $target_langs = $settings->get_setting('target_languages', ['en']);

        foreach ($groups as $group) {
            // ดึง tabs data โดยตรงจาก database เพื่อหลีกเลี่ยง filter interference
            $raw_tabs = $wpdb->get_var($wpdb->prepare(
                "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s",
                $group->ID,
                '_dt_tabs_data'
            ));
            
            $tabs_data = [];
            if (!empty($raw_tabs)) {
                $tabs_data = maybe_unserialize($raw_tabs);
                if (!is_array($tabs_data)) {
                    $tabs_data = [];
                }
            }

            // ดึง translated languages
            $translated_langs = $this->get_translated_languages($group->ID);
            $missing_langs = array_diff($target_langs, $translated_langs);

            // ดึง translations ที่มี
            $translations = [];
            foreach ($translated_langs as $lang) {
                $trans = $this->get_translations($group->ID, $lang);
                if ($trans) {
                    $translations[$lang] = $trans;
                }
            }

            $result[] = [
                'group' => $group,
                'tabs' => $tabs_data,
                'translated_langs' => $translated_langs,
                'translations' => $translations,
                'missing_langs' => array_values($missing_langs),
                'tab_count' => count($tabs_data)
            ];
        }

        return $result;
    }

    /**
     * ดึง Tab Groups ที่ยังแปลไม่ครบ
     * 
     * @return array
     */
    public function get_untranslated_groups() {
        $all_groups = $this->get_all_tab_groups();
        
        return array_filter($all_groups, function($item) {
            return !empty($item['missing_langs']) && !empty($item['tabs']);
        });
    }

    /**
     * ตรวจสอบว่า Tab Group มี translations ครบหรือไม่
     * 
     * @param int $group_id Tab Group ID
     * @return bool
     */
    public function has_complete_translations($group_id) {
        $settings = new \GovHybridTranslator\Modules\Settings();
        $target_langs = $settings->get_setting('target_languages', ['en']);
        $translated_langs = $this->get_translated_languages($group_id);

        return count(array_diff($target_langs, $translated_langs)) === 0;
    }

    /**
     * ดึง Tab Groups ที่แปลครบแล้ว
     * 
     * @return array
     */
    public function get_translated_groups() {
        $all_groups = $this->get_all_tab_groups();
        
        return array_filter($all_groups, function($item) {
            // มี tabs และไม่มี missing_langs
            return !empty($item['tabs']) && empty($item['missing_langs']);
        });
    }
}

