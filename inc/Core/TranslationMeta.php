<?php
/**
 * Translation Meta Class
 * 
 * จัดการ CRUD สำหรับเก็บคำแปล Posts/Pages ใน post_meta
 * ไม่สร้าง post ใหม่ แต่เก็บ title และ content แปลใน meta
 * 
 * Meta Keys:
 * - _ght_title_{lang}: ชื่อแปล เช่น _ght_title_en
 * - _ght_content_{lang}: เนื้อหาแปล เช่น _ght_content_en
 * - _ght_excerpt_{lang}: คำอธิบายย่อ เช่น _ght_excerpt_en
 * - _ght_status_{lang}: สถานะ (draft, published)
 * - _ght_translated_at_{lang}: วันที่แปล
 * 
 * @package GovHybridTranslator
 * @since 1.7.0
 */
namespace GovHybridTranslator\Core;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

class TranslationMeta {

    /**
     * Prefix สำหรับ meta keys
     */
    const PREFIX = '_ght_';

    /**
     * บันทึกคำแปลสำหรับ Post/Page
     * 
     * @param int $post_id Post ID
     * @param string $lang รหัสภาษา (en, zh, ja)
     * @param string $title ชื่อแปล
     * @param string $content เนื้อหาแปล
     * @param string $excerpt คำอธิบายย่อ (optional)
     * @param string $status สถานะ (draft, published) Default: published
     * @return bool สำเร็จหรือไม่
     */
    public static function save($post_id, $lang, $title, $content, $excerpt = '', $status = 'published') {
        if (empty($post_id) || empty($lang)) {
            return false;
        }

        $post_id = intval($post_id);
        $lang = sanitize_text_field($lang);
        $status = in_array($status, ['draft', 'published']) ? $status : 'published';

        // ลบ meta เก่าก่อน แล้วค่อย add ใหม่ (แก้ปัญหา update_post_meta returns false)
        $title_key = self::PREFIX . 'title_' . $lang;
        $content_key = self::PREFIX . 'content_' . $lang;
        $excerpt_key = self::PREFIX . 'excerpt_' . $lang;
        $status_key = self::PREFIX . 'status_' . $lang;
        $time_key = self::PREFIX . 'translated_at_' . $lang;

        // ลบ meta เก่า
        delete_post_meta($post_id, $title_key);
        delete_post_meta($post_id, $content_key);
        delete_post_meta($post_id, $status_key); // ลบสถานะเก่า
        delete_post_meta($post_id, $time_key);

        // เพิ่ม meta ใหม่
        $title_result = add_post_meta($post_id, $title_key, $title, true);
        $content_result = add_post_meta($post_id, $content_key, $content, true);
        add_post_meta($post_id, $status_key, $status, true); // บันทึกสถานะใหม่
        
        // บันทึก excerpt ถ้ามี
        if (!empty($excerpt)) {
            delete_post_meta($post_id, $excerpt_key);
            add_post_meta($post_id, $excerpt_key, $excerpt, true);
        }

        // บันทึกเวลาที่แปล
        add_post_meta($post_id, $time_key, current_time('mysql'), true);

        // ตรวจสอบว่าบันทึกสำเร็จหรือไม่
        // ตรวจสอบว่าบันทึกสำเร็จหรือไม่
        $saved_title = get_post_meta($post_id, $title_key, true);
        
        if (!empty($saved_title)) {
            // Update Post Modified Date to reflect the translation change
            // อัปเดตวันที่แก้ไขของ Post เพื่อให้ "Recent Translations" แสดงผลถูกต้อง (ดันขึ้นบนสุด)
            wp_update_post([
                'ID' => $post_id,
                'post_modified' => current_time('mysql'),
                'post_modified_gmt' => current_time('mysql', 1)
            ]);
            
            return true;
        }
        
        return false;
    }


    /**
     * ดึงคำแปลทั้งหมดสำหรับภาษาที่กำหนด
     * 
     * @param int $post_id Post ID
     * @param string $lang รหัสภาษา
     * @return array|null [title, content, excerpt, translated_at] หรือ null ถ้าไม่มี
     */
    public static function get($post_id, $lang) {
        $title = get_post_meta($post_id, self::PREFIX . 'title_' . $lang, true);
        
        if (empty($title)) {
            return null;
        }

        return [
            'title' => $title,
            'content' => get_post_meta($post_id, self::PREFIX . 'content_' . $lang, true),
            'excerpt' => get_post_meta($post_id, self::PREFIX . 'excerpt_' . $lang, true),
            'status' => get_post_meta($post_id, self::PREFIX . 'status_' . $lang, true) ?: 'published', // Default to published for old data
            'translated_at' => get_post_meta($post_id, self::PREFIX . 'translated_at_' . $lang, true),
        ];
    }

    /**
     * ดึง title แปล
     * 
     * @param int $post_id Post ID
     * @param string $lang รหัสภาษา
     * @return string|null title หรือ null ถ้าไม่มี
     */
    public static function get_title($post_id, $lang) {
        $title = get_post_meta($post_id, self::PREFIX . 'title_' . $lang, true);
        return !empty($title) ? $title : null;
    }

    /**
     * ดึง content แปล
     * 
     * @param int $post_id Post ID
     * @param string $lang รหัสภาษา
     * @return string|null content หรือ null ถ้าไม่มี
     */
    public static function get_content($post_id, $lang) {
        $content = get_post_meta($post_id, self::PREFIX . 'content_' . $lang, true);
        return !empty($content) ? $content : null;
    }

    /**
     * ดึง excerpt แปล
     * 
     * @param int $post_id Post ID
     * @param string $lang รหัสภาษา
     * @return string|null excerpt หรือ null ถ้าไม่มี
     */
    public static function get_excerpt($post_id, $lang) {
        $excerpt = get_post_meta($post_id, self::PREFIX . 'excerpt_' . $lang, true);
        return !empty($excerpt) ? $excerpt : null;
    }

    /**
     * ดึงรายการภาษาที่มีคำแปลแล้ว
     * 
     * @param int $post_id Post ID
     * @return array รายการรหัสภาษา เช่น ['en', 'zh']
     */
    public static function get_languages($post_id) {
        global $wpdb;
        
        $results = $wpdb->get_col($wpdb->prepare(
            "SELECT meta_key FROM {$wpdb->postmeta} 
             WHERE post_id = %d 
             AND meta_key LIKE %s",
            $post_id,
            self::PREFIX . 'title_%'
        ));

        $languages = [];
        foreach ($results as $meta_key) {
            // ดึงรหัสภาษาจาก meta_key เช่น _ght_title_en => en
            $lang = str_replace(self::PREFIX . 'title_', '', $meta_key);
            if (!empty($lang)) {
                $languages[] = $lang;
            }
        }

        return $languages;
    }

    /**
     * ตรวจสอบว่ามีคำแปลสำหรับภาษาที่กำหนดหรือไม่
     * 
     * @param int $post_id Post ID
     * @param string $lang รหัสภาษา
     * @return bool
     */
    public static function has_translation($post_id, $lang) {
        $title = get_post_meta($post_id, self::PREFIX . 'title_' . $lang, true);
        return !empty($title);
    }

    /**
     * ลบคำแปลสำหรับภาษาที่กำหนด
     * 
     * @param int $post_id Post ID
     * @param string $lang รหัสภาษา
     * @return bool
     */
    public static function delete($post_id, $lang) {
        delete_post_meta($post_id, self::PREFIX . 'title_' . $lang);
        delete_post_meta($post_id, self::PREFIX . 'content_' . $lang);
        delete_post_meta($post_id, self::PREFIX . 'excerpt_' . $lang);
        delete_post_meta($post_id, self::PREFIX . 'status_' . $lang);
        delete_post_meta($post_id, self::PREFIX . 'translated_at_' . $lang);
        
        return true;
    }

    /**
     * ลบคำแปลทุกภาษาสำหรับ Post
     * 
     * @param int $post_id Post ID
     * @return bool
     */
    public static function delete_all($post_id) {
        global $wpdb;
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->postmeta} 
             WHERE post_id = %d 
             AND meta_key LIKE %s",
            $post_id,
            self::PREFIX . '%'
        ));

        return true;
    }
}
