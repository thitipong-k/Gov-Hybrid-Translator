<?php
/**
 * Google Translate Provider
 * 
 * ใช้ Google Cloud Translation API v2
 * เหมาะสำหรับ: การแปลทั่วไป, รองรับหลายภาษา
 * 
 * API Docs: https://cloud.google.com/translate/docs/reference/rest/v2/translate
 * 
 * ข้อกำหนด:
 * 1. ต้อง Enable "Cloud Translation API" ใน Google Cloud Console
 * 2. ต้องมี Billing Account (แม้จะฟรี 500,000 chars/month)
 * 3. API Key ต้องไม่มี restriction หรือ restrict เฉพาะ Translation API
 * 
 * @package GovHybridTranslator
 * @since 1.6.0
 */
namespace GovHybridTranslator\Service\Providers;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

class GoogleProvider extends BaseProvider {

    /**
     * @var string API Endpoint
     */
    const API_ENDPOINT = 'https://translation.googleapis.com/language/translate/v2';

    /**
     * ดึงชื่อ Provider
     * 
     * @return string
     */
    public function getName() {
        return 'Google Translate';
    }

    /**
     * ดึงรหัส Provider
     * 
     * @return string
     */
    public function getSlug() {
        return 'google';
    }

    /**
     * ดึง fields ที่ต้องกรอกใน Settings
     * 
     * @return array
     */
    public function getRequiredFields() {
        return [
            'google_api_key' => [
                'label' => 'Google API Key',
                'type' => 'password',
                'required' => true,
                'description' => 'API Key จาก Google Cloud Console',
            ],
        ];
    }

    /**
     * แปลข้อความด้วย Google Translate API
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

        // สร้าง URL พร้อม API Key
        $url = self::API_ENDPOINT . '?key=' . $this->apiKey;

        // เตรียม request body
        $body = [
            'q' => $text,
            'source' => $sourceLang,
            'target' => $targetLang,
            'format' => 'text', // ส่งเป็น text เพราะ protect HTML แล้ว
        ];

        // ส่ง request
        $response = $this->makeRequest($url, [
            'body' => $body,
            'timeout' => 15,
        ]);

        // ตรวจสอบ error
        if (is_wp_error($response)) {
            $this->logError($response->get_error_message());
            return $text;
        }

        // Parse response
        if (isset($response['data']['translations'][0]['translatedText'])) {
            return $response['data']['translations'][0]['translatedText'];
        }

        // Handle API error
        if (isset($response['error'])) {
            $this->handleApiError($response['error']);
        }

        return $text;
    }

    /**
     * จัดการ API Error และแสดง message ที่เข้าใจง่าย
     * 
     * @param array $error Error object จาก API
     */
    private function handleApiError($error) {
        $code = $error['code'] ?? 0;
        $message = $error['message'] ?? 'Unknown error';

        switch ($code) {
            case 400:
                $this->lastError = 'Bad Request: ' . $message;
                break;
            case 403:
                // 403 = Forbidden - สาเหตุหลักๆ
                if (strpos($message, 'Forbidden') !== false || strpos($message, 'denied') !== false) {
                    $this->lastError = "API Key ไม่มีสิทธิ์ (403 Forbidden)\n\n" .
                        "สาเหตุที่เป็นไปได้:\n" .
                        "1. ยังไม่ได้ Enable 'Cloud Translation API' ใน Google Cloud Console\n" .
                        "2. ยังไม่ได้ตั้งค่า Billing Account\n" .
                        "3. API Key มี Restriction ที่ไม่รวม Translation API\n" .
                        "4. API Key ผิดหรือหมดอายุ";
                } else {
                    $this->lastError = "API Error 403: $message";
                }
                break;
            case 401:
                $this->lastError = 'API Key ไม่ถูกต้อง (Unauthorized)';
                break;
            case 429:
                $this->lastError = 'เกิน Rate Limit กรุณารอสักครู่แล้วลองใหม่';
                break;
            default:
                $this->lastError = "API Error [$code]: $message";
        }
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

        // สร้าง URL พร้อม API Key
        $url = self::API_ENDPOINT . '?key=' . $this->apiKey;

        // ทดสอบด้วยการแปลคำง่ายๆ
        $body = [
            'q' => 'Hello',
            'source' => 'en',
            'target' => 'th',
            'format' => 'text',
        ];

        $response = $this->makeRequest($url, [
            'body' => $body,
            'timeout' => 15,
        ]);

        // ตรวจสอบ WP_Error
        if (is_wp_error($response)) {
            $this->lastError = $response->get_error_message();
            return false;
        }

        // ตรวจสอบ API error
        if (isset($response['error'])) {
            $this->handleApiError($response['error']);
            return false;
        }

        // ตรวจสอบผลลัพธ์
        if (isset($response['data']['translations'][0]['translatedText'])) {
            return true;
        }

        $this->lastError = 'Unexpected response format';
        return false;
    }
}
