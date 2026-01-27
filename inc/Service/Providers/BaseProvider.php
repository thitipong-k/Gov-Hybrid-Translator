<?php
/**
 * Base Provider Class (Abstract)
 * 
 * Abstract class รวม logic ที่ใช้ร่วมกันสำหรับทุก Provider
 * - การป้องกัน HTML tags
 * - การ restore HTML tags
 * - Helper สำหรับ HTTP requests
 * 
 * @package GovHybridTranslator
 * @since 1.6.0
 */
namespace GovHybridTranslator\Service\Providers;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Modules\Settings;

abstract class BaseProvider implements TranslationProviderInterface {

    /**
     * @var string API Key
     */
    protected $apiKey;

    /**
     * @var Settings ออบเจ็กต์สำหรับดึงค่า settings
     */
    protected $settings;

    /**
     * @var string Error message ล่าสุด
     */
    protected $lastError = '';

    /**
     * Constructor
     * 
     * @param string|null $apiKey API Key (ถ้าไม่ระบุจะดึงจาก Settings)
     */
    public function __construct($apiKey = null) {
        $this->settings = new Settings();
        
        // ดึง API Key จาก Settings ถ้าไม่ได้ระบุ
        if ($apiKey === null) {
            $slug = $this->getSlug();
            $this->apiKey = $this->settings->get_setting($slug . '_api_key', '');
        } else {
            $this->apiKey = $apiKey;
        }
    }

    /**
     * ดึง error ล่าสุด
     * 
     * @return string
     */
    public function getLastError() {
        return $this->lastError;
    }

    /**
     * ป้องกัน HTML tags และ Shortcodes ก่อนส่งไปแปล
     * 
     * แทนที่ tags ด้วย placeholders เช่น {{HTML_0}}, {{HTML_1}}
     * เพื่อไม่ให้ AI แปล/เปลี่ยน HTML
     * 
     * @param string $html HTML content
     * @return array [protected_text, tags_map]
     */
    protected function protectTags($html) {
        $map = [];
        $i = 0;

        // Regex สำหรับ HTML tags และ Shortcodes
        $pattern = '/(<[^>]+>|\[[^\]]+\])/s';

        $protected = preg_replace_callback($pattern, function($matches) use (&$map, &$i) {
            $placeholder = '{{HTML_' . $i . '}}';
            $map[$placeholder] = $matches[0];
            $i++;
            return $placeholder;
        }, $html);

        return [$protected, $map];
    }

    /**
     * คืน HTML tags จาก placeholders
     * 
     * @param string $text Text ที่มี placeholders
     * @param array $map Mapping ของ placeholders กับ tags
     * @return string HTML ที่ restore แล้ว
     */
    protected function restoreTags($text, $map) {
        return str_replace(array_keys($map), array_values($map), $text);
    }

    /**
     * ส่ง HTTP Request
     * 
     * Wrapper สำหรับ wp_remote_post/get พร้อม error handling
     * 
     * @param string $url Endpoint URL
     * @param array $args Request arguments
     * @return array|WP_Error Response body หรือ error
     */
    protected function makeRequest($url, $args = []) {
        // Default settings
        $defaults = [
            'timeout' => 30,
            'sslverify' => false, // สำหรับ localhost/XAMPP
            'headers' => [],
        ];

        $args = wp_parse_args($args, $defaults);

        // เพิ่ม Referer header เพื่อแก้ปัญหา "referer <empty> blocked" ใน Google API
        // ใช้ site_url() เพื่อระบุ referer
        if (!isset($args['headers']['Referer'])) {
            $args['headers']['Referer'] = site_url();
        }

        // ส่ง request
        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            $this->lastError = $response->get_error_message();
            return $response;
        }

        // Parse response
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->lastError = 'Invalid JSON response';
            return new \WP_Error('json_error', $this->lastError);
        }

        return $data;
    }

    /**
     * แปล HTML content (template method)
     * 
     * ขั้นตอน:
     * 1. ตรวจสอบความซับซ้อนของ HTML
     * 2. ถ้าซับซ้อนมาก ใช้ DOM parsing
     * 3. ถ้าไม่ซับซ้อน ใช้ placeholder method
     * 
     * @param string $html HTML content
     * @param string $targetLang ภาษาเป้าหมาย
     * @param string $sourceLang ภาษาต้นฉบับ
     * @return string HTML ที่แปลแล้ว
     */
    public function translateHtml($html, $targetLang, $sourceLang = 'th') {
        if (empty($html)) {
            return '';
        }

        // ตรวจสอบความซับซ้อนของ HTML
        // ถ้ามี nested divs หลายชั้นหรือมี tags มากกว่า 50 ใช้ DOM method
        $tagCount = preg_match_all('/<[^>]+>/', $html);
        $isComplex = $tagCount > 50 || preg_match('/<div[^>]*>.*<div/s', $html);

        if ($isComplex) {
            return $this->translateHtmlDom($html, $targetLang, $sourceLang);
        }

        // Simple HTML: ใช้ placeholder method
        list($protectedText, $tagsMap) = $this->protectTags($html);
        $translatedText = $this->translate($protectedText, $targetLang, $sourceLang);
        return $this->restoreTags($translatedText, $tagsMap);
    }

    /**
     * แปล HTML ด้วย DOM parsing
     * 
     * ใช้สำหรับ HTML ที่ซับซ้อน เช่น timeline, cards ที่มี nested divs
     * แปลเฉพาะ text nodes โดยรักษา HTML structure
     * 
     * @param string $html HTML content
     * @param string $targetLang ภาษาเป้าหมาย
     * @param string $sourceLang ภาษาต้นฉบับ
     * @return string HTML ที่แปลแล้ว
     */
    protected function translateHtmlDom($html, $targetLang, $sourceLang = 'th') {
        // Collect all text nodes first
        $textNodes = [];
        $placeholders = [];
        $counter = 0;

        // ใช้ regex หา text ระหว่าง tags
        $pattern = '/>((?:[^<]|<[^\/])*?)([ก-๛\p{Thai}]+(?:[^<]*[ก-๛\p{Thai}]+)*)((?:[^<])*?)</u';
        
        $html = preg_replace_callback($pattern, function($matches) use (&$textNodes, &$counter) {
            $before = $matches[1];
            $thaiText = $matches[2];
            $after = $matches[3];
            
            // ข้ามถ้าไม่มีตัวอักษรไทย
            if (empty(trim($thaiText)) || !preg_match('/[ก-๛]/u', $thaiText)) {
                return '>' . $before . $thaiText . $after . '<';
            }
            
            $placeholder = '___THAI_TEXT_' . $counter . '___';
            $textNodes[$placeholder] = $thaiText;
            $counter++;
            
            return '>' . $before . $placeholder . $after . '<';
        }, $html);

        if (empty($textNodes)) {
            return $html;
        }

        // แปลทีละ batch (จำกัด 20 items)
        $chunks = array_chunk($textNodes, 20, true);
        $translations = [];

        foreach ($chunks as $chunk) {
            foreach ($chunk as $placeholder => $text) {
                $translated = $this->translate($text, $targetLang, $sourceLang);
                $translations[$placeholder] = $translated ?: $text;
            }
        }

        // Replace placeholders with translations
        return str_replace(array_keys($translations), array_values($translations), $html);
    }

    /**
     * ตรวจสอบว่ามี API Key หรือไม่
     * 
     * @return bool
     */
    public function hasApiKey() {
        return !empty($this->apiKey);
    }

    /**
     * Log error
     * 
     * @param string $message Error message
     */
    protected function logError($message) {
        $this->lastError = $message;
    }
}
