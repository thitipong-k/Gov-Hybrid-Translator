<?php
/**
 * Provider Factory
 * 
 * Factory class สำหรับสร้าง Translation Provider
 * ใช้ pattern Factory Method เพื่อให้ง่ายต่อการเพิ่ม Provider ใหม่
 * 
 * @package GovHybridTranslator
 * @since 1.6.0
 */
namespace GovHybridTranslator\Service;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Service\Providers\GoogleProvider;
use GovHybridTranslator\Service\Providers\OpenAIProvider;
use GovHybridTranslator\Service\Providers\DeepLProvider;
use GovHybridTranslator\Service\Providers\AzureProvider;
use GovHybridTranslator\Service\Providers\ClaudeProvider;
use GovHybridTranslator\Service\Providers\TranslationProviderInterface;

class ProviderFactory {

    /**
     * รายการ Providers ที่รองรับ
     * 
     * format: 'slug' => 'ClassName'
     * 
     * @var array
     */
    private static $providers = [
        'google' => GoogleProvider::class,
        'openai' => OpenAIProvider::class,
        'deepl' => DeepLProvider::class,
        'azure' => AzureProvider::class,
        'claude' => ClaudeProvider::class,
    ];

    /**
     * สร้าง Provider จากชื่อ
     * 
     * @param string $providerName ชื่อ provider (เช่น 'google', 'openai')
     * @param string|null $apiKey API Key (ถ้าไม่ระบุจะดึงจาก Settings)
     * @return TranslationProviderInterface|null Provider object หรือ null ถ้าไม่พบ
     */
    public static function create($providerName, $apiKey = null) {
        $providerName = strtolower($providerName);

        if (!isset(self::$providers[$providerName])) {
            return null;
        }

        $className = self::$providers[$providerName];
        return new $className($apiKey);
    }

    /**
     * ดึง Provider ที่ตั้งค่าไว้ใน Settings
     * 
     * @return TranslationProviderInterface|null
     */
    public static function createFromSettings() {
        $settings = new \GovHybridTranslator\Modules\Settings();
        $providerName = $settings->get_setting('ai_provider', 'google');
        
        return self::create($providerName);
    }

    /**
     * ดึงรายการ Providers ที่รองรับทั้งหมด
     * 
     * @return array [
     *   'slug' => [
     *     'name' => 'Display Name',
     *     'fields' => [...required fields...]
     *   ]
     * ]
     */
    public static function getAvailableProviders() {
        $available = [];

        foreach (self::$providers as $slug => $className) {
            $provider = new $className();
            $available[$slug] = [
                'name' => $provider->getName(),
                'slug' => $provider->getSlug(),
                'fields' => $provider->getRequiredFields(),
            ];
        }

        return $available;
    }

    /**
     * ตรวจสอบว่า Provider มีอยู่หรือไม่
     * 
     * @param string $providerName ชื่อ provider
     * @return bool
     */
    public static function exists($providerName) {
        return isset(self::$providers[strtolower($providerName)]);
    }

    /**
     * ลงทะเบียน Provider ใหม่
     * 
     * ใช้สำหรับเพิ่ม custom provider จาก plugin อื่น
     * 
     * @param string $slug รหัส provider
     * @param string $className ชื่อ class (ต้อง implement TranslationProviderInterface)
     */
    public static function register($slug, $className) {
        self::$providers[strtolower($slug)] = $className;
    }

    /**
     * ดึงรายการ slugs ของ Providers ทั้งหมด
     * 
     * @return array ['google', 'openai', 'deepl', ...]
     */
    public static function getProviderSlugs() {
        return array_keys(self::$providers);
    }
}
