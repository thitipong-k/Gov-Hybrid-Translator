<?php
/**
 * Dashboard Module - หน้าหลักของ Plugin
 * 
 * รับผิดชอบในการ:
 * - ลงทะเบียน Admin Menu
 * - แสดงผล Dashboard หลักของ Plugin
 * - ดึงข้อมูลสถิติและเนื้อหาสำหรับแสดงผล
 * 
 * @package GovHybridTranslator
 * @since 1.0.0
 * @updated 1.5.0 - เพิ่มการใช้ Custom Capabilities
 * @updated 2.1.0 - ดึงข้อมูลสถิติจริงจาก database แทน mock data
 */

namespace GovHybridTranslator\Modules;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Core\Capabilities;
use GovHybridTranslator\Core\TranslationMeta;
use GovHybridTranslator\Core\TermTranslationMeta;

class Dashboard {

    /**
     * ลงทะเบียน Module
     * เพิ่ม Admin Menu เมื่อ WordPress โหลด admin
     */
    public function register() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    /**
     * เพิ่ม Admin Menu
     * ใช้ ght_view_dashboard capability แทน manage_options
     * เพื่อให้ Role อื่นๆ สามารถเข้าถึงได้ตามที่กำหนด
     */
    public function add_admin_menu() {
        add_menu_page(
            'Gov Hybrid Translator',           // Page title
            'Gov Translator',                   // Menu title
            'ght_view_dashboard',               // Capability - ใช้ custom cap
            'gov-hybrid-translator',            // Menu slug
            [ $this, 'render_dashboard' ],      // Callback function
            'dashicons-translation',            // Icon
            2                                   // Position
        );

        // Submenu: Dashboard (Default)
        add_submenu_page(
            'gov-hybrid-translator',
            'Dashboard',
            'Dashboard',
            'ght_view_dashboard',
            'gov-hybrid-translator',
            [ $this, 'render_dashboard' ]
        );

        // Submenu: Review Queue
        add_submenu_page(
            'gov-hybrid-translator',
            'Review Queue',
            'Review Queue',
            'ght_approve_translation',
            'gov-hybrid-translator-review',
            [ $this, 'render_review_queue' ]
        );
    }
    
    /**
     * Enqueue Admin Scripts
     */
    public function enqueue_admin_assets($hook) {
        // Load only on our plugin pages
        if (strpos($hook, 'gov-hybrid-translator') === false) {
            return;
        }

        wp_enqueue_script('ght-admin-dashboard', GOV_HYBRID_TRANSLATOR_URL . 'assets/js/admin-dashboard.js', ['jquery'], '2.5.0', true);
        
        wp_localize_script('ght-admin-dashboard', 'ghtAdminData', [
            'nonce_save' => wp_create_nonce('ght_save_translation'),
            'nonce_translate' => wp_create_nonce('ght_translate_to_language'),
            'nonce_settings' => wp_create_nonce('ght_save_settings'),
            'nonce_design_tabs' => wp_create_nonce('ght_design_tabs'),
            'i18n' => [
                'error' => __('Error', 'gov-hybrid-translator'),
                'saved' => __('Saved', 'gov-hybrid-translator')
            ]
        ]);
        
        // Tailwind CDN (Optional/Dev only) - Explained to User
        // wp_enqueue_script('ght-tailwind', 'https://cdn.tailwindcss.com', [], null); // Commented out to reduce reliance? No, we need it for UI.
        // We keep it in the view for now or enqueue here:
        // wp_enqueue_script('ght-tailwind', 'https://cdn.tailwindcss.com', [], null);
    }

    /**
     * แสดงผล Review Queue
     */
    public function render_review_queue() {
        require GOV_HYBRID_TRANSLATOR_PATH . 'inc/Admin/views/review-dashboard-view.php';
    }

    /**
     * แสดงผล Dashboard
     * ดึงข้อมูลทั้งหมดและส่งไปยัง View
     */
    public function render_dashboard() {
        // ดึงข้อมูล Pages และ Posts ทั้งหมด (Sort by Modified Date for "Recent Translations")
        $all_pages = get_pages(['post_status' => 'publish', 'sort_column' => 'post_modified', 'sort_order' => 'desc']);
        $all_posts = get_posts(['post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'modified', 'order' => 'DESC']);

        
        // Initialize Settings
        $settings_obj = new \GovHybridTranslator\Modules\Settings();
        $settings = $settings_obj->get_settings();

        // ดึงข้อมูล Categories และ Menus
        $all_categories = get_terms(['taxonomy' => 'category', 'hide_empty' => false]);
        $all_menus = wp_get_nav_menus();

        // Calculate statistics
        $total_pages = count($all_pages);
        $total_posts = count($all_posts);
        $total_content = $total_pages + $total_posts;
        
        // === ดึงข้อมูลจริงจาก Database ===
        
        // 1. นับ Glossary Terms จาก Custom Post Type
        $glossary_terms = wp_count_posts('gov_glossary');
        $glossary_terms = isset($glossary_terms->publish) ? $glossary_terms->publish : 0;
        
        // 2. ดึง Translation Memory Stats (ถ้ามี)
        $tm_stats = \GovHybridTranslator\Modules\TranslationMemory::get_stats();
        // API Credits Used = Misses only (Requests sent to API)
        // นับเฉพาะ Misses เท่านั้น เพราะคือจำนวนครั้งที่ต้องเรียก API จริง (Hits = ใช้จาก Memory ไม่เสีย Credit)
        $ai_credits_used = ($tm_stats['misses'] ?? 0);
        // AI Credits Limit: ดึงจาก settings หรือใช้ค่า based on usage
        $ai_credits_limit = max($ai_credits_used * 2, 100); // อย่างน้อย 100 หรือ 2x ของ usage
        
        // 3. คำนวณ Success Rate จาก Translation Memory
        $success_rate = $tm_stats['hit_rate'] ?? 0;
        
        // 4. ดึง Translation Status สำหรับ translated_count
        $translation_status = new \GovHybridTranslator\Core\TranslationStatus();
        $status_stats = $translation_status->get_statistics();
        $translated_count = ($status_stats['translated'] ?? 0) + ($status_stats['partial'] ?? 0);
        $pending_count = $status_stats['pending'] ?? 0;
        $draft_count = $status_stats['draft'] ?? 0;
        
        // 5. คำนวณ Error Rate (ยังไม่มีระบบ track จริง - ใช้ 0)
        $error_rate = 0;
        
        // 6. Avg Translation Time (ยังไม่มีระบบ track - แสดง N/A)
        $avg_translation_time = 'N/A';
        
        // === Language Distribution (ดึงข้อมูลจริง) ===
        global $wpdb;
        $target_languages = $settings['target_languages'] ?? ['en'];
        
        $language_distribution = [];
        $lang_colors = [
            'en' => '#3b82f6', 'zh' => '#ef4444', 'ja' => '#10b981',
            'ko' => '#f59e0b', 'vi' => '#8b5cf6', 'my' => '#ec4899',
            'de' => '#14b8a6', 'fr' => '#6366f1'
        ];
        $lang_names = [
            'en' => 'English', 'zh' => 'Chinese', 'ja' => 'Japanese',
            'ko' => 'Korean', 'vi' => 'Vietnamese', 'my' => 'Myanmar',
            'de' => 'German', 'fr' => 'French'
        ];
        
        foreach ($target_languages as $lang) {
            // นับจำนวน posts/pages ที่มีการแปลเป็นภาษานี้
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} 
                 WHERE meta_key = %s AND meta_value != ''",
                '_ght_title_' . $lang
            ));
            
            $language_distribution[$lang] = [
                'name' => $lang_names[$lang] ?? strtoupper($lang),
                'count' => intval($count),
                'color' => $lang_colors[$lang] ?? '#6b7280'
            ];
        }
        
        // === Monthly Trends (ดึงข้อมูลจริงจาก post modified dates) ===
        $monthly_trends = [];
        for ($i = 5; $i >= 0; $i--) {
            $month_start = date('Y-m-01', strtotime("-$i months"));
            $month_end = date('Y-m-t', strtotime("-$i months"));
            $month_name = date('M', strtotime("-$i months"));
            
            // นับ posts ที่มีการแปลในเดือนนี้ (based on translated_at meta)
            // ใช้ _ght_translated_at_% เพื่อดูเวลาที่แปลจริงๆ แทน post_modified
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT post_id) 
                 FROM {$wpdb->postmeta}
                 WHERE meta_key LIKE %s
                 AND meta_value >= %s AND meta_value <= %s",
                '_ght_translated_at_%',
                $month_start . ' 00:00:00',
                $month_end . ' 23:59:59'
            ));
            
            $monthly_trends[] = [
                'month' => $month_name,
                'translations' => max(intval($count), 0)
            ];
        }
        
        // ถ้าไม่มีข้อมูลเลย ให้แสดง 0
        if (array_sum(array_column($monthly_trends, 'translations')) === 0) {
            // ป้องกัน division by zero ใน chart
            $monthly_trends[5]['translations'] = 1;
        }
        
        // === Top Categories (ดึงข้อมูลจริง) ===
        $top_categories = [];
        $categories_query = $wpdb->get_results(
            "SELECT t.name, COUNT(tr.object_id) as count
             FROM {$wpdb->terms} t
             INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
             INNER JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
             INNER JOIN {$wpdb->postmeta} pm ON tr.object_id = pm.post_id
             WHERE tt.taxonomy = 'category'
             AND pm.meta_key LIKE '_ght_title_%'
             AND pm.meta_value != ''
             GROUP BY t.term_id
             ORDER BY count DESC
             LIMIT 5"
        );
        
        if ($categories_query) {
            foreach ($categories_query as $cat) {
                $top_categories[] = [
                    'name' => $cat->name,
                    'count' => intval($cat->count)
                ];
            }
        }
        
        // ถ้าไม่มี categories ที่มีการแปล แสดง placeholder
        if (empty($top_categories)) {
            $top_categories = [
                ['name' => 'No translated categories yet', 'count' => 0]
            ];
        }
        
        // เตรียม TranslationStatus
        $translation_status_service = new \GovHybridTranslator\Core\TranslationStatus();
        $source_lang = $settings_obj->get_setting('source_language', 'th');

        // Filter Pages
        $untranslated_pages = [];
        $translated_pages = [];
        foreach ($all_pages as $page) {
            // Check Source Language
            $lang = get_post_meta($page->ID, '_gov_translator_lang', true);
            if ($lang && $lang !== $source_lang) continue; // Skip non-source pages

            $status = $translation_status_service->get_status($page->ID);

            // Logic:
            // Tasks (Untranslated) = Not fully translated (None, Pending, Partial, Draft, Needs Update)
            // Translated = Has some translation (Partial, Draft, Translated, Needs Update)
            
            if ($status !== \GovHybridTranslator\Core\TranslationStatus::STATUS_TRANSLATED) {
                $untranslated_pages[] = $page;
            }

            if ($status !== \GovHybridTranslator\Core\TranslationStatus::STATUS_NONE && 
                $status !== \GovHybridTranslator\Core\TranslationStatus::STATUS_PENDING) {
                $translated_pages[] = $page;
            }
        }

        // Filter Posts
        $untranslated_posts = [];
        $translated_posts = [];
        foreach ($all_posts as $post) {
            // Check Source Language
            $lang = get_post_meta($post->ID, '_gov_translator_lang', true);
            if ($lang && $lang !== $source_lang) continue; // Skip non-source posts

            $status = $translation_status_service->get_status($post->ID);

            if ($status !== \GovHybridTranslator\Core\TranslationStatus::STATUS_TRANSLATED) {
                $untranslated_posts[] = $post;
            }

            if ($status !== \GovHybridTranslator\Core\TranslationStatus::STATUS_NONE && 
                $status !== \GovHybridTranslator\Core\TranslationStatus::STATUS_PENDING) {
                $translated_posts[] = $post;
            }
        }

        // Filter Categories
        $untranslated_categories = [];
        $translated_categories = [];
        if (!is_wp_error($all_categories)) {
            foreach ($all_categories as $category) {
                $is_translated = false;
                $is_fully_translated = true;
                
                foreach ($target_languages as $lang) {
                    if ($lang === $source_lang) continue;
                    $trans_name = \GovHybridTranslator\Core\TermTranslationMeta::get_name($category->term_id, $lang);
                    
                    if (!empty($trans_name)) {
                        $is_translated = true;
                    } else {
                        $is_fully_translated = false;
                    }
                }
                
                // Logic:
                // Untranslated = Not fully translated
                // Translated = Has some translation
                
                if (!$is_fully_translated) {
                    $untranslated_categories[] = $category;
                }
                
                if ($is_translated) {
                    $translated_categories[] = $category;
                }
            }
        }

        // Filter Menus
        $untranslated_menus = [];
        $translated_menus = [];
        // For Menus, we list the MENU object itself, but we check if it has ANY untranslated items?
        // Or do we list items? The UI shows Menus.
        // Let's say: A menu is "Untranslated" if it has at least one item without translation.
        // A menu is "Translated" if it has at least one item WITH translation (or all?).
        // To fit the UI "Tasks" vs "Translated", let's put the Menu in "Tasks" if it has pending items.
        // And put it in "Translated" if it has translated items. Note: A menu could be in BOTH if partially translated.
        // But for simplicity, let's just pass the full list to the view and let the view filter items?
        // No, the user wants "Flow".
        // Let's filter:
        // Tasks Tab: Menus that have items needing translation.
        // Translated Tab: Menus that have translated items.
        
        if (!is_wp_error($all_menus)) {
            foreach ($all_menus as $menu) {
                $items = wp_get_nav_menu_items($menu->term_id);
                $has_untranslated = false;
                $has_translated = false;
                
                if ($items) {
                    foreach ($items as $item) {
                        $item_is_translated = false;
                        
                        // Check if item is translated to ALL target languages (or at least one?)
                        // For menu, we usually want full translation.
                        
                        foreach ($target_languages as $lang) {
                            if ($lang === $source_lang) continue;
                            $trans_title = \GovHybridTranslator\Core\TranslationMeta::get_title($item->ID, $lang);
                            
                            if (empty($trans_title)) {
                                $has_untranslated = true;
                            } else {
                                $has_translated = true;
                            }
                        }
                    }
                }

                if ($has_untranslated) $untranslated_menus[] = $menu;
                if ($has_translated) $translated_menus[] = $menu;
            }
        }
        
        // Get current settings (Already initialized)
        // $settings_obj and $settings are available
        
        // === ดึงข้อมูล Incomplete Translations จัดกลุ่มตาม Category ===
        // ป้องกัน Fatal Error หาก ContentReviewer class ไม่มี
        $incomplete_by_category = [];
        $incomplete_pages = [];
        if (class_exists('\GovHybridTranslator\Modules\ContentReviewer')) {
            $content_reviewer = new \GovHybridTranslator\Modules\ContentReviewer();
            $incomplete_by_category = $content_reviewer->get_incomplete_translations_by_category(50);
            $incomplete_pages = $content_reviewer->get_incomplete_page_translations(50);
            $incomplete_pages = $content_reviewer->get_incomplete_page_translations(50);
        }

        // === Activity Logs ===
        // ดึงข้อมูล Logs ล่าสุดมาแสดงใน Dashboard
        $activity_logger = new \GovHybridTranslator\Modules\ActivityLogger();
        $paged_logs = isset($_GET['paged_logs']) ? absint($_GET['paged_logs']) : 1;
        // ดึง 20 รายการล่าสุด ตามหน้าปัจจุบัน
        $logs_data = $activity_logger->get_logs(['limit' => 20, 'offset' => ($paged_logs - 1) * 20]);
        
        require GOV_HYBRID_TRANSLATOR_PATH . 'inc/Admin/views/dashboard-view.php';
    }

}
