<?php
/**
 * Fusion Builder Parser
 * 
 * Parser สำหรับ Fusion Builder (Avada Theme) shortcodes
 * ทำหน้าที่ extract, แปล และ rebuild content ที่มี Fusion shortcodes
 * 
 * Fusion Builder ใช้ shortcodes ซ้อนกันเก็บเนื้อหา เช่น:
 * [fusion_builder_container]
 *   [fusion_builder_row]
 *     [fusion_builder_column type="1_2"]
 *       [fusion_text]เนื้อหา[/fusion_text]
 *     [/fusion_builder_column]
 *   [/fusion_builder_row]
 * [/fusion_builder_container]
 * 
 * @package GovHybridTranslator
 * @since 2.2.0
 */
namespace GovHybridTranslator\Parsers;

// ป้องกันการเข้าถึงไฟล์โดยตรง
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Service\AIService;

class FusionParser {

    /**
     * @var AIService AI Service instance
     */
    private $ai_service;

    /**
     * @var string Target language
     */
    private $target_lang;

    /**
     * Shortcodes ที่มีเนื้อหาต้องแปล (มี opening และ closing tag)
     * 
     * ข้อความอยู่ระหว่าง [tag]...[/tag]
     */
    private static $content_shortcodes = [
        'fusion_text',           // Text Block - ใช้บ่อยที่สุด
        'fusion_title',          // Title/Heading
        'fusion_alert',          // Alert Box
        'fusion_accordion',      // Accordion
        'fusion_toggle',         // Toggle
        'fusion_tab',            // Tab content
        'fusion_modal',          // Modal content
        'fusion_testimonials',   // Testimonials
        'fusion_content_box',    // Content Box (single)
        'fusion_li_item',        // List item
        'fusion_pricing_price',  // Pricing table price  
        'fusion_pricing_cell',   // Pricing table cell
        'fusion_countdown',      // Countdown message
    ];

    /**
     * Shortcodes ที่มี attribute ต้องแปล
     * 
     * key = shortcode name
     * value = array of attribute names ที่ต้องแปล
     */
    private static $attribute_shortcodes = [
        'fusion_button' => ['title'],             // Button link text อยู่ใน content, title อยู่ใน attr
        'fusion_imageframe' => ['alt'],           // Image alt text
        'fusion_person' => ['name', 'title'],     // Person name and title
        'fusion_testimonial' => ['name', 'company'], // Testimonial author
        'fusion_content_boxes' => ['title'],      // Content boxes title
        'fusion_flip_box' => ['title_front', 'title_back'], // Flip box titles
        'fusion_modal' => ['title'],              // Modal title
        'fusion_popover' => ['title', 'content'], // Popover
        'fusion_tooltip' => ['title'],            // Tooltip
        'fusion_lightbox' => ['title'],           // Lightbox title
    ];

    /**
     * Constructor
     * 
     * @param AIService|null $ai_service Optional AI Service instance
     */
    public function __construct($ai_service = null) {
        $this->ai_service = $ai_service;
    }

    /**
     * ตรวจสอบว่า content มี Fusion Builder shortcodes หรือไม่
     * 
     * @param string $content Post content
     * @return bool True ถ้ามี Fusion shortcodes
     */
    public static function has_fusion_content($content) {
        if (empty($content)) {
            return false;
        }
        
        // ตรวจสอบ pattern ของ Fusion shortcodes
        return (
            strpos($content, '[fusion_') !== false ||
            strpos($content, '[/fusion_') !== false
        );
    }

    /**
     * ตรวจสอบว่า Post นี้ใช้ Fusion Builder หรือไม่
     * 
     * ตรวจสอบจาก:
     * 1. meta _fusion_builder_status = 'active'
     * 2. content มี fusion shortcodes
     * 
     * @param int $post_id Post ID
     * @return bool True ถ้าเป็น Fusion Builder post
     */
    public static function is_fusion_post($post_id) {
        // ตรวจสอบ meta
        $fusion_status = get_post_meta($post_id, '_fusion_builder_status', true);
        if ($fusion_status === 'active') {
            return true;
        }
        
        // ตรวจสอบ content
        $post = get_post($post_id);
        if ($post && self::has_fusion_content($post->post_content)) {
            return true;
        }
        
        return false;
    }

    /**
     * แปล Fusion Builder content
     * 
     * Static method สำหรับเรียกใช้จากภายนอก
     * 
     * @param string $content Post content with Fusion shortcodes
     * @param string $lang Target language
     * @param AIService|null $ai_service AI Service instance
     * @return string Translated content with shortcodes preserved
     */
    public static function translate($content, $lang, $ai_service = null) {
        if (empty($content)) {
            return $content;
        }
        
        $parser = new self($ai_service);
        return $parser->translate_content($content, $lang);
    }

    /**
     * แปล content (instance method)
     * 
     * @param string $content Post content
     * @param string $lang Target language
     * @return string Translated content
     */
    public function translate_content($content, $lang) {
        $this->target_lang = $lang;
        
        if (!$this->ai_service) {
            $this->ai_service = new AIService();
        }
        
        $translated = $content;
        
        // === Step 1: แปล content shortcodes (มี opening และ closing tag) ===
        foreach (self::$content_shortcodes as $tag) {
            $pattern = '/\[' . $tag . '([^\]]*)\](.*?)\[\/' . $tag . '\]/s';
            
            $translated = preg_replace_callback($pattern, function($matches) use ($tag) {
                $attributes = $matches[1];
                $inner_content = $matches[2];
                
                // แปล inner content ถ้ามีข้อความ
                if ($this->has_translatable_text($inner_content)) {
                    // ถ้ามี nested fusion shortcodes ให้ recursive
                    if (self::has_fusion_content($inner_content)) {
                        $translated_inner = $this->translate_content($inner_content, $this->target_lang);
                    } else {
                        // แปลด้วย AI
                        $translated_inner = $this->translate_html($inner_content);
                    }
                } else {
                    $translated_inner = $inner_content;
                }
                
                return '[' . $tag . $attributes . ']' . $translated_inner . '[/' . $tag . ']';
            }, $translated);
        }
        
        // === Step 2: แปล shortcode attributes ===
        foreach (self::$attribute_shortcodes as $tag => $attrs) {
            foreach ($attrs as $attr) {
                // Pattern สำหรับ attribute ใน shortcode
                // เช่น [fusion_button title="ข้อความ"] หรือ [fusion_button title='ข้อความ']
                
                // Double quotes
                $pattern = '/(\[' . $tag . '[^\]]*' . $attr . '=")([^"]+)(")/s';
                $translated = preg_replace_callback($pattern, function($matches) {
                    $before = $matches[1];
                    $value = $matches[2];
                    $after = $matches[3];
                    
                    if ($this->has_translatable_text($value)) {
                        $translated_value = $this->translate_text($value);
                        return $before . $translated_value . $after;
                    }
                    
                    return $matches[0];
                }, $translated);
                
                // Single quotes
                $pattern = "/(\[" . $tag . "[^\]]*" . $attr . "=')([^']+)(')/s";
                $translated = preg_replace_callback($pattern, function($matches) {
                    $before = $matches[1];
                    $value = $matches[2];
                    $after = $matches[3];
                    
                    if ($this->has_translatable_text($value)) {
                        $translated_value = $this->translate_text($value);
                        return $before . $translated_value . $after;
                    }
                    
                    return $matches[0];
                }, $translated);
            }
        }
        
        return $translated;
    }

    /**
     * ตรวจสอบว่ามีข้อความที่ต้องแปลหรือไม่
     * 
     * ข้าม content ที่มีแค่ whitespace, HTML tags, หรือ shortcodes
     * 
     * @param string $text Text to check
     * @return bool True ถ้ามีข้อความ
     */
    private function has_translatable_text($text) {
        if (empty($text)) {
            return false;
        }
        
        // ลบ HTML tags และ shortcodes
        $stripped = strip_tags($text);
        $stripped = preg_replace('/\[[^\]]+\]/', '', $stripped);
        $stripped = trim($stripped);
        
        return !empty($stripped);
    }

    /**
     * แปล HTML content
     * 
     * ใช้ AIService เพื่อแปล HTML โดยรักษา tags
     * 
     * @param string $html HTML content
     * @return string Translated HTML
     */
    private function translate_html($html) {
        if (empty($html) || !$this->ai_service) {
            return $html;
        }
        
        return $this->ai_service->translate_html($html, $this->target_lang);
    }

    /**
     * แปลข้อความธรรมดา (ไม่มี HTML)
     * 
     * @param string $text Plain text
     * @return string Translated text
     */
    private function translate_text($text) {
        if (empty($text) || !$this->ai_service) {
            return $text;
        }
        
        return $this->ai_service->translate_text($text, $this->target_lang);
    }

    /**
     * ดึงรายการ translatable shortcodes
     * 
     * @return array List of shortcode names
     */
    public static function get_translatable_shortcodes() {
        return array_merge(
            self::$content_shortcodes,
            array_keys(self::$attribute_shortcodes)
        );
    }

    /**
     * เพิ่ม content shortcode ที่ต้องแปล
     * 
     * @param string $tag Shortcode tag name
     */
    public static function add_content_shortcode($tag) {
        if (!in_array($tag, self::$content_shortcodes)) {
            self::$content_shortcodes[] = $tag;
        }
    }

    /**
     * เพิ่ม attribute shortcode ที่ต้องแปล
     * 
     * @param string $tag Shortcode tag name
     * @param array $attributes Array of attribute names
     */
    public static function add_attribute_shortcode($tag, $attributes) {
        if (!isset(self::$attribute_shortcodes[$tag])) {
            self::$attribute_shortcodes[$tag] = [];
        }
        self::$attribute_shortcodes[$tag] = array_merge(
            self::$attribute_shortcodes[$tag],
            $attributes
        );
    }
}
