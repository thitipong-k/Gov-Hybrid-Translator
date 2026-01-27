<?php
/**
 * AI Service Class
 * 
 * จัดการการแปลภาษาผ่าน AI Providers ต่างๆ
 * รองรับ: Google, OpenAI, DeepL, Azure, Claude
 * 
 * @package GovHybridTranslator
 * @since 1.0.0
 * @modified 1.6.0 - ใช้ Provider pattern แทน if-else
 */
namespace GovHybridTranslator\Service;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Modules\Settings;
use GovHybridTranslator\Service\ProviderFactory;
use GovHybridTranslator\Service\Providers\TranslationProviderInterface;

class AIService {

    /**
     * @var TranslationProviderInterface Provider ที่ใช้แปล
     */
    private $provider;

    /**
     * @var Settings ออบเจ็กต์สำหรับดึงค่า settings
     */
    private $settings;

    /**
     * @var string Error message ล่าสุด
     */
    private $last_error = '';

    /**
     * Constructor
     * 
     * สร้าง Provider จาก Settings หรือระบุเอง
     * 
     * @param string|null $providerName ชื่อ provider (ถ้าไม่ระบุจะดึงจาก Settings)
     * @param string|null $apiKey API Key (ถ้าไม่ระบุจะดึงจาก Settings)
     */
    public function __construct($providerName = null, $apiKey = null) {
        $this->settings = new Settings();

        // ดึง provider name จาก settings ถ้าไม่ได้ระบุ
        if ($providerName === null) {
            $providerName = $this->settings->get_setting('ai_provider', 'google');
        }

        // สร้าง Provider
        $this->provider = ProviderFactory::create($providerName, $apiKey);

        // Fallback เป็น Google ถ้าไม่พบ Provider
        if ($this->provider === null) {
            $this->provider = ProviderFactory::create('google', $apiKey);
        }
    }

    /**
     * ดึง error ล่าสุด
     * 
     * @return string
     */
    public function get_last_error() {
        if ($this->provider) {
            return $this->provider->getLastError();
        }
        return $this->last_error;
    }

    /**
     * Alias สำหรับ get_last_error() (CamelCase)
     * 
     * @return string
     */
    public function getLastError() {
        return $this->get_last_error();
    }

    /**
     * ตรวจสอบว่า AI Service พร้อมใช้งานหรือไม่
     * 
     * ตรวจสอบ:
     * 1. มี Provider หรือไม่
     * 2. Provider มี API Key หรือไม่
     * 
     * @return bool true = พร้อมใช้งาน
     */
    public function isReady() {
        if (!$this->provider) {
            $this->last_error = 'ไม่พบ Translation Provider';
            return false;
        }

        if (!$this->provider->hasApiKey()) {
            $this->last_error = 'กรุณาตั้งค่า API Key สำหรับ ' . $this->provider->getName() . ' ใน Settings';
            return false;
        }

        return true;
    }

    /**
     * แปล HTML content โดยรักษา tags
     * 
     * @param string $html HTML content
     * @param string $target_lang ภาษาเป้าหมาย
     * @param string $source_lang ภาษาต้นฉบับ
     * @return string HTML ที่แปลแล้ว
     */
    public function translate_html($html, $target_lang = 'en', $source_lang = 'th') {
        if (empty($html)) {
            return '';
        }

        if (!$this->provider) {
            $this->last_error = 'No translation provider available';
            return $html;
        }

        // ใช้ translateHtml ของ Provider (รองรับ protect/restore tags)
        return $this->provider->translateHtml($html, $target_lang, $source_lang);
    }

    /**
     * แปลข้อความธรรมดา (ไม่มี HTML)
     * 
     * @param string $text ข้อความ
     * @param string $target_lang ภาษาเป้าหมาย
     * @param string $source_lang ภาษาต้นฉบับ
     * @return string ข้อความที่แปลแล้ว
     */
    public function translate_text($text, $target_lang = 'en', $source_lang = 'th') {
        if (empty($text)) {
            return '';
        }

        if (!$this->provider) {
            $this->last_error = 'No translation provider available';
            return $text;
        }

        return $this->provider->translate($text, $target_lang, $source_lang);
    }

    /**
     * ทดสอบการเชื่อมต่อ API
     * 
     * @return bool true = สำเร็จ
     */
    public function test_connection() {
        if (!$this->provider) {
            $this->last_error = 'No translation provider available';
            return false;
        }

        return $this->provider->testConnection();
    }

    /**
     * ดึงชื่อ Provider ที่ใช้อยู่
     * 
     * @return string
     */
    public function get_provider_name() {
        if ($this->provider) {
            return $this->provider->getName();
        }
        return 'Unknown';
    }

    /**
     * เปลี่ยน Provider
     * 
     * @param string $providerName ชื่อ provider
     * @param string|null $apiKey API Key
     */
    public function set_provider($providerName, $apiKey = null) {
        $this->provider = ProviderFactory::create($providerName, $apiKey);
    }

    /**
     * ดึงรายการ Providers ที่รองรับ
     * 
     * @return array
     */
    public static function get_available_providers() {
        return ProviderFactory::getAvailableProviders();
    }
}

