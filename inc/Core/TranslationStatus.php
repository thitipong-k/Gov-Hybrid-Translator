<?php
/**
 * Translation Status Class
 * 
 * จัดการสถานะการแปลของ Posts/Pages
 * ใช้ตรวจสอบว่า Post มีการแปลหรือยัง และต้องอัพเดทหรือไม่
 * 
 * สถานะที่เป็นไปได้:
 * - `none`: ยังไม่มีการแปล
 * - `pending`: รอแปล (มี original แต่ยังไม่มี translation)
 * - `translated`: แปลครบทุกภาษาเป้าหมาย
 * - `partial`: แปลบางภาษา
 * - `needs_update`: ต้องอัพเดท (original เปลี่ยนแปลงหลังแปล)
 * 
 * @package GovHybridTranslator
 * @since 1.5.0
 */
namespace GovHybridTranslator\Core;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Modules\Settings;
use GovHybridTranslator\Core\TranslationMeta;

class TranslationStatus {

    /**
     * @var Settings ออบเจ็กต์สำหรับดึงค่า settings
     */
    private $settings;

    /**
     * Status Constants
     * ค่าคงที่สำหรับสถานะต่างๆ
     */
    const STATUS_NONE = 'none';           // ยังไม่มีการแปล
    const STATUS_PENDING = 'pending';     // รอแปล
    const STATUS_TRANSLATED = 'translated'; // แปลครบแล้ว
    const STATUS_PARTIAL = 'partial';     // แปลบางภาษา
    const STATUS_NEEDS_UPDATE = 'needs_update'; // ต้องอัพเดท

    /**
     * Constructor
     * สร้าง Settings object
     */
    public function __construct() {
        $this->settings = new Settings();
    }

    /**
     * ลงทะเบียน hooks
     * 
     * Hooks ที่ใช้:
     * - save_post: ตรวจจับเมื่อ Post ต้นฉบับถูกแก้ไข
     */
    public function register() {
        // ตรวจจับการแก้ไข Post ต้นฉบับ
        add_action('save_post', [$this, 'on_post_saved'], 10, 3);
    }

    /**
     * ดึงสถานะการแปลของ Post
     * 
     * ขั้นตอน:
     * 1. ตรวจสอบ Meta-based translations ก่อน (_ght_title_{lang})
     * 2. ถ้าไม่มี → ตรวจสอบ Clone-based translations (_gov_translator_group_id)
     * 3. กำหนดสถานะตามจำนวนภาษาที่แปลแล้ว
     * 
     * @param int $post_id Post ID
     * @return string สถานะ: none, pending, translated, partial, needs_update
     * 
     * @since 2.1.1 - เพิ่มการตรวจสอบ Meta-based translations
     */
    public function get_status($post_id) {
        // ดึง target languages จาก settings
        $target_langs = $this->settings->get_setting('target_languages', ['en']);
        
        // === ตรวจสอบ Meta-based translations ก่อน ===
        $meta_langs = TranslationMeta::get_languages($post_id);
        
        if (!empty($meta_langs)) {
            // มี meta-based translations
            if (count($meta_langs) >= count($target_langs)) {
                return self::STATUS_TRANSLATED;
            } else {
                return self::STATUS_PARTIAL;
            }
        }
        
        // === ตรวจสอบ Clone-based translations (legacy) ===
        $lang = get_post_meta($post_id, '_gov_translator_lang', true);
        $group_id = get_post_meta($post_id, '_gov_translator_group_id', true);
        $original_id = get_post_meta($post_id, '_gov_translator_original_id', true);
        $needs_update = get_post_meta($post_id, '_gov_translator_needs_update', true);

        // กรณีที่ 1: เป็น Translation (clone) → ตรวจสอบว่าต้อง update
        if (!empty($original_id)) {
            if ($needs_update === '1') {
                return self::STATUS_NEEDS_UPDATE;
            }
            return self::STATUS_TRANSLATED;
        }

        // กรณีที่ 2: เป็น Post ต้นฉบับที่มี clone → ตรวจสอบว่ามี translations
        if (!empty($group_id)) {
            $translations = $this->get_translated_posts($post_id);
            
            if (count($translations) === 0) {
                return self::STATUS_PENDING;
            } elseif (count($translations) >= count($target_langs)) {
                return self::STATUS_TRANSLATED;
            } else {
                return self::STATUS_PARTIAL;
            }
        }

        // กรณีที่ 3: ไม่มีการแปลใดๆ = ยังไม่เคยแปล
        return self::STATUS_NONE;
    }

    /**
     * ดึง Posts ที่แปลจาก Post ต้นฉบับ
     * 
     * @param int $post_id Post ID ต้นฉบับ
     * @return array Array ของ WP_Post objects
     */
    public function get_translated_posts($post_id) {
        // ดึง group_id ของ Post นี้
        $group_id = get_post_meta($post_id, '_gov_translator_group_id', true);
        
        if (empty($group_id)) {
            return [];
        }

        // ค้นหา Posts ทั้งหมดในกลุ่มเดียวกัน (ยกเว้น Post นี้)
        $args = [
            'post_type' => ['post', 'page'],
            'post_status' => 'any',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_gov_translator_group_id',
                    'value' => $group_id,
                ],
                [
                    'key' => '_gov_translator_original_id',
                    'value' => $post_id,
                ],
            ],
        ];

        return get_posts($args);
    }

    /**
     * ดึง Post ต้นฉบับจาก Translation
     * 
     * @param int $translation_id Translation Post ID
     * @return WP_Post|null Post ต้นฉบับ หรือ null
     */
    public function get_original_post($translation_id) {
        $original_id = get_post_meta($translation_id, '_gov_translator_original_id', true);
        
        if (empty($original_id)) {
            return null;
        }

        return get_post($original_id);
    }

    /**
     * อัพเดทสถานะ "Needs Update" ให้ translations ทั้งหมดในกลุ่ม
     * 
     * เรียกใช้เมื่อ Post ต้นฉบับถูกแก้ไข
     * 
     * @param int $original_id Post ID ต้นฉบับ
     * @return int จำนวน Posts ที่อัพเดท
     */
    public function mark_needs_update($original_id) {
        $translations = $this->get_translated_posts($original_id);
        $count = 0;

        foreach ($translations as $translation) {
            // บันทึกว่าต้อง update
            update_post_meta($translation->ID, '_gov_translator_needs_update', '1');
            // บันทึกเวลาที่ original เปลี่ยน
            update_post_meta($translation->ID, '_gov_translator_original_modified', current_time('mysql'));
            $count++;
        }

        return $count;
    }

    /**
     * ล้างสถานะ "Needs Update"
     * 
     * เรียกใช้หลังจาก Translation ถูกอัพเดทแล้ว
     * 
     * @param int $translation_id Translation Post ID
     */
    public function clear_needs_update($translation_id) {
        delete_post_meta($translation_id, '_gov_translator_needs_update');
        delete_post_meta($translation_id, '_gov_translator_original_modified');
    }

    /**
     * Hook: เมื่อ Post ถูกบันทึก
     * 
     * ตรวจสอบว่าเป็น Post ต้นฉบับที่มี translations หรือไม่
     * ถ้าใช่ → อัพเดทสถานะ translations เป็น "needs_update"
     * 
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     * @param bool $update เป็นการอัพเดทหรือไม่ (true = update, false = insert)
     */
    public function on_post_saved($post_id, $post, $update) {
        // ข้ามถ้าเป็น autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // ข้ามถ้าเป็น revision
        if (wp_is_post_revision($post_id)) {
            return;
        }

        // ข้ามถ้าไม่ใช่การ update (เป็นการสร้างใหม่)
        if (!$update) {
            return;
        }

        // ตรวจสอบว่าเป็น Post/Page หรือไม่
        if (!in_array($post->post_type, ['post', 'page'])) {
            return;
        }

        // ตรวจสอบว่ามี translations หรือไม่
        $group_id = get_post_meta($post_id, '_gov_translator_group_id', true);
        $original_id = get_post_meta($post_id, '_gov_translator_original_id', true);

        // ถ้าเป็น Post ต้นฉบับที่มี group_id → อัพเดท translations
        if (!empty($group_id) && empty($original_id)) {
            $this->mark_needs_update($post_id);
        }
    }

    /**
     * ดึงสถิติการแปลทั้งหมด
     * 
     * @return array [
     *   'total' => จำนวน Posts/Pages ทั้งหมด,
     *   'translated' => แปลครบแล้ว,
     *   'partial' => แปลบางภาษา,
     *   'pending' => รอแปล,
     *   'needs_update' => ต้องอัพเดท,
     *   'none' => ยังไม่เริ่มแปล
     * ]
     */
    public function get_statistics() {
        // ดึงภาษาต้นฉบับ
        $source_lang = $this->settings->get_setting('source_language', 'th');

        // ดึง Posts/Pages ที่เป็นภาษาต้นฉบับ (ไม่ใช่ Translations)
        $args = [
            'post_type' => ['post', 'page'],
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'OR',
                // Posts ที่ยังไม่มี lang (ถือว่าเป็นภาษาต้นฉบับ)
                [
                    'key' => '_gov_translator_lang',
                    'compare' => 'NOT EXISTS',
                ],
                // Posts ที่เป็นภาษาต้นฉบับ
                [
                    'key' => '_gov_translator_lang',
                    'value' => $source_lang,
                ],
            ],
        ];

        $posts = get_posts($args);

        $stats = [
            'total' => count($posts),
            'translated' => 0,
            'partial' => 0,
            'pending' => 0,
            'needs_update' => 0,
            'none' => 0,
        ];

        // นับแต่ละสถานะ
        foreach ($posts as $post) {
            $status = $this->get_status($post->ID);
            if (isset($stats[$status])) {
                $stats[$status]++;
            }
        }

        // นับ translations ที่ต้อง update
        $needs_update_args = [
            'post_type' => ['post', 'page'],
            'post_status' => 'any',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_gov_translator_needs_update',
                    'value' => '1',
                ],
            ],
        ];
        $needs_update_posts = get_posts($needs_update_args);
        $stats['needs_update'] = count($needs_update_posts);

        return $stats;
    }

    /**
     * ดึง label สำหรับแสดงผล
     * 
     * @param string $status สถานะ
     * @return array ['label' => 'ชื่อ', 'color' => 'สี CSS']
     */
    public static function get_status_label($status) {
        $labels = [
            self::STATUS_NONE => [
                'label' => 'ยังไม่แปล',
                'color' => 'gray',
                'icon' => '⬜',
            ],
            self::STATUS_PENDING => [
                'label' => 'รอแปล',
                'color' => 'yellow',
                'icon' => '🟡',
            ],
            self::STATUS_TRANSLATED => [
                'label' => 'แปลแล้ว',
                'color' => 'green',
                'icon' => '✅',
            ],
            self::STATUS_PARTIAL => [
                'label' => 'แปลบางส่วน',
                'color' => 'blue',
                'icon' => '🔵',
            ],
            self::STATUS_NEEDS_UPDATE => [
                'label' => 'ต้องอัพเดท',
                'color' => 'orange',
                'icon' => '🟠',
            ],
        ];

        return $labels[$status] ?? $labels[self::STATUS_NONE];
    }
}
