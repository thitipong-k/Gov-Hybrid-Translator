<?php
/**
 * Claude Provider
 * 
 * ใช้ Anthropic Claude API สำหรับการแปล
 * เหมาะสำหรับ: การแปลที่ต้องการความเข้าใจบริบทสูง, tone เป็นทางการ
 * 
 * API Docs: https://docs.anthropic.com/claude/reference/messages_post
 * 
 * @package GovHybridTranslator
 * @since 1.6.0
 */
namespace GovHybridTranslator\Service\Providers;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

class ClaudeProvider extends BaseProvider {

    /**
     * @var string API Endpoint
     */
    const API_ENDPOINT = 'https://api.anthropic.com/v1/messages';

    /**
     * @var string Default model
     */
    const DEFAULT_MODEL = 'claude-3-sonnet-20240229';

    /**
     * @var string API Version
     */
    const API_VERSION = '2023-06-01';

    /**
     * ดึงชื่อ Provider
     * 
     * @return string
     */
    public function getName() {
        return 'Anthropic Claude';
    }

    /**
     * ดึงรหัส Provider
     * 
     * @return string
     */
    public function getSlug() {
        return 'claude';
    }

    /**
     * ดึง fields ที่ต้องกรอกใน Settings
     * 
     * @return array
     */
    public function getRequiredFields() {
        return [
            'claude_api_key' => [
                'label' => 'Claude API Key',
                'type' => 'password',
                'required' => true,
                'description' => 'API Key จาก Anthropic Console',
            ],
            'claude_model' => [
                'label' => 'Model',
                'type' => 'select',
                'required' => false,
                'options' => [
                    'claude-3-haiku-20240307' => 'Claude 3 Haiku (เร็ว, ประหยัด)',
                    'claude-3-sonnet-20240229' => 'Claude 3 Sonnet (สมดุล)',
                    'claude-3-opus-20240229' => 'Claude 3 Opus (ดีที่สุด)',
                ],
                'default' => 'claude-3-sonnet-20240229',
            ],
        ];
    }

    /**
     * ดึง model ที่ใช้
     * 
     * @return string
     */
    private function getModel() {
        return $this->settings->get_setting('claude_model', self::DEFAULT_MODEL);
    }

    /**
     * สร้าง system prompt สำหรับการแปล
     * 
     * @return string
     */
    private function getSystemPrompt() {
        return 'You are a professional translator specializing in Thai government documents. ' .
               'Translate text to the target language while maintaining formal tone and accuracy. ' .
               'Preserve any placeholders like {{HTML_x}} exactly as they appear. ' .
               'Respond with only the translated text, no explanations.';
    }

    /**
     * แปลข้อความด้วย Claude API
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
            'system' => $this->getSystemPrompt(),
            'messages' => [
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'max_tokens' => 4096,
        ];

        // ส่ง request
        $response = $this->makeRequest(self::API_ENDPOINT, [
            'headers' => [
                'x-api-key' => $this->apiKey,
                'anthropic-version' => self::API_VERSION,
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
        if (isset($response['content'][0]['text'])) {
            return trim($response['content'][0]['text']);
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

        // ทดสอบแปลคำง่ายๆ
        $result = $this->translate('Hello', 'th', 'en');
        
        // ถ้าได้ผลลัพธ์ที่ต่างจาก input = สำเร็จ
        return $result !== 'Hello' && !empty($result);
    }
}
