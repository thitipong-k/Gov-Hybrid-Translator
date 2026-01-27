<?php
/**
 * API Key Manager Class
 * 
 * จัดการ API Keys อย่างปลอดภัย
 * - เข้ารหัส (encrypt) ก่อนบันทึกลง database
 * - ถอดรหัส (decrypt) เมื่อต้องใช้งาน
 * - ใช้ WordPress auth keys เป็น encryption key
 * 
 * @package GovHybridTranslator
 * @since 1.6.0
 */
namespace GovHybridTranslator\Core;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

class APIKeyManager {

    /**
     * @var string Option name สำหรับเก็บ API Keys
     */
    const OPTION_NAME = 'ght_api_keys';

    /**
     * @var string Encryption method
     */
    const CIPHER_METHOD = 'AES-256-CBC';

    /**
     * ดึง encryption key จาก WordPress
     * ใช้ AUTH_KEY หรือ SECURE_AUTH_KEY
     * 
     * @return string
     */
    private static function get_encryption_key() {
        if (defined('AUTH_KEY') && AUTH_KEY !== 'put your unique phrase here') {
            return AUTH_KEY;
        }
        if (defined('SECURE_AUTH_KEY')) {
            return SECURE_AUTH_KEY;
        }
        // Fallback (ไม่แนะนำ)
        return 'ght_default_key_' . get_site_url();
    }

    /**
     * เข้ารหัส API Key
     * 
     * @param string $plain_text API Key แบบ plain text
     * @return string Encrypted string (base64)
     */
    public static function encrypt($plain_text) {
        if (empty($plain_text)) {
            return '';
        }

        $key = hash('sha256', self::get_encryption_key());
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::CIPHER_METHOD));
        
        $encrypted = openssl_encrypt(
            $plain_text,
            self::CIPHER_METHOD,
            $key,
            0,
            $iv
        );

        // เก็บ IV รวมกับ encrypted data
        return base64_encode($iv . '::' . $encrypted);
    }

    /**
     * ถอดรหัส API Key
     * 
     * @param string $encrypted_text Encrypted string (base64)
     * @return string Plain text API Key
     */
    public static function decrypt($encrypted_text) {
        if (empty($encrypted_text)) {
            return '';
        }

        $key = hash('sha256', self::get_encryption_key());
        
        // แยก IV และ encrypted data
        $data = base64_decode($encrypted_text);
        $parts = explode('::', $data, 2);
        
        if (count($parts) !== 2) {
            // อาจเป็น plain text เดิม (ก่อน encryption)
            return $encrypted_text;
        }

        list($iv, $encrypted) = $parts;

        $decrypted = openssl_decrypt(
            $encrypted,
            self::CIPHER_METHOD,
            $key,
            0,
            $iv
        );

        return $decrypted !== false ? $decrypted : '';
    }

    /**
     * บันทึก API Key สำหรับ provider
     * 
     * @param string $provider Provider slug (google, openai, etc.)
     * @param string $api_key API Key (plain text)
     * @param array $extra_data ข้อมูลเพิ่มเติม (model, region, etc.)
     */
    public static function save($provider, $api_key, $extra_data = []) {
        $all_keys = get_option(self::OPTION_NAME, []);

        // เข้ารหัส API Key
        $encrypted_key = self::encrypt($api_key);

        $all_keys[$provider] = [
            'api_key' => $encrypted_key,
            'extra' => $extra_data,
            'configured' => true,
            'updated_at' => current_time('mysql'),
        ];

        update_option(self::OPTION_NAME, $all_keys);
    }

    /**
     * ดึง API Key สำหรับ provider
     * 
     * @param string $provider Provider slug
     * @return string API Key (decrypted)
     */
    public static function get($provider) {
        $all_keys = get_option(self::OPTION_NAME, []);

        if (!isset($all_keys[$provider]['api_key'])) {
            return '';
        }

        return self::decrypt($all_keys[$provider]['api_key']);
    }

    /**
     * ดึง extra data สำหรับ provider
     * 
     * @param string $provider Provider slug
     * @return array Extra data
     */
    public static function get_extra($provider) {
        $all_keys = get_option(self::OPTION_NAME, []);
        return $all_keys[$provider]['extra'] ?? [];
    }

    /**
     * ลบ API Key สำหรับ provider
     * 
     * @param string $provider Provider slug
     */
    public static function delete($provider) {
        $all_keys = get_option(self::OPTION_NAME, []);
        unset($all_keys[$provider]);
        update_option(self::OPTION_NAME, $all_keys);
    }

    /**
     * ตรวจสอบว่า provider มี API Key หรือไม่
     * 
     * @param string $provider Provider slug
     * @return bool
     */
    public static function has($provider) {
        $all_keys = get_option(self::OPTION_NAME, []);
        return !empty($all_keys[$provider]['api_key']);
    }

    /**
     * ดึงรายการ providers ที่ตั้งค่าไว้แล้ว
     * 
     * @return array [provider => data]
     */
    public static function get_configured_providers() {
        $all_keys = get_option(self::OPTION_NAME, []);
        $configured = [];

        foreach ($all_keys as $provider => $data) {
            if (!empty($data['api_key'])) {
                $configured[$provider] = [
                    'has_key' => true,
                    'extra' => $data['extra'] ?? [],
                    'updated_at' => $data['updated_at'] ?? '',
                    'masked_key' => self::mask_key(self::decrypt($data['api_key'])),
                ];
            }
        }

        return $configured;
    }

    /**
     * Mask API Key สำหรับแสดงผล
     * แสดงแค่ 4 ตัวแรกและ 4 ตัวสุดท้าย
     * 
     * @param string $key API Key
     * @return string Masked key
     */
    public static function mask_key($key) {
        if (strlen($key) <= 8) {
            return str_repeat('•', strlen($key));
        }
        
        $first = substr($key, 0, 4);
        $last = substr($key, -4);
        $middle_length = strlen($key) - 8;
        
        return $first . str_repeat('•', min($middle_length, 20)) . $last;
    }
}
