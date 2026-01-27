<?php
/**
 * Gutenberg Block Parser
 * 
 * Parser สำหรับ Gutenberg blocks ใน WordPress
 * ทำหน้าที่ extract, แปล และ rebuild content ที่มี blocks
 * 
 * คุณสมบัติ:
 * - Parse blocks จาก post_content
 * - แปลเฉพาะ blocks ที่กำหนด (paragraph, heading, list, etc.)
 * - รองรับ nested blocks (columns, groups)
 * - รักษา block attributes และ className
 * - ใช้ WordPress core functions (parse_blocks, serialize_blocks)
 * 
 * Blocks ที่รองรับ:
 * - core/paragraph - ย่อหน้า
 * - core/heading - หัวข้อ
 * - core/list - รายการ
 * - core/list-item - รายการย่อย
 * - core/quote - คำพูด/อ้างอิง
 * - core/pullquote - Pull quote
 * - core/button - ปุ่ม
 * - core/image - รูปภาพ (เฉพาะ alt และ caption)
 * - core/table - ตาราง
 * 
 * Blocks ที่ไม่แปล:
 * - core/code - Code blocks
 * - core/html - Custom HTML
 * - core/embed - Embedded content
 * - core/shortcode - Shortcodes
 * 
 * @package GovHybridTranslator
 * @since 2.0.0
 */
namespace GovHybridTranslator\Parsers;

// ป้องกันการเข้าถึงไฟล์โดยตรง
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Service\AIService;

class GutenbergParser {

    /**
     * @var array รายการ block names ที่สามารถแปลได้
     * กำหนด blocks ที่มี text content ที่ต้องการแปล
     */
    private static $translatable_blocks = [
        'core/paragraph',
        'core/heading',
        'core/list',
        'core/list-item',
        'core/quote',
        'core/pullquote',
        'core/button',
        'core/table',
        'core/verse',
        'core/preformatted',
        'core/freeform', // Classic editor block
        'core/html',     // Custom HTML block - แปลเนื้อหา HTML
    ];

    /**
     * @var array Blocks ที่มี nested content และต้อง process ลึกลงไป
     */
    private static $container_blocks = [
        'core/columns',
        'core/column',
        'core/group',
        'core/cover',
        'core/media-text',
        'core/buttons',
    ];

    /**
     * @var array Blocks ที่ต้องแปล attributes แทน innerHTML
     * Mapping: blockName => [attribute keys]
     */
    private static $attribute_blocks = [
        'core/image' => ['alt', 'caption'],
        'core/button' => ['text', 'url'], // url อาจไม่ต้องแปล
        'core/heading' => ['content'],
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
     * ตรวจสอบว่า content มี Gutenberg blocks หรือไม่
     * 
     * ใช้ WordPress function has_blocks() เป็นหลัก
     * 
     * @param string $content Post content
     * @return bool True ถ้ามี blocks
     */
    public static function has_blocks($content) {
        return function_exists('has_blocks') && has_blocks($content);
    }

    /**
     * แปล Gutenberg blocks ใน content
     * 
     * ฟังก์ชันหลักสำหรับแปล content ที่มี blocks
     * ใช้ parse_blocks() และ serialize_blocks() ของ WordPress
     * 
     * @param string $content Post content with blocks
     * @param string $lang Target language code (e.g., 'en', 'zh')
     * @param AIService|null $ai_service Optional AI Service
     * @return string Translated content with blocks preserved
     */
    public static function translate($content, $lang, $ai_service = null) {
        // สร้าง instance และเรียก method
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
        // ตรวจสอบว่ามี blocks หรือไม่
        if (!self::has_blocks($content)) {
            return $content;
        }

        $this->target_lang = $lang;

        // สร้าง AI Service ถ้ายังไม่มี
        if (!$this->ai_service) {
            $this->ai_service = new AIService();
        }

        // Parse blocks จาก content
        $blocks = parse_blocks($content);

        // แปลแต่ละ block
        $translated_blocks = $this->translate_blocks($blocks);

        // Serialize กลับเป็น content
        return serialize_blocks($translated_blocks);
    }

    /**
     * แปล array ของ blocks แบบ recursive
     * 
     * วนลูปผ่านทุก block และแปลเนื้อหา
     * รองรับ nested blocks (innerBlocks)
     * 
     * @param array $blocks Array of parsed blocks
     * @return array Translated blocks
     */
    private function translate_blocks($blocks) {
        $result = [];

        foreach ($blocks as $block) {
            // ข้าม empty blocks
            if (empty($block['blockName'])) {
                $result[] = $block;
                continue;
            }

            // แปล block ถ้าเป็นประเภทที่รองรับ
            if ($this->is_translatable($block['blockName'])) {
                $block = $this->translate_block($block);
            }

            // Process nested blocks (innerBlocks) แบบ recursive
            if (!empty($block['innerBlocks'])) {
                $block['innerBlocks'] = $this->translate_blocks($block['innerBlocks']);
                // Rebuild innerContent สำหรับ container blocks
                $block = $this->rebuild_inner_content($block);
            }

            $result[] = $block;
        }

        return $result;
    }

    /**
     * ตรวจสอบว่า block สามารถแปลได้หรือไม่
     * 
     * @param string $block_name Block name (e.g., 'core/paragraph')
     * @return bool True ถ้าแปลได้
     */
    private function is_translatable($block_name) {
        return in_array($block_name, self::$translatable_blocks, true) 
            || in_array($block_name, self::$container_blocks, true);
    }

    /**
     * แปลเนื้อหาของ block แต่ละตัว
     * 
     * ตรวจสอบประเภท block และแปลเนื้อหาที่เหมาะสม
     * - blocks ปกติ: แปล innerHTML
     * - attribute blocks: แปล specific attributes
     * 
     * @param array $block Single block array
     * @return array Translated block
     */
    private function translate_block($block) {
        $block_name = $block['blockName'];

        // === แปล attributes สำหรับ blocks พิเศษ ===
        if (isset(self::$attribute_blocks[$block_name])) {
            $block = $this->translate_block_attributes($block, self::$attribute_blocks[$block_name]);
        }

        // === แปล innerHTML สำหรับ blocks ที่มีเนื้อหา ===
        if (!empty($block['innerHTML']) && $this->has_translatable_text($block['innerHTML'])) {
            $block['innerHTML'] = $this->translate_html($block['innerHTML']);
            
            // อัพเดท innerContent ด้วย
            if (!empty($block['innerContent'])) {
                $block['innerContent'] = array_map(function($content) {
                    if (is_string($content) && !empty($content)) {
                        return $this->translate_html($content);
                    }
                    return $content;
                }, $block['innerContent']);
            }
        }

        return $block;
    }

    /**
     * แปล block attributes ที่กำหนด
     * 
     * @param array $block Block array
     * @param array $attr_keys Array of attribute keys to translate
     * @return array Block with translated attributes
     */
    private function translate_block_attributes($block, $attr_keys) {
        if (empty($block['attrs'])) {
            return $block;
        }

        foreach ($attr_keys as $key) {
            if (!empty($block['attrs'][$key]) && is_string($block['attrs'][$key])) {
                $original = $block['attrs'][$key];
                
                // ข้าม URL
                if ($key === 'url' || filter_var($original, FILTER_VALIDATE_URL)) {
                    continue;
                }

                $block['attrs'][$key] = $this->translate_text($original);
            }
        }

        return $block;
    }

    /**
     * Rebuild innerContent สำหรับ container blocks
     * 
     * หลังจากแปล innerBlocks แล้ว ต้อง rebuild innerContent
     * เพื่อให้ serialize_blocks() ทำงานถูกต้อง
     * 
     * @param array $block Block with innerBlocks
     * @return array Block with rebuilt innerContent
     */
    private function rebuild_inner_content($block) {
        if (empty($block['innerBlocks'])) {
            return $block;
        }

        // สร้าง innerContent ใหม่
        $inner_content = [];
        
        foreach ($block['innerContent'] as $content) {
            if ($content === null) {
                // null = placeholder สำหรับ innerBlock
                $inner_content[] = null;
            } else {
                $inner_content[] = $content;
            }
        }

        $block['innerContent'] = $inner_content;
        return $block;
    }

    /**
     * ตรวจสอบว่า HTML มีข้อความที่ต้องแปลหรือไม่
     * 
     * ข้าม HTML ที่มีแค่ whitespace หรือ tags
     * 
     * @param string $html HTML content
     * @return bool True ถ้ามีข้อความ
     */
    private function has_translatable_text($html) {
        // ลบ tags และ whitespace
        $text = strip_tags($html);
        $text = trim($text);

        return !empty($text);
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
        try {
            $translated = $this->ai_service->translate_html($html, $this->target_lang);
            return $translated ?: $html;
        } catch (\Exception $e) {
            return $html;
        }
    }

    /**
     * แปลข้อความธรรมดา (ไม่มี HTML)
     * 
     * @param string $text Plain text
     * @return string Translated text
     */
    private function translate_text($text) {
        try {
            $translated = $this->ai_service->translate_text($text, $this->target_lang);
            return $translated ?: $text;
        } catch (\Exception $e) {
            return $text;
        }
    }

    /**
     * ดึงรายการ translatable blocks
     * 
     * ใช้สำหรับ debug หรือ admin UI
     * 
     * @return array List of translatable block names
     */
    public static function get_translatable_blocks() {
        return self::$translatable_blocks;
    }

    /**
     * เพิ่ม block ที่สามารถแปลได้
     * 
     * สำหรับ third-party blocks หรือ custom blocks
     * 
     * @param string $block_name Block name (e.g., 'my-plugin/custom-block')
     */
    public static function add_translatable_block($block_name) {
        if (!in_array($block_name, self::$translatable_blocks, true)) {
            self::$translatable_blocks[] = $block_name;
        }
    }

    /**
     * เพิ่ม attribute mapping สำหรับ block
     * 
     * @param string $block_name Block name
     * @param array $attributes Array of attribute keys to translate
     */
    public static function add_attribute_mapping($block_name, $attributes) {
        self::$attribute_blocks[$block_name] = $attributes;
    }
}
