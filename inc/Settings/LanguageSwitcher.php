<?php
/**
 * Language Switcher Settings Handler
 * 
 * จัดการ settings สำหรับปุ่มสลับภาษา
 * รวม defaults, sanitization, และ keys ทั้งหมดไว้ใน class เดียว
 * 
 * @package GovHybridTranslator
 * @since 1.0.0
 * @modified 1.3.0 - เพิ่ม floating_position, button_content, layout_type, top_offset
 */
namespace GovHybridTranslator\Settings;

// ป้องกันการเข้าถึงไฟล์โดยตรง
if (!defined('ABSPATH')) exit;

class LanguageSwitcher {

    /**
     * ค่า Default สำหรับ Language Switcher Settings
     * 
     * รายการ Settings:
     * - switcher_type: ประเภทการแสดง (floating/menu/shortcode)
     * - show_flags: แสดงธงชาติ
     * - show_names: แสดงชื่อภาษา
     * - placement: ตำแหน่งที่วาง
     * - remember_preference: จำการเลือกของผู้ใช้
     * - auto_redirect: เปลี่ยนภาษาอัตโนมัติ
     * - floating_position: ตำแหน่งปุ่มลอย (top-right/center-right/bottom-right)
     * - button_content: เนื้อหาปุ่ม (both/flag_only/text_only)
     * - layout_type: รูปแบบปุ่ม (single/dual)
     * - top_offset: ระยะห่างจากด้านบน (pixels)
     *
     * @return array ค่า defaults ทั้งหมด
     */
    public function get_defaults() {
        return [
            // === การตั้งค่าพื้นฐาน ===
            'switcher_type' => 'flags',    // ประเภท: dropdown, flags, flag_pair, text
            'show_flags' => true,          // แสดงธงชาติ
            'show_names' => true,          // แสดงชื่อภาษา
            'show_source_lang' => true,    // แสดงภาษาหลัก (ภาษาไทย)
            'placement' => ['floating', 'menu'], // ตำแหน่งที่แสดง
            'remember_preference' => true, // จำการเลือกภาษาของผู้ใช้
            'auto_redirect' => false,      // เปลี่ยนภาษาอัตโนมัติตาม browser

            // === การตั้งค่า Floating Button ===
            'floating_position' => 'bottom-right', // ตำแหน่ง: top-left, top-right, bottom-left, bottom-right
            'floating_margin_x' => 20,     // ระยะห่างแนวนอน (pixels)
            'floating_margin_y' => 20,     // ระยะห่างแนวตั้ง (pixels)
            'button_content' => 'both',    // เนื้อหา: both (ธง+ชื่อ), flag_only (ธงเท่านั้น), text_only (ชื่อเท่านั้น)
            'layout_type' => 'single',     // รูปแบบ: single (ปุ่มเดียว สลับภาษา), dual (สองปุ่ม TH|EN)
            'top_offset' => 0,             // ระยะห่างจากด้านบน 0-300 pixels (สำหรับ fixed header)
        ];
    }

    /**
     * Sanitize Language Switcher Settings
     * 
     * ทำความสะอาดข้อมูลก่อนบันทึกลง database
     * ป้องกัน XSS และ SQL Injection
     *
     * @param array $settings ข้อมูลดิบจาก form
     * @return array ข้อมูลที่ sanitize แล้ว
     */
    public function sanitize($settings) {
        $sanitized = [];

        // === ตั้งค่าพื้นฐาน ===
        
        // ประเภทการแสดง (text)
        $sanitized['switcher_type'] = isset($settings['switcher_type']) 
            ? sanitize_text_field($settings['switcher_type']) 
            : 'floating';

        // แสดงธงชาติ (boolean)
        $sanitized['show_flags'] = isset($settings['show_flags']) 
            ? (bool)$settings['show_flags'] 
            : true;

        // แสดงชื่อภาษา (boolean)
        $sanitized['show_names'] = isset($settings['show_names']) 
            ? (bool)$settings['show_names'] 
            : true;

        // ตำแหน่งที่แสดง (array)
        $sanitized['placement'] = isset($settings['placement']) 
            ? array_map('sanitize_text_field', (array)$settings['placement']) 
            : ['floating', 'menu'];

        // จำการเลือกภาษา (boolean)
        $sanitized['remember_preference'] = isset($settings['remember_preference']) 
            ? (bool)$settings['remember_preference'] 
            : true;

        // เปลี่ยนภาษาอัตโนมัติ (boolean)
        $sanitized['auto_redirect'] = isset($settings['auto_redirect']) 
            ? (bool)$settings['auto_redirect'] 
            : false;

        // แสดงภาษาหลัก (boolean)
        $sanitized['show_source_lang'] = isset($settings['show_source_lang']) 
            ? (bool)$settings['show_source_lang'] 
            : true;

        // === ตั้งค่า Floating Button ===
        
        // ตำแหน่งปุ่มลอย (text)
        $sanitized['floating_position'] = isset($settings['floating_position']) 
            ? sanitize_text_field($settings['floating_position']) 
            : 'bottom-right';

        // ระยะห่างแนวนอน (integer, 0-100)
        $sanitized['floating_margin_x'] = isset($settings['floating_margin_x']) 
            ? absint($settings['floating_margin_x']) 
            : 20;

        // ระยะห่างแนวตั้ง (integer, 0-100)
        $sanitized['floating_margin_y'] = isset($settings['floating_margin_y']) 
            ? absint($settings['floating_margin_y']) 
            : 20;

        // เนื้อหาปุ่ม (text)
        $sanitized['button_content'] = isset($settings['button_content']) 
            ? sanitize_text_field($settings['button_content']) 
            : 'both';

        // รูปแบบปุ่ม (text)
        $sanitized['layout_type'] = isset($settings['layout_type']) 
            ? sanitize_text_field($settings['layout_type']) 
            : 'single';

        // ระยะห่างจากด้านบน (integer, 0-300)
        $sanitized['top_offset'] = isset($settings['top_offset']) 
            ? absint($settings['top_offset']) 
            : 0;

        return $sanitized;
    }

    /**
     * รายการ Keys ทั้งหมดที่จัดการโดย class นี้
     * 
     * ใช้สำหรับ loop หรือ validation
     *
     * @return array รายการ setting keys
     */
    public function get_keys() {
        return [
            'switcher_type',       // ประเภทการแสดง
            'show_flags',          // แสดงธงชาติ
            'show_names',          // แสดงชื่อภาษา
            'show_source_lang',    // แสดงภาษาหลัก
            'placement',           // ตำแหน่งที่แสดง
            'remember_preference', // จำการเลือกภาษา
            'auto_redirect',       // เปลี่ยนภาษาอัตโนมัติ
            'floating_position',   // ตำแหน่งปุ่มลอย
            'floating_margin_x',   // ระยะห่างแนวนอน
            'floating_margin_y',   // ระยะห่างแนวตั้ง
            'button_content',      // เนื้อหาปุ่ม
            'layout_type',         // รูปแบบปุ่ม
            'top_offset',          // ระยะห่างจากด้านบน
        ];
    }
}
