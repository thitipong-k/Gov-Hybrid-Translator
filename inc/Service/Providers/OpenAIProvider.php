<?php
/**
 * OpenAI Provider
 * 
 * ใช้ OpenAI GPT API สำหรับการแปล
 * เหมาะสำหรับ: การแปลที่ต้องการบริบท, tone ที่เหมาะสม
 * 
 * API Docs: https://platform.openai.com/docs/api-reference/chat
 * 
 * @package GovHybridTranslator
 * @since 1.6.0
 */
namespace GovHybridTranslator\Service\Providers;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

class OpenAIProvider extends BaseProvider {

    /**
     * @var string API Endpoint
     */
    const API_ENDPOINT = 'https://api.openai.com/v1/chat/completions';

    /**
     * @var string Default model
     */
    const DEFAULT_MODEL = 'gpt-3.5-turbo';

    /**
     * ดึงชื่อ Provider
     * 
     * @return string
     */
    public function getName() {
        return 'OpenAI GPT';
    }

    /**
     * ดึงรหัส Provider
     * 
     * @return string
     */
    public function getSlug() {
        return 'openai';
    }

    /**
     * ดึง fields ที่ต้องกรอกใน Settings
     * 
     * @return array
     */
    public function getRequiredFields() {
        return [
            'openai_api_key' => [
                'label' => 'OpenAI API Key',
                'type' => 'password',
                'required' => true,
                'description' => 'API Key จาก OpenAI Platform',
            ],
            'openai_model' => [
                'label' => 'Model',
                'type' => 'select',
                'required' => false,
                'options' => [
                    'gpt-3.5-turbo' => 'GPT-3.5 Turbo (เร็ว, ประหยัด)',
                    'gpt-4' => 'GPT-4 (ดีที่สุด, แพง)',
                    'gpt-4-turbo-preview' => 'GPT-4 Turbo (เร็ว, คุณภาพสูง)',
                ],
                'default' => 'gpt-3.5-turbo',
            ],
        ];
    }

    /**
     * ดึง model ที่ใช้
     * 
     * @return string
     */
    private function getModel() {
        return $this->settings->get_setting('openai_model', self::DEFAULT_MODEL);
    }

    /**
     * สร้าง system prompt สำหรับการแปล
     * 
     * @return string
     */
    private function getSystemPrompt() {
        return 'You are a professional translator for Thai government websites. ' .
               'Translate the following text accurately while maintaining a formal tone. ' .
               'Preserve any placeholders like {{HTML_x}} exactly as they are. ' .
               'Only return the translated text, nothing else.';
    }

    /**
     * แปลข้อความด้วย OpenAI GPT
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

        // สร้าง user prompt
        $langNames = [
            'th' => 'Thai',
            'en' => 'English',
            'zh' => 'Chinese',
            'ja' => 'Japanese',
            'ko' => 'Korean',
        ];
        $targetName = $langNames[$targetLang] ?? $targetLang;
        
        $userPrompt = "Translate the following text to {$targetName}:\n\n{$text}";

        // เตรียม request body
        $body = [
            'model' => $this->getModel(),
            'messages' => [
                ['role' => 'system', 'content' => $this->getSystemPrompt()],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'temperature' => 0.3, // ลด randomness สำหรับความถูกต้อง
        ];

        // ส่ง request
        $response = $this->makeRequest(self::API_ENDPOINT, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($body),
            'timeout' => 60,
        ]);

        // ตรวจสอบ error
        if (is_wp_error($response)) {
            return $text;
        }

        // Parse response
        if (isset($response['choices'][0]['message']['content'])) {
            return trim($response['choices'][0]['message']['content']);
        }

        // Handle API error
        if (isset($response['error'])) {
            $msg = $response['error']['message'] ?? print_r($response['error'], true);
            $this->logError('API Error: ' . $msg);
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

        // ทดสอบแปลคำง่ายๆ
        $result = $this->translate('Hello', 'th', 'en');
        
        // ถ้าได้ผลลัพธ์ที่ต่างจาก input = สำเร็จ
        return $result !== 'Hello' && !empty($result);
    }
}
