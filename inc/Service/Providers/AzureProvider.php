<?php
/**
 * Azure Translator Provider
 * 
 * ใช้ Azure Cognitive Services Translator API
 * เหมาะสำหรับ: องค์กรที่ใช้ Microsoft Cloud, รองรับหลายภาษามาก
 * 
 * API Docs: https://learn.microsoft.com/en-us/azure/ai-services/translator/
 * 
 * @package GovHybridTranslator
 * @since 1.6.0
 */
namespace GovHybridTranslator\Service\Providers;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

class AzureProvider extends BaseProvider {

    /**
     * @var string API Endpoint
     */
    const API_ENDPOINT = 'https://api.cognitive.microsofttranslator.com/translate';

    /**
     * @var string API Version
     */
    const API_VERSION = '3.0';

    /**
     * ดึงชื่อ Provider
     * 
     * @return string
     */
    public function getName() {
        return 'Azure Translator';
    }

    /**
     * ดึงรหัส Provider
     * 
     * @return string
     */
    public function getSlug() {
        return 'azure';
    }

    /**
     * ดึง fields ที่ต้องกรอกใน Settings
     * 
     * @return array
     */
    public function getRequiredFields() {
        return [
            'azure_api_key' => [
                'label' => 'Azure Subscription Key',
                'type' => 'password',
                'required' => true,
                'description' => 'Subscription Key จาก Azure Portal',
            ],
            'azure_region' => [
                'label' => 'Region',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'southeastasia' => 'Southeast Asia (Singapore)',
                    'eastasia' => 'East Asia (Hong Kong)',
                    'westus' => 'West US',
                    'eastus' => 'East US',
                    'westeurope' => 'West Europe',
                    'northeurope' => 'North Europe',
                    'australiaeast' => 'Australia East',
                    'japaneast' => 'Japan East',
                    'koreacentral' => 'Korea Central',
                ],
                'default' => 'southeastasia',
            ],
        ];
    }

    /**
     * ดึง region ที่ตั้งค่าไว้
     * 
     * @return string
     */
    private function getRegion() {
        return $this->settings->get_setting('azure_region', 'southeastasia');
    }

    /**
     * แปลข้อความด้วย Azure Translator API
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

        // สร้าง URL พร้อม query params
        $url = self::API_ENDPOINT . '?' . http_build_query([
            'api-version' => self::API_VERSION,
            'from' => $sourceLang,
            'to' => $targetLang,
        ]);

        // เตรียม request body
        $body = [
            ['text' => $text],
        ];

        // ส่ง request
        $response = $this->makeRequest($url, [
            'headers' => [
                'Ocp-Apim-Subscription-Key' => $this->apiKey,
                'Ocp-Apim-Subscription-Region' => $this->getRegion(),
                'Content-Type' => 'application/json; charset=UTF-8',
            ],
            'body' => json_encode($body),
            'timeout' => 30,
        ]);

        // ตรวจสอบ error
        if (is_wp_error($response)) {
            return $text;
        }

        // Parse response
        if (isset($response[0]['translations'][0]['text'])) {
            return $response[0]['translations'][0]['text'];
        }

        // Handle API error
        if (isset($response['error'])) {
            $this->logError('API Error: ' . ($response['error']['message'] ?? print_r($response['error'], true)));
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

        // ทดสอบแปล EN -> TH
        $result = $this->translate('Hello', 'th', 'en');
        
        // ถ้าได้ผลลัพธ์ที่ต่างจาก input = สำเร็จ
        return $result !== 'Hello' && !empty($result);
    }
}
