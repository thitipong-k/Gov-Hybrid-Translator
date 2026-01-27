<?php
namespace GovHybridTranslator\Core;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

class Languages {

    /**
     * Get all supported languages
     * 
     * @return array Array of language configurations
     */
    public static function get_supported_languages() {
        return [
            'en' => [
                'code' => 'en',
                'name' => 'English',
                'native_name' => 'English',
                'flag' => '🇬🇧',
                'direction' => 'ltr',
                'enabled' => true,
                'popular' => true,
            ],
            'zh' => [
                'code' => 'zh',
                'name' => 'Chinese',
                'native_name' => '中文',
                'flag' => '🇨🇳',
                'direction' => 'ltr',
                'enabled' => true,
                'popular' => true,
            ],
            'ja' => [
                'code' => 'ja',
                'name' => 'Japanese',
                'native_name' => '日本語',
                'flag' => '🇯🇵',
                'direction' => 'ltr',
                'enabled' => true,
                'popular' => true,
            ],
            'ko' => [
                'code' => 'ko',
                'name' => 'Korean',
                'native_name' => '한국어',
                'flag' => '🇰🇷',
                'direction' => 'ltr',
                'enabled' => true,
                'popular' => false,
            ],
            'fr' => [
                'code' => 'fr',
                'name' => 'French',
                'native_name' => 'Français',
                'flag' => '🇫🇷',
                'direction' => 'ltr',
                'enabled' => true,
                'popular' => false,
            ],
            'de' => [
                'code' => 'de',
                'name' => 'German',
                'native_name' => 'Deutsch',
                'flag' => '🇩🇪',
                'direction' => 'ltr',
                'enabled' => true,
                'popular' => false,
            ],
            'es' => [
                'code' => 'es',
                'name' => 'Spanish',
                'native_name' => 'Español',
                'flag' => '🇪🇸',
                'direction' => 'ltr',
                'enabled' => true,
                'popular' => false,
            ],
            'ru' => [
                'code' => 'ru',
                'name' => 'Russian',
                'native_name' => 'Русский',
                'flag' => '🇷🇺',
                'direction' => 'ltr',
                'enabled' => true,
                'popular' => false,
            ],
            'ar' => [
                'code' => 'ar',
                'name' => 'Arabic',
                'native_name' => 'العربية',
                'flag' => '🇸🇦',
                'direction' => 'rtl',
                'enabled' => true,
                'popular' => false,
            ],
            'vi' => [
                'code' => 'vi',
                'name' => 'Vietnamese',
                'native_name' => 'Tiếng Việt',
                'flag' => '🇻🇳',
                'direction' => 'ltr',
                'enabled' => true,
                'popular' => false,
            ],
            'pt' => [
                'code' => 'pt',
                'name' => 'Portuguese',
                'native_name' => 'Português',
                'flag' => '🇵🇹',
                'direction' => 'ltr',
                'enabled' => true,
                'popular' => false,
            ],
            'it' => [
                'code' => 'it',
                'name' => 'Italian',
                'native_name' => 'Italiano',
                'flag' => '🇮🇹',
                'direction' => 'ltr',
                'enabled' => true,
                'popular' => false,
            ],
            'nl' => [
                'code' => 'nl',
                'name' => 'Dutch',
                'native_name' => 'Nederlands',
                'flag' => '🇳🇱',
                'direction' => 'ltr',
                'enabled' => true,
                'popular' => false,
            ],
            'pl' => [
                'code' => 'pl',
                'name' => 'Polish',
                'native_name' => 'Polski',
                'flag' => '🇵🇱',
                'direction' => 'ltr',
                'enabled' => true,
                'popular' => false,
            ],
            'tr' => [
                'code' => 'tr',
                'name' => 'Turkish',
                'native_name' => 'Türkçe',
                'flag' => '🇹🇷',
                'direction' => 'ltr',
                'enabled' => true,
                'popular' => false,
            ],
            'hi' => [
                'code' => 'hi',
                'name' => 'Hindi',
                'native_name' => 'हिन्दी',
                'flag' => '🇮🇳',
                'direction' => 'ltr',
                'enabled' => true,
                'popular' => false,
            ],
            'id' => [
                'code' => 'id',
                'name' => 'Indonesian',
                'native_name' => 'Bahasa Indonesia',
                'flag' => '🇮🇩',
                'direction' => 'ltr',
                'enabled' => true,
                'popular' => false,
            ],
            'ms' => [
                'code' => 'ms',
                'name' => 'Malay',
                'native_name' => 'Bahasa Melayu',
                'flag' => '🇲🇾',
                'direction' => 'ltr',
                'enabled' => true,
                'popular' => false,
            ],
            'tl' => [
                'code' => 'tl',
                'name' => 'Tagalog',
                'native_name' => 'Tagalog',
                'flag' => '🇵🇭',
                'direction' => 'ltr',
                'enabled' => true,
                'popular' => false,
            ],
            'my' => [
                'code' => 'my',
                'name' => 'Burmese',
                'native_name' => 'မြန်မာဘာသာ',
                'flag' => '🇲🇲',
                'direction' => 'ltr',
                'enabled' => true,
                'popular' => false,
            ],
            'km' => [
                'code' => 'km',
                'name' => 'Khmer',
                'native_name' => 'ភាសាខ្មែរ',
                'flag' => '🇰🇭',
                'direction' => 'ltr',
                'enabled' => true,
                'popular' => false,
            ],
            'lo' => [
                'code' => 'lo',
                'name' => 'Lao',
                'native_name' => 'ພາສາລາວ',
                'flag' => '🇱🇦',
                'direction' => 'ltr',
                'enabled' => true,
                'popular' => false,
            ],
        ];
    }

    /**
     * Get enabled languages only
     * 
     * @return array
     */
    public static function get_enabled_languages() {
        return array_filter(self::get_supported_languages(), function($lang) {
            return $lang['enabled'] === true;
        });
    }

    /**
     * Get popular languages (for quick selection)
     * 
     * @return array
     */
    public static function get_popular_languages() {
        return array_filter(self::get_supported_languages(), function($lang) {
            return $lang['popular'] === true && $lang['enabled'] === true;
        });
    }

    /**
     * Get language by code
     * 
     * @param string $code Language code
     * @return array|null
     */
    public static function get_language($code) {
        $languages = self::get_supported_languages();
        return isset($languages[$code]) ? $languages[$code] : null;
    }

    /**
     * Check if language is supported
     * 
     * @param string $code Language code
     * @return bool
     */
    public static function is_supported($code) {
        $languages = self::get_supported_languages();
        return isset($languages[$code]) && $languages[$code]['enabled'];
    }

    /**
     * Get language name
     * 
     * @param string $code Language code
     * @param bool $native Return native name
     * @return string
     */
    public static function get_language_name($code, $native = false) {
        $lang = self::get_language($code);
        if (!$lang) return $code;
        return $native ? $lang['native_name'] : $lang['name'];
    }

    /**
     * Get language flag emoji
     * 
     * @param string $code Language code
     * @return string
     */
    public static function get_language_flag($code) {
        $lang = self::get_language($code);
        return $lang ? $lang['flag'] : '🌐';
    }

    /**
     * Filter for adding custom languages
     * Allows developers to add more languages via filter
     * 
     * @return array
     */
    public static function get_all_languages() {
        $languages = self::get_supported_languages();
        
        // Allow developers to add custom languages
        return apply_filters('ght_supported_languages', $languages);
    }
}
