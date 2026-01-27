<?php
/**
 * Translation Cache Class
 * 
 * จัดการ caching สำหรับ translated content
 * ช่วยเพิ่ม performance โดยไม่ต้อง query database ซ้ำ
 * 
 * Storage:
 * - Object Cache (Redis, Memcached) ถ้ามี
 * - Transients ถ้าไม่มี Object Cache
 * 
 * @package GovHybridTranslator
 * @since 1.5.0
 */
namespace GovHybridTranslator\Core;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

class TranslationCache {

    /**
     * @var string Cache group
     */
    const CACHE_GROUP = 'gov_translator';

    /**
     * @var int Default TTL - 24 ชั่วโมง
     */
    const DEFAULT_TTL = 24 * HOUR_IN_SECONDS;

    /**
     * ลงทะเบียน hooks
     * 
     * Hooks ที่ใช้:
     * - save_post: ล้าง cache เมื่อ Post ถูกแก้ไข
     * - delete_post: ล้าง cache เมื่อ Post ถูกลบ
     */
    public function register() {
        add_action('save_post', [$this, 'invalidate_post_cache'], 10, 1);
        add_action('delete_post', [$this, 'invalidate_post_cache'], 10, 1);
    }

    /**
     * ดึงข้อมูลจาก Cache
     * 
     * @param string $key Cache key
     * @return mixed|false ข้อมูล หรือ false ถ้าไม่พบ
     */
    public function get($key) {
        // ลอง Object Cache ก่อน
        $cached = wp_cache_get($key, self::CACHE_GROUP);
        
        if ($cached !== false) {
            return $cached;
        }

        // Fallback เป็น Transient
        return get_transient(self::CACHE_GROUP . '_' . $key);
    }

    /**
     * บันทึกข้อมูลลง Cache
     * 
     * @param string $key Cache key
     * @param mixed $value ข้อมูลที่จะ cache
     * @param int $ttl Time To Live (seconds)
     */
    public function set($key, $value, $ttl = null) {
        if ($ttl === null) {
            $ttl = self::DEFAULT_TTL;
        }

        // บันทึกลง Object Cache
        wp_cache_set($key, $value, self::CACHE_GROUP, $ttl);

        // บันทึกลง Transient ด้วย (backup)
        set_transient(self::CACHE_GROUP . '_' . $key, $value, $ttl);
    }

    /**
     * ลบข้อมูลจาก Cache
     * 
     * @param string $key Cache key
     */
    public function delete($key) {
        wp_cache_delete($key, self::CACHE_GROUP);
        delete_transient(self::CACHE_GROUP . '_' . $key);
    }

    /**
     * สร้าง Cache key สำหรับ Post
     * 
     * @param int $post_id Post ID
     * @param string $lang ภาษา
     * @return string Cache key
     */
    public static function get_post_key($post_id, $lang = '') {
        return 'post_' . $post_id . ($lang ? '_' . $lang : '');
    }

    /**
     * Cache translated content ของ Post
     * 
     * @param int $post_id Post ID
     * @param string $content Translated content
     * @param string $lang ภาษา
     */
    public function cache_post_content($post_id, $content, $lang) {
        $key = self::get_post_key($post_id, $lang);
        $this->set($key, $content);
    }

    /**
     * ดึง cached content ของ Post
     * 
     * @param int $post_id Post ID
     * @param string $lang ภาษา
     * @return string|false Content หรือ false ถ้าไม่มี cache
     */
    public function get_post_content($post_id, $lang) {
        $key = self::get_post_key($post_id, $lang);
        return $this->get($key);
    }

    /**
     * ล้าง Cache เมื่อ Post ถูกแก้ไข
     * 
     * ขั้นตอน:
     * 1. ล้าง cache ของ Post นี้
     * 2. ถ้าเป็น original → ล้าง cache ของ translations ด้วย
     * 3. ถ้าเป็น translation → ล้าง cache ของ original ด้วย
     * 
     * @param int $post_id Post ID
     */
    public function invalidate_post_cache($post_id) {
        // ข้ามถ้าเป็น revision
        if (wp_is_post_revision($post_id)) {
            return;
        }

        // ล้าง cache ของ Post นี้
        $this->clear_post_cache($post_id);

        // ดึง group_id เพื่อหา related posts
        $group_id = get_post_meta($post_id, '_gov_translator_group_id', true);
        $original_id = get_post_meta($post_id, '_gov_translator_original_id', true);

        if (!empty($group_id)) {
            // หา posts ทั้งหมดในกลุ่ม
            $related_posts = get_posts([
                'post_type' => ['post', 'page'],
                'post_status' => 'any',
                'posts_per_page' => -1,
                'meta_key' => '_gov_translator_group_id',
                'meta_value' => $group_id,
                'exclude' => [$post_id],
            ]);

            // ล้าง cache ของ related posts
            foreach ($related_posts as $related) {
                $this->clear_post_cache($related->ID);
            }
        }
    }

    /**
     * ล้าง cache ของ Post เดี่ยว
     * 
     * @param int $post_id Post ID
     */
    private function clear_post_cache($post_id) {
        // ลบทุกภาษา
        $languages = ['th', 'en', 'zh', 'ja', 'ko'];
        
        foreach ($languages as $lang) {
            $this->delete(self::get_post_key($post_id, $lang));
        }

        // ลบ key ที่ไม่มีภาษา
        $this->delete(self::get_post_key($post_id));
    }

    /**
     * ล้าง Cache ทั้งหมด
     */
    public static function clear_all() {
        global $wpdb;

        // ล้าง Object Cache group
        wp_cache_flush();

        // ล้าง Transients ที่เกี่ยวข้อง
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . self::CACHE_GROUP . '_%'
            )
        );
    }

    /**
     * ดึงสถิติ Cache
     * 
     * @return array สถิติ [hits, misses, size]
     */
    public static function get_stats() {
        global $wpdb;

        // นับจำนวน transients
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . self::CACHE_GROUP . '_%'
            )
        );

        return [
            'cached_items' => intval($count),
            'cache_group' => self::CACHE_GROUP,
            'ttl_hours' => self::DEFAULT_TTL / HOUR_IN_SECONDS,
        ];
    }
}
