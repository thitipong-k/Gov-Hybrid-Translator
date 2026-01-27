<?php
/**
 * Term Translation Meta Class
 * 
 * จัดการ CRUD สำหรับเก็บคำแปล Categories/Tags ใน term_meta
 * ไม่สร้าง term ใหม่ แต่เก็บ name และ description แปลใน meta
 * 
 * Meta Keys:
 * - _ght_name_{lang}: ชื่อแปล เช่น _ght_name_en
 * - _ght_description_{lang}: คำอธิบายแปล เช่น _ght_description_en
 * - _ght_translated_at_{lang}: วันที่แปล
 * 
 * @package GovHybridTranslator
 * @since 1.7.0
 */
namespace GovHybridTranslator\Core;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

class TermTranslationMeta {

    /**
     * Prefix สำหรับ meta keys
     */
    const PREFIX = '_ght_';

    /**
     * บันทึกคำแปลสำหรับ Term (Category/Tag)
     * 
     * @param int $term_id Term ID
     * @param string $lang รหัสภาษา (en, zh, ja)
     * @param string $name ชื่อแปล
     * @param string $description คำอธิบายแปล (optional)
     * @return bool สำเร็จหรือไม่
     */
    public static function save($term_id, $lang, $name, $description = '') {
        if (empty($term_id) || empty($lang)) {
            return false;
        }

        // บันทึก name
        update_term_meta($term_id, self::PREFIX . 'name_' . $lang, $name);
        
        // บันทึก description ถ้ามี
        if (!empty($description)) {
            update_term_meta($term_id, self::PREFIX . 'description_' . $lang, $description);
        }

        // บันทึกเวลาที่แปล
        update_term_meta($term_id, self::PREFIX . 'translated_at_' . $lang, current_time('mysql'));

        return true;
    }

    /**
     * ดึงคำแปลทั้งหมดสำหรับภาษาที่กำหนด
     * 
     * @param int $term_id Term ID
     * @param string $lang รหัสภาษา
     * @return array|null [name, description, translated_at] หรือ null ถ้าไม่มี
     */
    public static function get($term_id, $lang) {
        $name = get_term_meta($term_id, self::PREFIX . 'name_' . $lang, true);
        
        if (empty($name)) {
            return null;
        }

        return [
            'name' => $name,
            'description' => get_term_meta($term_id, self::PREFIX . 'description_' . $lang, true),
            'translated_at' => get_term_meta($term_id, self::PREFIX . 'translated_at_' . $lang, true),
        ];
    }

    /**
     * ดึง name แปล
     * 
     * @param int $term_id Term ID
     * @param string $lang รหัสภาษา
     * @return string|null name หรือ null ถ้าไม่มี
     */
    public static function get_name($term_id, $lang) {
        $name = get_term_meta($term_id, self::PREFIX . 'name_' . $lang, true);
        return !empty($name) ? $name : null;
    }

    /**
     * ดึง description แปล
     * 
     * @param int $term_id Term ID
     * @param string $lang รหัสภาษา
     * @return string|null description หรือ null ถ้าไม่มี
     */
    public static function get_description($term_id, $lang) {
        $description = get_term_meta($term_id, self::PREFIX . 'description_' . $lang, true);
        return !empty($description) ? $description : null;
    }

    /**
     * ดึงรายการภาษาที่มีคำแปลแล้ว
     * 
     * @param int $term_id Term ID
     * @return array รายการรหัสภาษา เช่น ['en', 'zh']
     */
    public static function get_languages($term_id) {
        global $wpdb;
        
        $results = $wpdb->get_col($wpdb->prepare(
            "SELECT meta_key FROM {$wpdb->termmeta} 
             WHERE term_id = %d 
             AND meta_key LIKE %s",
            $term_id,
            self::PREFIX . 'name_%'
        ));

        $languages = [];
        foreach ($results as $meta_key) {
            // ดึงรหัสภาษาจาก meta_key เช่น _ght_name_en => en
            $lang = str_replace(self::PREFIX . 'name_', '', $meta_key);
            if (!empty($lang)) {
                $languages[] = $lang;
            }
        }

        return $languages;
    }

    /**
     * ตรวจสอบว่ามีคำแปลสำหรับภาษาที่กำหนดหรือไม่
     * 
     * @param int $term_id Term ID
     * @param string $lang รหัสภาษา
     * @return bool
     */
    public static function has_translation($term_id, $lang) {
        $name = get_term_meta($term_id, self::PREFIX . 'name_' . $lang, true);
        return !empty($name);
    }

    /**
     * ลบคำแปลสำหรับภาษาที่กำหนด
     * 
     * @param int $term_id Term ID
     * @param string $lang รหัสภาษา
     * @return bool
     */
    public static function delete($term_id, $lang) {
        delete_term_meta($term_id, self::PREFIX . 'name_' . $lang);
        delete_term_meta($term_id, self::PREFIX . 'description_' . $lang);
        delete_term_meta($term_id, self::PREFIX . 'translated_at_' . $lang);
        
        return true;
    }

    /**
     * ลบคำแปลทุกภาษาสำหรับ Term
     * 
     * @param int $term_id Term ID
     * @return bool
     */
    public static function delete_all($term_id) {
        global $wpdb;
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->termmeta} 
             WHERE term_id = %d 
             AND meta_key LIKE %s",
            $term_id,
            self::PREFIX . '%'
        ));

        return true;
    }
}
