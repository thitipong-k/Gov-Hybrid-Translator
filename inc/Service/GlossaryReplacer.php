<?php
/**
 * Glossary Replacer Service Class
 * 
 * จัดการ Pre-Translation & Post-Translation Glossary Replacement
 * 
 * Flow:
 * 1. Pre-translation: ค้นหาคำศัพท์เฉพาะจาก Glossary ในเนื้อหาภาษาไทยต้นฉบับ
 *    แล้วแทนที่ด้วย Placeholder (เช่น {{GLOSSARY_0}}, {{GLOSSARY_1}})
 * 2. AI Translation: ส่งเนื้อหาที่มี Placeholder ให้ AI แปล (AI จะไม่แปล Placeholder)
 * 3. Post-translation: นำคำแปลตามภาษาเป้าหมายจาก Glossary กลับมาใส่แทน Placeholder
 * 
 * @package GovHybridTranslator
 * @since 2.5.0
 */

namespace GovHybridTranslator\Service;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

class GlossaryReplacer {

    /**
     * @var string Cache key prefix
     */
    const CACHE_KEY_PREFIX = 'ght_glossary_terms_';

    /**
     * ดึงคำศัพท์ทั้งหมดจาก Glossary พร้อมคำแปลตามภาษาเป้าหมาย
     * 
     * เรียงลำดับคำจากยาวไปสั้น เพื่อให้แทนที่คำที่ยาวกว่าก่อน (ป้องกันคำสั้นไปตัดคำยาว)
     * 
     * @param string $target_lang ภาษาเป้าหมาย (เช่น 'en', 'zh', 'ja')
     * @return array Map ของ ['คำภาษาไทย' => 'คำแปลภาษาเป้าหมาย']
     */
    public function get_terms($target_lang = 'en') {
        $cache_key = self::CACHE_KEY_PREFIX . $target_lang;
        $cached_terms = wp_cache_get($cache_key, 'gov_hybrid_translator');

        if ($cached_terms !== false && is_array($cached_terms)) {
            return $cached_terms;
        }

        // ดึง Glossary Posts ทั้งหมด
        $args = [
            'post_type'      => 'gov_glossary',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ];
        
        $posts = get_posts($args);

        if (empty($posts)) {
            wp_cache_set($cache_key, [], 'gov_hybrid_translator', 300);
            return [];
        }

        $terms_map = [];

        foreach ($posts as $post) {
            $source_word = trim($post->post_title); // คำต้นฉบับภาษาไทย
            if (empty($source_word)) {
                continue;
            }

            // ดึงคำแปลตามภาษาเป้าหมาย
            $meta_key = '_gov_glossary_' . $target_lang . '_term';
            $target_word = get_post_meta($post->ID, $meta_key, true);

            // Fallback 1: ถ้าไม่ใช่ EN และไม่มีคำแปลภาษาเป้าหมาย ให้ลองดึงภาษาอังกฤษก่อน
            if (empty($target_word) && $target_lang !== 'en') {
                $target_word = get_post_meta($post->ID, '_gov_glossary_en_term', true);
            }

            // Fallback 2: ใช้ post_content
            if (empty($target_word)) {
                $target_word = trim($post->post_content);
            }

            if (!empty($target_word)) {
                $terms_map[$source_word] = $target_word;
            }
        }

        // เรียงลำดับจากคำที่ยาวที่สุดไปสั้นที่สุด (Prevent partial match issues)
        uksort($terms_map, function($a, $b) {
            return mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8');
        });

        wp_cache_set($cache_key, $terms_map, 'gov_hybrid_translator', 300);

        return $terms_map;
    }

    /**
     * ซ่อนคำ Glossary ในเนื้อหาภาษาไทยก่อนส่งให้ AI แปล (Pre-translation Protection)
     * 
     * แทนที่คำภาษาไทยใน text content (ไม่อยู่ใน HTML tags) ด้วย {{GLOSSARY_0}}, {{GLOSSARY_1}}
     * 
     * @param string $content เนื้อหาภาษาไทยต้นฉบับ
     * @param string $target_lang ภาษาเป้าหมาย
     * @return array ['protected_content' => string, 'map' => array]
     */
    public function protect_glossary_terms($content, $target_lang = 'en') {
        if (empty($content)) {
            return ['protected_content' => '', 'map' => []];
        }

        $terms_map = $this->get_terms($target_lang);

        if (empty($terms_map)) {
            return ['protected_content' => $content, 'map' => []];
        }

        $placeholder_map = [];
        $i = 0;

        // แยก HTML Tags ออกจาก Text Content เพื่อไม่ให้แทนที่ใน HTML Attribute/Tag Name
        $parts = preg_split('/(<[^>]*>)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($parts as &$part) {
            // ข้ามส่วนที่เป็น HTML Tag (<...>)
            if (strpos($part, '<') === 0 && substr($part, -1) === '>') {
                continue;
            }

            // แทนที่คำไทยใน Text Node
            foreach ($terms_map as $source_word => $target_word) {
                if (mb_strpos($part, $source_word) !== false) {
                    $placeholder = '{{GLOSSARY_' . $i . '}}';
                    
                    // บันทึกการ map ระหว่าง Placeholder -> คำแปลจาก Glossary
                    $placeholder_map[$placeholder] = $target_word;

                    // แทนที่คำไทยด้วย Placeholder (Case-insensitive & Unicode regex)
                    $pattern = '/' . preg_quote($source_word, '/') . '/ui';
                    $part = preg_replace($pattern, $placeholder, $part);
                    
                    $i++;
                }
            }
        }

        $protected_content = implode('', $parts);

        return [
            'protected_content' => $protected_content,
            'map'               => $placeholder_map,
        ];
    }

    /**
     * คืนคำแปลจาก Glossary กลับเข้าสู่เนื้อหาหลัง AI แปลเสร็จ (Post-translation Restoration)
     * 
     * @param string $translated_content เนื้อหาที่ผ่านการแปลจาก AI
     * @param array $placeholder_map Map ของ [{{GLOSSARY_X}} => 'คำแปล']
     * @return string เนื้อหาที่มีคำจาก Glossary ถูกต้องครบถ้วน
     */
    public function restore_glossary_terms($translated_content, $placeholder_map) {
        if (empty($translated_content) || empty($placeholder_map)) {
            return $translated_content;
        }

        // คืนคำแปลจาก Glossary เข้าไปแทนที่ Placeholders
        return str_replace(array_keys($placeholder_map), array_values($placeholder_map), $translated_content);
    }

    /**
     * สร้าง Hash Key สำหรับ Glossary Version เพื่อใช้ในการทำ Cache Invalidation
     * 
     * @return string MD5 Hash
     */
    public function get_glossary_hash() {
        $posts = get_posts([
            'post_type'      => 'gov_glossary',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
        ]);

        $dates = [];
        foreach ($posts as $id) {
            $dates[] = get_the_modified_date('Y-m-d H:i:s', $id);
        }

        return md5(implode('|', $dates));
    }

    /**
     * ล้าง Cache ของ Glossary terms ทั้งหมด
     */
    public static function clear_cache() {
        wp_cache_delete_group('gov_hybrid_translator');
    }
}
