<?php
/**
 * Content Reviewer Module
 * 
 * จัดการการตรวจสอบเนื้อหาและการแปลพร้อม Custom Terms
 * 
 * คุณสมบัติ:
 * - ดึงเนื้อหาพร้อมไฮไลท์คำศัพท์เฉพาะ
 * - แปลเนื้อหาพร้อมบันทึก Custom Terms ใหม่
 * - รองรับหลายภาษาเป้าหมาย
 * - ดึงเนื้อหาที่ยังแปลไม่ครบจัดกลุ่มตาม Category (v2.1.1)
 * 
 * @package GovHybridTranslator
 * @since 1.4.0
 * @modified 2.0.0 - เพิ่ม target language parameter
 * @modified 2.1.1 - เพิ่ม get_incomplete_translations_by_category()
 */
namespace GovHybridTranslator\Modules;

// ป้องกันการเข้าถึงไฟล์โดยตรง
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Integrations\Post;
use GovHybridTranslator\Service\AIService;
use GovHybridTranslator\Core\TranslationMeta;

class ContentReviewer {

    /**
     * Get untranslated pages.
     *
     * @param int $limit
     * @return array
     */
    public function get_untranslated_pages($limit = 20) {
        return $this->get_untranslated_content('page', $limit);
    }

    /**
     * Get untranslated posts.
     *
     * @param int $limit
     * @return array
     */
    public function get_untranslated_posts($limit = 20) {
        return $this->get_untranslated_content('post', $limit);
    }

    /**
     * Get untranslated content by type.
     *
     * @param string $post_type
     * @param int $limit
     * @return array
     */
    private function get_untranslated_content($post_type, $limit) {
        $args = [
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'meta_query' => [
                [
                    'key' => '_gov_translator_lang',
                    'compare' => 'NOT EXISTS' // Or check if it's NOT 'en'
                ]
            ]
        ];

        // Better logic: Get all Thai posts that don't have an English version linked
        // This is simplified; real logic might need to check group_id
        
        $query = new \WP_Query($args);
        $items = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                // Check if already translated (has English version in group)
                $group_id = get_post_meta($post_id, '_gov_translator_group_id', true);
                $has_translation = false;
                
                if ($group_id) {
                    $trans_query = new \WP_Query([
                        'post_type' => $post_type,
                        'meta_query' => [
                            ['key' => '_gov_translator_group_id', 'value' => $group_id],
                            ['key' => '_gov_translator_lang', 'value' => 'en']
                        ]
                    ]);
                    if ($trans_query->have_posts()) {
                        $has_translation = true;
                    }
                }

                if (!$has_translation) {
                    $items[] = [
                        'id' => $post_id,
                        'title' => get_the_title(),
                        'date' => get_the_date('Y-m-d'),
                        'excerpt' => wp_trim_words(get_the_content(), 20),
                    ];
                }
            }
            wp_reset_postdata();
        }

        return $items;
    }

    /**
     * ดึงเนื้อหาสำหรับ Review พร้อมคำศัพท์เฉพาะ
     *
     * @param int $post_id Post ID
     * @return array|WP_Error ข้อมูลเนื้อหาและคำศัพท์ที่พบ
     */
    public function get_content($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return new \WP_Error('not_found', 'ไม่พบเนื้อหา');
        }
        
        // === ดึงคำศัพท์จาก Glossary ===
        $glossary_manager = new GlossaryManager();
        $all_terms = $glossary_manager->get_glossary_terms(['posts_per_page' => -1]);
        
        $found_terms = [];
        $content = $post->post_content;
        
        // === ค้นหาคำศัพท์ในเนื้อหา ===
        if (!empty($all_terms['terms'])) {
            foreach ($all_terms['terms'] as $term) {
                // ตรวจสอบว่าคำศัพท์มีอยู่ในเนื้อหาหรือไม่
                $thai_term = $term['thai_term'];
                if (!empty($thai_term) && mb_strpos($content, $thai_term) !== false) {
                    // พบคำศัพท์ในเนื้อหา
                    $found_terms[] = [
                        'thai_term' => $thai_term,
                        'english_term' => $term['english_term'] ?? '',
                        'category' => $term['category'] ?? 'Other',
                        'id' => $term['id'] ?? 0,
                    ];
                }
            }
        }

        // Return content with formatting but raw enough for selection
        return [
            'content' => wpautop($post->post_content),
            'found_terms' => $found_terms,
            'total_glossary_terms' => count($all_terms['terms'] ?? []),
        ];
    }

    /**
     * ดึงเนื้อหาที่แปลแล้วสำหรับภาษาที่กำหนด
     * 
     * @param int $post_id Post ID
     * @param string $lang Language code (e.g., 'en')
     * @return array|WP_Error ข้อมูลเนื้อหาที่แปลแล้ว
     * 
     * @since 2.2.0
     */
    public function get_translated_content($post_id, $lang = 'en') {
        $post = get_post($post_id);
        if (!$post) {
            return new \WP_Error('not_found', 'ไม่พบเนื้อหา');
        }
        
        // ดึงจาก TranslationMeta
        $translation = TranslationMeta::get($post_id, $lang);
        
        if (!$translation || empty($translation['content'])) {
            return new \WP_Error('no_translation', 'ยังไม่มีคำแปลสำหรับภาษา ' . strtoupper($lang));
        }
        
        return [
            'content' => wpautop($translation['content']),
            'title' => $translation['title'],
            'excerpt' => $translation['excerpt'] ?? '',
            'lang' => $lang,
            'original_title' => $post->post_title,
        ];
    }

    /**
     * แปลเนื้อหาพร้อม Custom Terms
     * 
     * ขั้นตอน:
     * 1. เพิ่ม custom terms ลง Glossary (ถ้ามี)
     * 2. แปลด้วย translate_to_meta (Meta-based)
     * 
     * รองรับหลายภาษาเป้าหมาย
     *
     * @param int $post_id Post ID
     * @param array $custom_terms Array of ['thai' => '...', 'english' => '...', 'category' => '...']
     * @param string $target_lang ภาษาเป้าหมาย (default: 'en')
     * @return bool|WP_Error true ถ้าสำเร็จ
     * 
     * @since 1.8.0 - เปลี่ยนเป็น Meta-based
     * @since 2.0.0 - เพิ่ม target_lang parameter
     */
    public function translate_with_custom_terms($post_id, $custom_terms = [], $target_lang = 'en') {
        // === ตรวจสอบ Post ===
        $post = get_post($post_id);
        if (!$post) {
            return new \WP_Error('not_found', 'ไม่พบ Post');
        }

        // === ขั้นตอนที่ 1: เพิ่ม Custom Terms ลง Glossary ===
        $glossary_manager = new GlossaryManager();
        $terms_added = 0;
        $terms_updated = 0;
        
        if (!empty($custom_terms) && is_array($custom_terms)) {
            foreach ($custom_terms as $term) {
                if (!empty($term['thai']) && !empty($term['english'])) {
                    // ค้นหา term ที่มีอยู่แล้ว
                    $existing = $glossary_manager->search_terms($term['thai'], 1);
                    
                    if ($existing['total'] > 0) {
                        // อัพเดท term ที่มีอยู่
                        $glossary_manager->update_term($existing['terms'][0]['id'], [
                            'english_term' => $term['english'],
                            'category' => $term['category'] ?? 'other'
                        ]);
                        $terms_updated++;
                    } else {
                        // สร้าง term ใหม่
                        $glossary_manager->create_term([
                            'thai_term' => $term['thai'],
                            'english_term' => $term['english'],
                            'category' => $term['category'] ?? 'other'
                        ]);
                        $terms_added++;
                    }
                }
            }
        }

        // === ขั้นตอนที่ 2: แปลด้วย Meta-based ===
        $translator = new Post();
        $result = $translator->translate_to_meta($post_id, $target_lang);
        
        if (is_wp_error($result)) {
            return $result;
        }

        return true;
    }

    /**
     * ดึง Posts ที่ยังแปลไม่ครบทุกภาษาเป้าหมาย จัดกลุ่มตาม Category
     * 
     * ใช้สำหรับแสดง UI ที่จัดเรียงตามหมวดหมู่
     * รวมข้อมูลคำแปลที่มีอยู่แล้วและภาษาที่ยังขาด
     * 
     * @param int $limit จำนวน posts ต่อ category (default: 50)
     * @return array [
     *   'category_slug' => [
     *     'category' => WP_Term,
     *     'posts' => [
     *       [
     *         'post' => WP_Post,
     *         'thumbnail' => string URL,
     *         'translated_langs' => ['en' => [...], 'zh' => [...]], 
     *         'missing_langs' => ['my', 'km']
     *       ]
     *     ],
     *     'count' => int
     *   ]
     * ]
     * 
     * @since 2.1.1
     */
    public function get_incomplete_translations_by_category($limit = 50) {
        // ดึง target languages จาก settings
        $settings = new Settings();
        $target_langs = $settings->get_setting('target_languages', ['en']);
        
        // ดึง categories ทั้งหมดที่มี posts
        $categories = get_categories([
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        ]);
        
        $result = [];
        
        foreach ($categories as $category) {
            // ดึง posts ใน category นี้
            $posts = get_posts([
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => $limit,
                'category' => $category->term_id,
                'meta_query' => [
                    // ดึงเฉพาะ posts ภาษาไทย (ไม่มี _gov_translator_lang หรือเป็น 'th')
                    [
                        'relation' => 'OR',
                        [
                            'key' => '_gov_translator_lang',
                            'compare' => 'NOT EXISTS'
                        ],
                        [
                            'key' => '_gov_translator_lang',
                            'value' => 'th'
                        ]
                    ]
                ]
            ]);
            
            $incomplete_posts = [];
            
            foreach ($posts as $post) {
                // ดึงภาษาที่แปลแล้ว (meta-based)
                $translated_langs = \GovHybridTranslator\Core\TranslationMeta::get_languages($post->ID);
                
                // หาภาษาที่ยังขาด
                $missing_langs = array_diff($target_langs, $translated_langs);
                
                // ถ้ายังแปลไม่ครบ ให้รวมเข้า list
                if (!empty($missing_langs)) {
                    // ดึงข้อมูลคำแปลที่มีอยู่
                    $translations = [];
                    foreach ($translated_langs as $lang) {
                        $trans = \GovHybridTranslator\Core\TranslationMeta::get($post->ID, $lang);
                        if ($trans) {
                            $translations[$lang] = [
                                'title' => $trans['title'],
                                'excerpt' => wp_trim_words($trans['content'] ?? '', 15),
                                'translated_at' => $trans['translated_at'] ?? ''
                            ];
                        }
                    }
                    
                    $incomplete_posts[] = [
                        'post' => $post,
                        'thumbnail' => get_the_post_thumbnail_url($post->ID, 'thumbnail') ?: '',
                        'translated_langs' => $translations,
                        'missing_langs' => array_values($missing_langs)
                    ];
                }
            }
            
            // เพิ่มเข้า result ถ้ามี posts ที่ยังแปลไม่ครบ
            if (!empty($incomplete_posts)) {
                $result[$category->slug] = [
                    'category' => $category,
                    'posts' => $incomplete_posts,
                    'count' => count($incomplete_posts)
                ];
            }
        }
        
        return $result;
    }

    /**
     * ดึง Pages ที่ยังแปลไม่ครบทุกภาษาเป้าหมาย
     * 
     * @param int $limit จำนวน pages (default: 50)
     * @return array รายการ pages พร้อมข้อมูลคำแปล
     * 
     * @since 2.1.1
     */
    public function get_incomplete_page_translations($limit = 50) {
        // ดึง target languages จาก settings
        $settings = new Settings();
        $target_langs = $settings->get_setting('target_languages', ['en']);
        
        $pages = get_posts([
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
        
        $incomplete_pages = [];
        
        foreach ($pages as $page) {
            // ดึงภาษาที่แปลแล้ว
            $translated_langs = \GovHybridTranslator\Core\TranslationMeta::get_languages($page->ID);
            
            // หาภาษาที่ยังขาด
            $missing_langs = array_diff($target_langs, $translated_langs);
            
            if (!empty($missing_langs)) {
                $translations = [];
                foreach ($translated_langs as $lang) {
                    $trans = \GovHybridTranslator\Core\TranslationMeta::get($page->ID, $lang);
                    if ($trans) {
                        $translations[$lang] = [
                            'title' => $trans['title'],
                            'excerpt' => wp_trim_words($trans['content'] ?? '', 15)
                        ];
                    }
                }
                
                $incomplete_pages[] = [
                    'post' => $page,
                    'thumbnail' => get_the_post_thumbnail_url($page->ID, 'thumbnail') ?: '',
                    'translated_langs' => $translations,
                    'missing_langs' => array_values($missing_langs)
                ];
            }
        }
        
        return $incomplete_pages;
    }
}

