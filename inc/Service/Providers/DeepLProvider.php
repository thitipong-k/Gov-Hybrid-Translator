<?php
/**
 * DeepL Provider
 * 
 * ใช้ DeepL API สำหรับการแปล
 * เหมาะสำหรับ: การแปลคุณภาพสูง, รองรับ EU languages ดีมาก
 * 
 * API Docs: https://www.deepl.com/docs-api
 * 
 * @package GovHybridTranslator
 * @since 1.6.0
 */
namespace GovHybridTranslator\Service\Providers;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

class DeepLProvider extends BaseProvider {

    /**
     * @var string API Endpoint (Free)
     */
    const API_ENDPOINT_FREE = 'https://api-free.deepl.com/v2/translate';

    /**
     * @var string API Endpoint (Pro)
     */
    const API_ENDPOINT_PRO = 'https://api.deepl.com/v2/translate';

    /**
     * ดึงชื่อ Provider
     * 
     * @return string
     */
    public function getName() {
        return 'DeepL Translator';
    }

    /**
     * ดึงรหัส Provider
     * 
     * @return string
     */
    public function getSlug() {
        return 'deepl';
    }

    /**
     * ดึง fields ที่ต้องกรอกใน Settings
     * 
     * @return array
     */
    public function getRequiredFields() {
        return [
            'deepl_api_key' => [
                'label' => 'DeepL API Key',
                'type' => 'password',
                'required' => true,
                'description' => 'API Key จาก DeepL (Free หรือ Pro)',
            ],
            'deepl_plan' => [
                'label' => 'Plan',
                'type' => 'select',
                'required' => false,
                'options' => [
                    'free' => 'Free (500,000 chars/month)',
                    'pro' => 'Pro (Unlimited)',
                ],
                'default' => 'free',
            ],
        ];
    }

    /**
     * ดึง API Endpoint ตาม plan
     * 
     * @return string
     */
    private function getEndpoint() {
        $plan = $this->settings->get_setting('deepl_plan', 'free');
        return $plan === 'pro' ? self::API_ENDPOINT_PRO : self::API_ENDPOINT_FREE;
    }

    /**
     * แปลงรหัสภาษาให้ตรงกับ DeepL format
     * 
     * DeepL ใช้ uppercase และบางภาษามีรหัสต่างกัน
     * 
     * @param string $lang รหัสภาษา
     * @return string รหัสภาษาสำหรับ DeepL
     */
    private function normalizeLanguageCode($lang) {
        $map = [
            'th' => 'TH', // DeepL ไม่รองรับ TH โดยตรง
            'en' => 'EN',
            'zh' => 'ZH',
            'ja' => 'JA',
            'ko' => 'KO',
            'de' => 'DE',
            'fr' => 'FR',
            'es' => 'ES',
            'pt' => 'PT-BR',
        ];
        
        return $map[strtolower($lang)] ?? strtoupper($lang);
    }

    /**
     * แปลข้อความด้วย DeepL API
     * 
     * @param string $text ข้อความที่ต้องการแปล
     * @param string $targetLang รหัสภาษาเป้าหมาย
     * @param string $sourceLang รหัสภาษาต้นฉบับ
     * @return string ข้อความที่แปลแล้ว
     */
    public function translate($text, $targetLang, $sourceLang = 'th') {
        // ตรวจสอบ API Key
        if (!$this->hasApiKey()) {
            $this->logError('API Key is missing');
            return $text;
        }

        // หมายเหตุ: DeepL ไม่รองรับภาษาไทยเป็น source
        // ควรใช้ auto-detect หรือ OpenAI สำหรับ TH
        if (strtolower($sourceLang) === 'th') {
            $this->logError('DeepL does not support Thai as source language');
            return $text;
        }

        // เตรียม request body
        $body = [
            'text' => [$text],
            'target_lang' => $this->normalizeLanguageCode($targetLang),
        ];

        // เพิ่ม source_lang ถ้าไม่ใช่ auto
        if (!empty($sourceLang) && strtolower($sourceLang) !== 'auto') {
            $body['source_lang'] = $this->normalizeLanguageCode($sourceLang);
        }

        // ส่ง request
        $response = $this->makeRequest($this->getEndpoint(), [
            'headers' => [
                'Authorization' => 'DeepL-Auth-Key ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($body),
            'timeout' => 30,
        ]);

        // ตรวจสอบ error
        if (is_wp_error($response)) {
            return $text;
        }

        // Parse response
        if (isset($response['translations'][0]['text'])) {
            return $response['translations'][0]['text'];
        }

        // Handle API error
        if (isset($response['message'])) {
            $this->logError('API Error: ' . $response['message']);
        }

        return $text;
    }

    /**
     * ทดสอบการเชื่อมต่อ
     * 
     * @return bool
     */
    public function testConnection() {
        if (!$this->hasApiKey()) {
            $this->lastError = 'API Key is missing';
            return false;
        }

        // ทดสอบแปล EN -> DE (DeepL รองรับ)
        $result = $this->translate('Hello', 'de', 'en');
        
        // "Hallo" = สำเร็จ
        return stripos($result, 'hallo') !== false || ($result !== 'Hello' && !empty($result));
    }
}
