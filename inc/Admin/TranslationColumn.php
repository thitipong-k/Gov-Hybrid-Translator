<?php
/**
 * Translation Column Class
 * 
 * เพิ่ม column "Translation Status" ใน Posts/Pages list ใน Admin
 * แสดงสถานะการแปลพร้อม badge สี
 * 
 * @package GovHybridTranslator
 * @since 1.5.0
 */
namespace GovHybridTranslator\Admin;

// ป้องกันการเข้าถึงไฟล์โดยตรง
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Core\TranslationStatus;

class TranslationColumn {

    /**
     * @var TranslationStatus ออบเจ็กต์สำหรับดึงสถานะ
     */
    private $status_helper;

    /**
     * Constructor
     */
    public function __construct() {
        $this->status_helper = new TranslationStatus();
    }

    /**
     * ลงทะเบียน hooks
     * 
     * Hooks ที่ใช้:
     * - manage_posts_columns: เพิ่ม header column
     * - manage_posts_custom_column: แสดงเนื้อหา column
     * - manage_pages_columns: เพิ่ม header column สำหรับ Pages
     * - manage_pages_custom_column: แสดงเนื้อหา column สำหรับ Pages
     * - admin_head: เพิ่ม CSS styles
     */
    public function register() {
        // === Posts ===
        add_filter('manage_posts_columns', [$this, 'add_column']);
        add_action('manage_posts_custom_column', [$this, 'render_column'], 10, 2);

        // === Pages ===
        add_filter('manage_pages_columns', [$this, 'add_column']);
        add_action('manage_pages_custom_column', [$this, 'render_column'], 10, 2);

        // === CSS ===
        add_action('admin_head', [$this, 'add_styles']);
    }

    /**
     * เพิ่ม column header "Translation"
     * 
     * @param array $columns Columns ที่มีอยู่
     * @return array Columns ที่อัพเดท
     */
    public function add_column($columns) {
        // แทรก column หลัง 'title'
        $new_columns = [];
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['translation_status'] = '🌐 Translation';
            }
        }
        return $new_columns;
    }

    /**
     * แสดงเนื้อหา column
     * 
     * แสดง badge ภาษาที่แปลแล้ว (ยกเว้นภาษาไทย)
     * ใช้ HTML text badge แทน emoji เพื่อ compatibility
     * 
     * @param string $column ชื่อ column
     * @param int $post_id Post ID
     * 
     * @since 1.8.0 - Meta-based + text badge
     */
    public function render_column($column, $post_id) {
        if ($column !== 'translation_status') {
            return;
        }

        // ดึงภาษาที่มี translation จาก Meta
        $translated_langs = \GovHybridTranslator\Core\TranslationMeta::get_languages($post_id);

        if (empty($translated_langs)) {
            echo '<span style="color: #ccc;">—</span>';
            return;
        }

        $badges = [];
        foreach ($translated_langs as $lang) {
            // ข้ามภาษาไทย (ภาษาหลัก)
            if ($lang === 'th') {
                continue;
            }

            // สร้าง badge HTML พร้อม styling
            $lang_upper = strtoupper($lang);
            $badges[] = '<span class="ght-lang-badge" title="' . esc_attr($lang_upper) . '">' . esc_html($lang_upper) . '</span>';
        }

        if (!empty($badges)) {
            echo implode(' ', $badges);
        } else {
            echo '<span style="color: #ccc;">—</span>';
        }
    }

    /**
     * เพิ่ม CSS Styles สำหรับ badges
     */
    public function add_styles() {
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->base, ['edit'])) {
            return;
        }
        ?>
        <style>
        /* === Translation Status Column === */
        .column-translation_status {
            width: 120px;
        }

        /* === Status Badges === */
        .ght-status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
            line-height: 1.4;
        }

        /* สีเทา - ยังไม่แปล */
        .ght-status-gray {
            background: #f0f0f0;
            color: #666;
        }

        /* สีเหลือง - รอแปล */
        .ght-status-yellow {
            background: #fff3cd;
            color: #856404;
        }

        /* สีเขียว - แปลครบ */
        .ght-status-green {
            background: #d4edda;
            color: #155724;
        }

        /* สีฟ้า - แปลบางส่วน */
        .ght-status-blue {
            background: #cce5ff;
            color: #004085;
        }

        /* สีส้ม - ต้องอัพเดท */
        .ght-status-orange {
            background: #ffe5cc;
            color: #cc5500;
        }

        /* === Language Badge (New) === */
        .ght-lang-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 600;
            background: linear-gradient(135deg, #0073aa, #005177);
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-right: 3px;
        }

        /* EN = เขียว */
        .ght-lang-badge[title="EN"] {
            background: linear-gradient(135deg, #28a745, #1e7e34);
        }

        /* ZH = แดง */
        .ght-lang-badge[title="ZH"] {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }

        /* JA = ชมพู */
        .ght-lang-badge[title="JA"] {
            background: linear-gradient(135deg, #e83e8c, #d63384);
        }
        </style>
        <?php
    }
}
