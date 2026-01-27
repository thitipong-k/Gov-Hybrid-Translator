<?php
/**
 * Translation Memory Class
 * 
 * เก็บประโยคที่แปลแล้วเพื่อใช้ซ้ำ
 * ช่วยประหยัด API tokens และเพิ่มความเร็ว
 * 
 * Storage: ใช้ WordPress Transients (ถ้าไม่มี Object Cache)
 * หรือ Custom Table (ถ้าต้องการ persistence)
 * 
 * @package GovHybridTranslator
 * @since 1.5.0
 */
namespace GovHybridTranslator\Modules;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

class TranslationMemory {

    /**
     * @var string Transient prefix
     */
    const TRANSIENT_PREFIX = 'ght_tm_';

    /**
     * @var int TTL (Time To Live) - 30 วัน
     */
    const TTL = 30 * DAY_IN_SECONDS;

    /**
     * @var string Option name สำหรับเก็บ statistics
     */
    const STATS_OPTION = 'ght_translation_memory_stats';

    /**
     * ลงทะเบียน hooks
     */
    public function register() {
        // Hook เข้า AI Translation เพื่อใช้ Memory
        add_filter('ght_before_ai_translate', [$this, 'check_memory'], 10, 3);
        add_action('ght_after_ai_translate', [$this, 'save_to_memory'], 10, 4);
    }

    /**
     * สร้าง hash key จาก source text และภาษา
     * 
     * @param string $source_text ข้อความต้นฉบับ
     * @param string $source_lang ภาษาต้นฉบับ
     * @param string $target_lang ภาษาเป้าหมาย
     * @return string Hash key
     */
    private function generate_key($source_text, $source_lang, $target_lang) {
        // ทำความสะอาด text ก่อน hash
        $normalized = $this->normalize_text($source_text);
        $hash = md5($normalized . '|' . $source_lang . '|' . $target_lang);
        return self::TRANSIENT_PREFIX . $hash;
    }

    /**
     * Normalize text สำหรับ matching
     * 
     * - ลบ whitespace ซ้ำ
     * - ลบ HTML tags
     * - แปลงเป็น lowercase
     * 
     * @param string $text ข้อความ
     * @return string ข้อความที่ normalize แล้ว
     */
    private function normalize_text($text) {
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        $text = mb_strtolower($text, 'UTF-8');
        return $text;
    }

    /**
     * ค้นหาใน Memory
     * 
     * @param string $source_text ข้อความต้นฉบับ
     * @param string $source_lang ภาษาต้นฉบับ
     * @param string $target_lang ภาษาเป้าหมาย
     * @return string|false ข้อความที่แปลแล้ว หรือ false ถ้าไม่พบ
     */
    public function find($source_text, $source_lang = 'th', $target_lang = 'en') {
        // ข้ามถ้าข้อความสั้นเกินไป (< 20 ตัวอักษร)
        if (mb_strlen($source_text) < 20) {
            return false;
        }

        $key = $this->generate_key($source_text, $source_lang, $target_lang);
        $cached = get_transient($key);

        if ($cached !== false) {
            // อัพเดทสถิติ
            $this->update_stats('hits');
            return $cached;
        }

        $this->update_stats('misses');
        return false;
    }

    /**
     * บันทึกลง Memory
     * 
     * @param string $source_text ข้อความต้นฉบับ
     * @param string $translated_text ข้อความที่แปลแล้ว
     * @param string $source_lang ภาษาต้นฉบับ
     * @param string $target_lang ภาษาเป้าหมาย
     */
    public function save($source_text, $translated_text, $source_lang = 'th', $target_lang = 'en') {
        // ข้ามถ้าข้อความสั้นเกินไป
        if (mb_strlen($source_text) < 20) {
            return;
        }

        // ข้ามถ้าผลลัพธ์ว่าง
        if (empty($translated_text)) {
            return;
        }

        $key = $this->generate_key($source_text, $source_lang, $target_lang);
        set_transient($key, $translated_text, self::TTL);

        // อัพเดทสถิติ
        $this->update_stats('saves');
    }

    /**
     * Hook: ตรวจสอบ Memory ก่อนเรียก AI
     * 
     * @param string|null $cached_result ผลลัพธ์จาก cache (null = ไม่มี)
     * @param string $source_text ข้อความต้นฉบับ
     * @param string $target_lang ภาษาเป้าหมาย
     * @return string|null ถ้าพบใน Memory จะ return ผลลัพธ์
     */
    public function check_memory($cached_result, $source_text, $target_lang) {
        // ถ้ามี cached result แล้ว ข้าม
        if ($cached_result !== null) {
            return $cached_result;
        }

        return $this->find($source_text, 'th', $target_lang);
    }

    /**
     * Hook: บันทึกลง Memory หลัง AI แปลเสร็จ
     * 
     * @param string $translated_text ข้อความที่แปลแล้ว
     * @param string $source_text ข้อความต้นฉบับ
     * @param string $source_lang ภาษาต้นฉบับ
     * @param string $target_lang ภาษาเป้าหมาย
     */
    public function save_to_memory($translated_text, $source_text, $source_lang, $target_lang) {
        $this->save($source_text, $translated_text, $source_lang, $target_lang);
    }

    /**
     * อัพเดทสถิติ
     * 
     * @param string $type ประเภท: hits, misses, saves
     */
    private function update_stats($type) {
        $stats = get_option(self::STATS_OPTION, [
            'hits' => 0,
            'misses' => 0,
            'saves' => 0,
            'last_updated' => '',
        ]);

        $stats[$type]++;
        $stats['last_updated'] = current_time('mysql');

        update_option(self::STATS_OPTION, $stats);
    }

    /**
     * ดึงสถิติ
     * 
     * @return array สถิติ [hits, misses, saves, hit_rate]
     */
    public static function get_stats() {
        $stats = get_option(self::STATS_OPTION, [
            'hits' => 0,
            'misses' => 0,
            'saves' => 0,
            'last_updated' => '',
        ]);

        // คำนวณ hit rate
        $total = $stats['hits'] + $stats['misses'];
        $stats['hit_rate'] = $total > 0 ? round(($stats['hits'] / $total) * 100, 1) : 0;

        return $stats;
    }

    /**
     * ล้าง Memory ทั้งหมด
     * 
     * หมายเหตุ: Transients ที่มี prefix ght_tm_ จะถูกลบ
     * ใช้สำหรับ reset หรือ cleanup
     */
    public static function clear_all() {
        global $wpdb;

        // ลบ transients ที่ขึ้นต้นด้วย prefix
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . self::TRANSIENT_PREFIX . '%'
            )
        );

        // Reset stats
        delete_option(self::STATS_OPTION);
    }

    /**
     * ค้นหาแบบ Fuzzy Match
     * 
     * หา segment ที่คล้ายกันแต่ไม่ exact match
     * ใช้ similar_text() ของ PHP
     * 
     * @param string $source_text ข้อความต้นฉบับ
     * @param string $target_lang ภาษาเป้าหมาย
     * @param int $threshold % ความเหมือนขั้นต่ำ (default: 80)
     * @return array|false [match, similarity] หรือ false ถ้าไม่พบ
     */
    public function find_fuzzy($source_text, $target_lang = 'en', $threshold = 80) {
        // TODO: Implement fuzzy match
        // ต้องใช้ database table แทน transients
        // สำหรับ v1 จะใช้แค่ exact match
        return false;
    }
}
