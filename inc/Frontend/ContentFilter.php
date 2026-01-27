<?php
/**
 * Content Filter Class
 * 
 * Filter WordPress hooks เพื่อแสดง content ตามภาษาที่เลือก
 * ใช้ข้อมูลจาก TranslationMeta
 * 
 * Hooks ที่ filter:
 * - the_title: แสดง title ตามภาษา
 * - the_content: แสดง content ตามภาษา
 * - the_excerpt: แสดง excerpt ตามภาษา
 * - blogname: แสดงชื่อเว็บไซต์ตามภาษา (หัวเว็บ)
 * - blogdescription: แสดง tagline ตามภาษา
 * - widget_title: แสดงชื่อ widget ตามภาษา
 * 
 * @package GovHybridTranslator
 * @since 1.7.0
 * @updated 2.0.0 - เพิ่ม blogname, blogdescription, widget_title filters
 */
namespace GovHybridTranslator\Frontend;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Core\TranslationMeta;

class ContentFilter {

    /**
     * ลงทะเบียน hooks
     */
    public function register() {
        // Filter title
        add_filter('the_title', [$this, 'filter_title'], 10, 2);
        
        // Filter content
        add_filter('the_content', [$this, 'filter_content'], 10, 1);
        
        // Filter excerpt
        add_filter('the_excerpt', [$this, 'filter_excerpt'], 10, 1);
        add_filter('get_the_excerpt', [$this, 'filter_get_excerpt'], 10, 2);
        
        // === Header Translation Filters ===
        // Filter bloginfo() ซึ่ง themes ส่วนใหญ่ใช้
        add_filter('bloginfo', [$this, 'filter_bloginfo'], 10, 2);
        
        // Filter option ด้วย (สำหรับ themes ที่ใช้ get_option โดยตรง)
        add_filter('option_blogname', [$this, 'filter_blogname'], 10, 1);
        add_filter('option_blogdescription', [$this, 'filter_blogdescription'], 10, 1);
        
        // Filter Widget Title
        add_filter('widget_title', [$this, 'filter_widget_title'], 10, 3);
    }

    /**
     * Filter bloginfo() - ใช้โดย themes ส่วนใหญ่
     * 
     * @param string $output ค่า bloginfo
     * @param string $show ชื่อ parameter (name, description, etc.)
     * @return string ค่าที่แปล (ถ้ามี)
     */
    public function filter_bloginfo($output, $show) {
        // แก้ไข null สำหรับ PHP 8.1+
        $output = $output ?? '';
        
        // ข้าม admin
        if (is_admin() && !wp_doing_ajax()) {
            return $output;
        }

        $lang = LanguageSwitcher::get_current_language();
        if ($lang === 'th') {
            return $output;
        }

        // แปลตาม parameter
        switch ($show) {
            case 'name':
                $translated = get_option('ght_blogname_' . $lang, '');
                return !empty($translated) ? $translated : $output;
                
            case 'description':
                $translated = get_option('ght_blogdescription_' . $lang, '');
                return !empty($translated) ? $translated : $output;
        }

        return $output;
    }

    /**
     * Filter ชื่อเว็บไซต์ (blogname)
     * 
     * @param string $blogname ชื่อเว็บไซต์ต้นฉบับ
     * @return string ชื่อที่แปล (ถ้ามี)
     */
    public function filter_blogname($blogname) {
        // แก้ไข null สำหรับ PHP 8.1+
        $blogname = $blogname ?? '';
        
        // ข้าม admin
        if (is_admin() && !wp_doing_ajax()) {
            return $blogname;
        }

        $lang = LanguageSwitcher::get_current_language();
        if ($lang === 'th') {
            return $blogname;
        }

        // ดึงชื่อเว็บที่แปล (เก็บใน option)
        $translated = get_option('ght_blogname_' . $lang, '');
        return !empty($translated) ? $translated : $blogname;
    }

    /**
     * Filter pre_option_blogname (เรียกก่อน option_blogname)
     * Return false เพื่อให้ WordPress ดึงค่าจาก database แล้วผ่าน option_blogname filter
     */
    public function filter_blogname_pre($pre) {
        // คืนค่า false เพื่อให้ WP ดึงจาก DB แล้วใช้ option_blogname filter
        return false;
    }

    /**
     * Filter Tagline (blogdescription)
     */
    public function filter_blogdescription($tagline) {
        // แก้ไข null สำหรับ PHP 8.1+
        $tagline = $tagline ?? '';
        
        if (is_admin() && !wp_doing_ajax()) {
            return $tagline;
        }

        $lang = LanguageSwitcher::get_current_language();
        if ($lang === 'th') {
            return $tagline;
        }

        $translated = get_option('ght_blogdescription_' . $lang, '');
        return !empty($translated) ? $translated : $tagline;
    }

    /**
     * Filter pre_option_blogdescription
     */
    public function filter_blogdescription_pre($pre) {
        return false;
    }

    /**
     * Filter Widget Title
     * 
     * @param string $title Widget title
     * @param array $instance Widget instance
     * @param string $id_base Widget ID base
     * @return string Title ที่แปล (ถ้ามี)
     */
    public function filter_widget_title($title, $instance = [], $id_base = '') {
        // แก้ไข null สำหรับ PHP 8.1+
        $title = $title ?? '';
        
        if (is_admin() && !wp_doing_ajax()) {
            return $title;
        }

        $lang = LanguageSwitcher::get_current_language();
        if ($lang === 'th' || empty($title)) {
            return $title;
        }

        // ดึง widget title ที่แปล (ถ้ามี)
        // สร้าง key จาก title hash
        $key = 'ght_widget_' . $lang . '_' . md5($title);
        $translated = get_option($key, '');
        
        return !empty($translated) ? $translated : $title;
    }


    /**
     * Filter the_title
     * 
     * @param string $title Title เดิม
     * @param int $post_id Post ID
     * @return string Title ที่แปล (ถ้ามี)
     */
    public function filter_title($title, $post_id = 0) {
        // แก้ไข null สำหรับ PHP 8.1+
        $title = $title ?? '';
        
        // ข้าม admin area (ยกเว้น AJAX)
        if (is_admin() && !wp_doing_ajax()) {
            return $title;
        }

        // ดึงภาษาปัจจุบัน
        $lang = LanguageSwitcher::get_current_language();
        
        // ถ้าเป็นภาษาต้นฉบับ (ไทย) ไม่ต้องแปล
        if ($lang === 'th') {
            return $title;
        }

        // ดึง title แปลจาก meta
        $translated_title = TranslationMeta::get_title($post_id, $lang);
        
        // === Check Status (Draft/Published) ===
        // ตรวจสอบสถานะการแปล (Feature: Advanced Workflow)
        // ถ้าเป็น Draft และไม่ใช่ Admin ให้แสดงภาษาเดิม (ป้องกัน User ทั่วไปเห็นเนื้อหาที่ยังไม่ approved)
        // Admin จะยังเห็นได้เพื่อการตรวจสอบ (Preview)
        $status = get_post_meta($post_id, '_ght_status_' . $lang, true) ?: 'published';
        if ($status === 'draft' && !current_user_can('manage_options')) {
            return $title;
        }

        return !empty($translated_title) ? $translated_title : $title;
    }

    /**
     * Filter the_content
     * 
     * @param string $content Content เดิม
     * @return string Content ที่แปล (ถ้ามี)
     */
    public function filter_content($content) {
        // แก้ไข null สำหรับ PHP 8.1+
        $content = $content ?? '';
        
        // ข้าม admin area
        if (is_admin() && !wp_doing_ajax()) {
            return $content;
        }
        
        // === Skip when Fusion is rendering template sections (header/footer) ===
        // เพื่อป้องกัน content ซ้ำเมื่อ render header/footer templates
        if (class_exists('Fusion_Template_Builder')) {
            $builder = \Fusion_Template_Builder::get_instance();
            $current_override = $builder->get_current_override_name();
            if ($current_override && in_array($current_override, ['header', 'footer', 'page_title_bar'], true)) {
                return $content;
            }
        }

        // ดึง Post ID
        $post_id = get_the_ID();
        if (!$post_id) {
            return $content;
        }
        
        // === Skip Fusion template sections (fusion_tb_section) ===
        // ป้องกันการแทนที่ content ของ template sections
        $post_type = get_post_type($post_id);
        if ($post_type === 'fusion_tb_section' || $post_type === 'fusion_tb_layout') {
            return $content;
        }

        // ดึงภาษาปัจจุบัน
        $lang = LanguageSwitcher::get_current_language();
        
        // ถ้าเป็นภาษาต้นฉบับ (ไทย) ไม่ต้องแปล
        if ($lang === 'th') {
            return $content;
        }

        // ดึง content แปลจาก meta
        $translated_content = TranslationMeta::get_content($post_id, $lang);
        
        // === Check Status (Draft/Published) ===
        // ตรวจสอบสถานะการแปล (Feature: Advanced Workflow)
        // ถ้าเป็น Draft และไม่ใช่ Admin ให้แสดงภาษาเดิม (ป้องกัน User ทั่วไปเห็นเนื้อหาที่ยังไม่ approved)
        $status = get_post_meta($post_id, '_ght_status_' . $lang, true) ?: 'published';
        if ($status === 'draft' && !current_user_can('manage_options')) {
            return $content;
        }

        return !empty($translated_content) ? $translated_content : $content;
    }

    /**
     * Filter the_excerpt
     * 
     * @param string $excerpt Excerpt เดิม
     * @return string Excerpt ที่แปล (ถ้ามี)
     */
    public function filter_excerpt($excerpt) {
        // แก้ไข null สำหรับ PHP 8.1+
        $excerpt = $excerpt ?? '';
        
        // ข้าม admin area
        if (is_admin() && !wp_doing_ajax()) {
            return $excerpt;
        }

        // ดึง Post ID
        $post_id = get_the_ID();
        if (!$post_id) {
            return $excerpt;
        }

        // ดึงภาษาปัจจุบัน
        $lang = LanguageSwitcher::get_current_language();
        
        // ถ้าเป็นภาษาต้นฉบับ (ไทย) ไม่ต้องแปล
        if ($lang === 'th') {
            return $excerpt;
        }

        // ดึง excerpt แปลจาก meta
        $translated_excerpt = TranslationMeta::get_excerpt($post_id, $lang);
        
        // === Check Status (Draft/Published) ===
        // ถ้าเป็น Draft และไม่ใช่ Admin ให้แสดงภาษาเดิม
        $status = get_post_meta($post_id, '_ght_status_' . $lang, true) ?: 'published';
        if ($status === 'draft' && !current_user_can('manage_options')) {
            return $excerpt;
        }

        return !empty($translated_excerpt) ? $translated_excerpt : $excerpt;
    }

    /**
     * Filter get_the_excerpt
     * 
     * @param string $excerpt Excerpt เดิม
     * @param WP_Post $post Post object
     * @return string Excerpt ที่แปล (ถ้ามี)
     */
    public function filter_get_excerpt($excerpt, $post) {
        // แก้ไข null สำหรับ PHP 8.1+
        $excerpt = $excerpt ?? '';
        
        // ข้าม admin area
        if (is_admin() && !wp_doing_ajax()) {
            return $excerpt;
        }

        if (!$post) {
            return $excerpt;
        }

        // ดึงภาษาปัจจุบัน
        $lang = LanguageSwitcher::get_current_language();
        
        // ถ้าเป็นภาษาต้นฉบับ (ไทย) ไม่ต้องแปล
        if ($lang === 'th') {
            return $excerpt;
        }

        // ดึง excerpt แปลจาก meta
        $translated_excerpt = TranslationMeta::get_excerpt($post->ID, $lang);
        
        // === Check Status (Draft/Published) ===
        // ถ้าเป็น Draft และไม่ใช่ Admin ให้แสดงภาษาเดิม
        $status = get_post_meta($post->ID, '_ght_status_' . $lang, true) ?: 'published';
        if ($status === 'draft' && !current_user_can('manage_options')) {
            return $excerpt;
        }
        
        return !empty($translated_excerpt) ? $translated_excerpt : $excerpt;
    }
}
