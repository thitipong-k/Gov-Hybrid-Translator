<?php
/**
 * Elementor Data Parser
 * 
 * Parser สำหรับ Elementor page builder data
 * ทำหน้าที่ extract, แปล และ rebuild Elementor JSON data
 * 
 * คุณสมบัติ:
 * - Parse Elementor data จาก post_meta
 * - แปลเฉพาะ widgets ที่กำหนด
 * - รองรับ nested sections/columns
 * - รักษา styling และ layout settings
 * - บันทึกกลับเป็น post_meta
 * 
 * Widgets ที่รองรับ:
 * - heading - หัวข้อ
 * - text-editor - Text Editor (TinyMCE content)
 * - button - ปุ่ม
 * - icon-box - Icon Box (title, description)
 * - image-box - Image Box (title, description)
 * - testimonial - Testimonial (content, name, job)
 * - tabs - Tabs (tab titles, content)
 * - accordion - Accordion (titles, content)
 * - toggle - Toggle (titles, content)
 * - alert - Alert (title, description)
 * - counter - Counter (title, prefix, suffix)
 * - call-to-action - CTA (title, description, button)
 * 
 * Widgets ที่ไม่แปล:
 * - image - รูปภาพ (เฉพาะถ้าไม่มี alt/caption)
 * - video - Video embed
 * - html - Custom HTML
 * - shortcode - Shortcodes
 * - spacer, divider - Layout elements
 * 
 * @package GovHybridTranslator
 * @since 2.0.0
 */
namespace GovHybridTranslator\Parsers;

// ป้องกันการเข้าถึงไฟล์โดยตรง
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Service\AIService;

class ElementorParser {

    /**
     * @var string Elementor data meta key
     */
    const ELEMENTOR_DATA_KEY = '_elementor_data';

    /**
     * @var string Elementor edit mode meta key
     */
    const ELEMENTOR_EDIT_MODE_KEY = '_elementor_edit_mode';

    /**
     * @var array Widget types และ settings ที่ต้องแปล
     * 
     * Format: widget_type => [setting_key => type]
     * Types: 'text', 'html', 'repeater'
     */
    private static $widget_text_fields = [
        'heading' => [
            'title' => 'text',
        ],
        'text-editor' => [
            'editor' => 'html',
        ],
        'button' => [
            'text' => 'text',
        ],
        'icon-box' => [
            'title_text' => 'text',
            'description_text' => 'html',
        ],
        'image-box' => [
            'title_text' => 'text',
            'description_text' => 'html',
        ],
        'testimonial' => [
            'testimonial_content' => 'html',
            'testimonial_name' => 'text',
            'testimonial_job' => 'text',
        ],
        'tabs' => [
            'tabs' => 'repeater:tab_title,tab_content',
        ],
        'accordion' => [
            'tabs' => 'repeater:tab_title,tab_content',
        ],
        'toggle' => [
            'tabs' => 'repeater:tab_title,tab_content',
        ],
        'alert' => [
            'alert_title' => 'text',
            'alert_description' => 'html',
        ],
        'counter' => [
            'title' => 'text',
            'prefix' => 'text',
            'suffix' => 'text',
        ],
        'progress' => [
            'title' => 'text',
        ],
        'call-to-action' => [
            'title' => 'text',
            'description' => 'html',
            'button' => 'text',
        ],
        'price-list' => [
            'price_list' => 'repeater:title,item_description,price',
        ],
        'price-table' => [
            'heading' => 'text',
            'sub_heading' => 'text',
            'features_list' => 'repeater:item_text',
            'button_text' => 'text',
            'footer_additional_info' => 'text',
        ],
    ];

    /**
     * @var AIService Instance ของ AI Service
     */
    private $ai_service;

    /**
     * @var string ภาษาเป้าหมาย
     */
    private $target_lang;

    /**
     * Constructor
     * 
     * @param AIService|null $ai_service Optional AI Service instance
     */
    public function __construct($ai_service = null) {
        $this->ai_service = $ai_service;
    }

    /**
     * ตรวจสอบว่า Post ใช้ Elementor หรือไม่
     * 
     * ตรวจสอบ meta key _elementor_edit_mode
     * 
     * @param int $post_id Post ID
     * @return bool True ถ้าใช้ Elementor
     */
    public static function is_elementor_post($post_id) {
        $edit_mode = get_post_meta($post_id, self::ELEMENTOR_EDIT_MODE_KEY, true);
        return $edit_mode === 'builder';
    }

    /**
     * ดึง Elementor data จาก Post
     * 
     * @param int $post_id Post ID
     * @return array|null Elementor data array หรือ null
     */
    public static function get_elementor_data($post_id) {
        $data = get_post_meta($post_id, self::ELEMENTOR_DATA_KEY, true);
        
        if (empty($data)) {
            return null;
        }

        // ถ้าเป็น string (JSON) ให้ decode
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        return $data;
    }

    /**
     * แปล Elementor data ของ Post
     * 
     * ฟังก์ชันหลักสำหรับแปล Elementor content
     * 
     * @param int $post_id Post ID
     * @param string $lang Target language code
     * @param AIService|null $ai_service Optional AI Service
     * @return array|false Translated data หรือ false ถ้าล้มเหลว
     */
    public static function translate($post_id, $lang, $ai_service = null) {
        $parser = new self($ai_service);
        return $parser->translate_post($post_id, $lang);
    }

    /**
     * แปล Elementor data ของ Post (instance method)
     * 
     * @param int $post_id Post ID
     * @param string $lang Target language
     * @return array|false Translated data หรือ false
     */
    public function translate_post($post_id, $lang) {
        // ตรวจสอบว่าเป็น Elementor post หรือไม่
        if (!self::is_elementor_post($post_id)) {
            return false;
        }

        // ดึง Elementor data
        $data = self::get_elementor_data($post_id);
        if (empty($data) || !is_array($data)) {
            return false;
        }

        $this->target_lang = $lang;

        // สร้าง AI Service ถ้ายังไม่มี
        if (!$this->ai_service) {
            $this->ai_service = new AIService();
        }

        // แปล elements
        $translated_data = $this->translate_elements($data);

        return $translated_data;
    }

    /**
     * บันทึก translated Elementor data
     * 
     * บันทึกเป็น post_meta แยกตามภาษา
     * 
     * @param int $post_id Post ID
     * @param string $lang Language code
     * @param array $data Translated Elementor data
     * @return bool Success
     */
    public static function save_translated_data($post_id, $lang, $data) {
        $meta_key = '_ght_elementor_data_' . $lang;
        
        // Encode เป็น JSON
        $json_data = wp_json_encode($data);
        
        // ลบค่าเก่า แล้วเพิ่มใหม่
        delete_post_meta($post_id, $meta_key);
        return add_post_meta($post_id, $meta_key, $json_data, true);
    }

    /**
     * ดึง translated Elementor data
     * 
     * @param int $post_id Post ID
     * @param string $lang Language code
     * @return array|null Translated data หรือ null
     */
    public static function get_translated_data($post_id, $lang) {
        $meta_key = '_ght_elementor_data_' . $lang;
        $data = get_post_meta($post_id, $meta_key, true);
        
        if (empty($data)) {
            return null;
        }

        if (is_string($data)) {
            return json_decode($data, true);
        }

        return $data;
    }

    /**
     * แปล elements array แบบ recursive
     * 
     * Elementor structure: sections > columns > widgets
     * ต้อง traverse ลึกลงไปในทุกระดับ
     * 
     * @param array $elements Array of Elementor elements
     * @return array Translated elements
     */
    private function translate_elements($elements) {
        $result = [];

        foreach ($elements as $element) {
            // แปล element นี้
            $element = $this->translate_element($element);

            // Process nested elements แบบ recursive
            if (!empty($element['elements']) && is_array($element['elements'])) {
                $element['elements'] = $this->translate_elements($element['elements']);
            }

            $result[] = $element;
        }

        return $result;
    }

    /**
     * แปล element แต่ละตัว
     * 
     * ตรวจสอบ elType และ widgetType แล้วแปลตาม mapping
     * 
     * @param array $element Single element
     * @return array Translated element
     */
    private function translate_element($element) {
        // ตรวจสอบว่าเป็น widget หรือไม่
        if ($element['elType'] !== 'widget') {
            return $element;
        }

        // ดึง widget type
        $widget_type = $element['widgetType'] ?? '';
        
        // ตรวจสอบว่ามี mapping สำหรับ widget นี้หรือไม่
        if (!isset(self::$widget_text_fields[$widget_type])) {
            return $element;
        }

        // แปลแต่ละ setting ที่กำหนดใน mapping
        $fields = self::$widget_text_fields[$widget_type];
        
        foreach ($fields as $setting_key => $type) {
            if (!isset($element['settings'][$setting_key])) {
                continue;
            }

            $element['settings'][$setting_key] = $this->translate_setting(
                $element['settings'][$setting_key],
                $type
            );
        }

        return $element;
    }

    /**
     * แปล setting value ตาม type
     * 
     * Types:
     * - text: ข้อความธรรมดา
     * - html: HTML content
     * - repeater: Array ของ items (เช่น tabs)
     * 
     * @param mixed $value Setting value
     * @param string $type Setting type
     * @return mixed Translated value
     */
    private function translate_setting($value, $type) {
        // ถ้าเป็น repeater
        if (strpos($type, 'repeater:') === 0) {
            return $this->translate_repeater($value, $type);
        }

        // ถ้าเป็น empty หรือไม่ใช่ string
        if (empty($value) || !is_string($value)) {
            return $value;
        }

        // แปลตาม type
        switch ($type) {
            case 'text':
                return $this->translate_text($value);
            case 'html':
                return $this->translate_html($value);
            default:
                return $value;
        }
    }

    /**
     * แปล repeater setting (เช่น tabs, accordion items)
     * 
     * @param array $items Array of repeater items
     * @param string $type Repeater type (e.g., 'repeater:tab_title,tab_content')
     * @return array Translated items
     */
    private function translate_repeater($items, $type) {
        if (!is_array($items)) {
            return $items;
        }

        // Parse fields จาก type string
        // Format: repeater:field1,field2
        $fields_str = str_replace('repeater:', '', $type);
        $fields = explode(',', $fields_str);

        $result = [];

        foreach ($items as $item) {
            foreach ($fields as $field) {
                $field = trim($field);
                if (isset($item[$field]) && !empty($item[$field])) {
                    // ตรวจสอบว่าเป็น HTML หรือ text
                    if (strpos($item[$field], '<') !== false) {
                        $item[$field] = $this->translate_html($item[$field]);
                    } else {
                        $item[$field] = $this->translate_text($item[$field]);
                    }
                }
            }
            $result[] = $item;
        }

        return $result;
    }

    /**
     * แปลข้อความธรรมดา
     * 
     * @param string $text Plain text
     * @return string Translated text
     */
    private function translate_text($text) {
        if (empty($text) || !is_string($text)) {
            return $text;
        }

        try {
            $translated = $this->ai_service->translate_text($text, $this->target_lang);
            return $translated ?: $text;
        } catch (\Exception $e) {
            return $text;
        }
    }

    /**
     * แปล HTML content
     * 
     * @param string $html HTML content
     * @return string Translated HTML
     */
    private function translate_html($html) {
        if (empty($html) || !is_string($html)) {
            return $html;
        }

        try {
            $translated = $this->ai_service->translate_html($html, $this->target_lang);
            return $translated ?: $html;
        } catch (\Exception $e) {
            return $html;
        }
    }

    /**
     * ดึงรายการ widget types ที่รองรับ
     * 
     * @return array Widget types list
     */
    public static function get_supported_widgets() {
        return array_keys(self::$widget_text_fields);
    }

    /**
     * เพิ่ม widget type และ fields ที่ต้องแปล
     * 
     * สำหรับ third-party widgets หรือ Elementor Pro widgets
     * 
     * @param string $widget_type Widget type name
     * @param array $fields Fields mapping
     */
    public static function add_widget_mapping($widget_type, $fields) {
        self::$widget_text_fields[$widget_type] = $fields;
    }

    /**
     * ตรวจสอบว่า widget ได้รับการรองรับหรือไม่
     * 
     * @param string $widget_type Widget type
     * @return bool True ถ้ารองรับ
     */
    public static function is_supported_widget($widget_type) {
        return isset(self::$widget_text_fields[$widget_type]);
    }
}
