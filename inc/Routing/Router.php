<?php
/**
 * Router Class - จัดการ Path-based URL Routing
 * 
 * ไฟล์นี้รับผิดชอบในการ:
 * 1. เพิ่ม WordPress Rewrite Rules สำหรับ URL แบบ /en/, /zh/, etc.
 * 2. ลงทะเบียน Query Variables สำหรับภาษา
 * 3. จัดการ Permalink สำหรับ multi-language
 * 
 * การทำงาน:
 * - เมื่อ WordPress รับ request มาที่ /en/about/
 * - Rewrite Rule จะแปลงเป็น index.php?ght_lang=en&pagename=about
 * - get_query_var('ght_lang') จะคืนค่า 'en'
 * 
 * ข้อควรระวัง:
 * - ต้อง flush rewrite rules หลัง activate plugin
 * - ต้องเปิด Pretty Permalinks ใน WordPress Settings
 * - อย่าสร้าง page ที่มี slug เป็น 'en', 'zh', etc.
 * 
 * @package GovHybridTranslator
 * @since 1.8.0
 * @see PATH_BASED_URL_PLAN.md สำหรับรายละเอียดแผนการพัฒนา
 */

namespace GovHybridTranslator\Routing;

class Router {

    /**
     * รายการภาษาที่รองรับ
     * 
     * หมายเหตุ:
     * - 'th' เป็น default ไม่ต้องมี prefix ใน URL
     * - ภาษาอื่นๆ จะมี prefix เช่น /en/, /zh/
     * 
     * @var array
     */
    const SUPPORTED_LANGUAGES = ['en', 'zh', 'ja', 'ko', 'vi', 'my'];

    /**
     * Query Variable สำหรับเก็บภาษา
     * 
     * ใช้สำหรับ get_query_var() และ add_rewrite_tag()
     * 
     * @var string
     */
    const LANG_QUERY_VAR = 'ght_lang';

    /**
     * ลงทะเบียน Router
     * 
     * เรียกใช้จาก Loader.php เพื่อ:
     * 1. เพิ่ม rewrite rules
     * 2. ลงทะเบียน query vars
     * 3. Filter permalinks
     * 4. Handle language homepage template
     */
    public function register() {
        // เพิ่ม rewrite rules และ tags
        add_action('init', [$this, 'add_rewrite_rules'], 10);
        
        // ลงทะเบียน query variable
        add_filter('query_vars', [$this, 'add_query_vars']);
        
        // ตรวจสอบว่าต้อง flush rewrite rules หรือไม่
        add_action('init', [$this, 'maybe_flush_rewrite_rules'], 999);
        
        // === ตรวจจับ language homepage ก่อน WordPress parse request ===
        add_action('parse_request', [$this, 'handle_language_homepage_early'], 1);
        
        // === ทำให้ /en/ โหลด homepage template เหมือน / ===
        add_action('pre_get_posts', [$this, 'handle_language_homepage']);
        
        // === จัดการ posts/pages สำหรับภาษาอื่น ===
        add_action('pre_get_posts', [$this, 'handle_translated_content'], 15);
        
        // === Setup translated content BEFORE Avada sets page_id ===
        // Avada's set_page_id() runs at 'wp' hook priority 10
        // เราต้อง set query flags ก่อนเพื่อให้ Avada ได้ค่าถูกต้อง
        add_action('wp', [$this, 'setup_translated_content_early'], 1);
        
        // === แก้ไข 404 status ก่อน template โหลด ===
        add_action('template_redirect', [$this, 'fix_language_homepage_404'], 1);
        
        // === แก้ไข 404 สำหรับ translated pages (ไม่ใช่ homepage) ===
        add_action('template_redirect', [$this, 'fix_translated_content_404'], 2);
        
        // Template redirect สำหรับ language homepage
        add_filter('template_include', [$this, 'maybe_load_home_template'], 999);
        
        // === Override query vars สำหรับ language homepage ===
        // ใช้ request filter ที่มี priority ต่ำสุดเพื่อ override ค่าสุดท้าย
        add_filter('request', [$this, 'override_language_homepage_query'], 999);
    }
    
    /**
     * Override Query Vars สำหรับ Language Homepage
     * 
     * Filter นี้ทำงานหลัง WordPress parse URL แล้ว
     * เราจะตรวจสอบ REQUEST_URI โดยตรงและ override query vars
     * เพื่อบังคับให้โหลด front page สำหรับ /en/
     * 
     * @param array $query_vars Query vars from WordPress
     * @return array Modified query vars
     * @since 2.1.1
     */
    public function override_language_homepage_query($query_vars) {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        
        // Pattern สำหรับ language prefix
        $languages_pattern = implode('|', self::SUPPORTED_LANGUAGES);
        
        // === ตรวจสอบ Language Homepage ก่อน ===
        // Pattern: /wordpress/en/ หรือ /en/ (รองรับทั้ง subdirectory และ root)
        if (preg_match('#/(' . $languages_pattern . ')/?(\\?.*)?$#', $request_uri, $matches)) {
            $lang = $matches[1];
            
            // Set language query var
            $query_vars[self::LANG_QUERY_VAR] = $lang;
            
            // ดึงการตั้งค่า front page
            $show_on_front = get_option('show_on_front');
            $page_on_front = get_option('page_on_front');
            
            if ($show_on_front === 'page' && $page_on_front) {
                // Override เป็น page query สำหรับ static front page
                $query_vars['page_id'] = $page_on_front;
                $query_vars['post_type'] = 'page';
                
                // ลบ query vars ที่อาจทำให้เกิด 404
                unset($query_vars['error']);
                unset($query_vars['pagename']);
                unset($query_vars['name']);
            }
            
            return $query_vars;
        }
        
        // === ตรวจสอบ Internal Pages (/en/slug/) ===
        // Pattern: /wordpress/en/slug/ หรือ /en/slug/
        if (preg_match('#/(' . $languages_pattern . ')/(.+?)/?(?:\?|$)#', $request_uri, $matches)) {
            $lang = $matches[1];
            $slug = sanitize_title($matches[2]);
            
            if (!empty($slug)) {
                // Set language query var
                $query_vars[self::LANG_QUERY_VAR] = $lang;
                
                // หา post/page ที่มี slug ตรงกัน
                global $wpdb;
                $post = $wpdb->get_row($wpdb->prepare(
                    "SELECT ID, post_type FROM {$wpdb->posts} 
                     WHERE post_name = %s AND post_status = 'publish' 
                     AND post_type IN ('page', 'post') 
                     LIMIT 1",
                    $slug
                ));
                
                if ($post) {
                    // Set query vars ให้ WordPress หา post ถูกต้อง
                    if ($post->post_type === 'page') {
                        $query_vars['page_id'] = $post->ID;
                        $query_vars['post_type'] = 'page';
                    } else {
                        $query_vars['p'] = $post->ID;
                        $query_vars['post_type'] = 'post';
                    }
                    
                    // ลบ query vars ที่อาจทำให้เกิด 404
                    unset($query_vars['error']);
                    unset($query_vars['pagename']);
                    unset($query_vars['name']);
                }
            }
        }
        
        return $query_vars;
    }
    
    /**
     * ตรวจจับ Language Homepage ก่อน WordPress Parse Request
     * 
     * Hook นี้ทำงานก่อน WordPress สร้าง query object
     * เราจะ detect ว่า URL เป็น /en/ (language homepage) หรือไม่
     * แล้ว set query_vars ให้ถูกต้องเพื่อป้องกัน 404
     * 
     * @param WP $wp WordPress environment object
     * @since 2.1.1
     */
    public function handle_language_homepage_early($wp) {
        $request = $wp->request ?? '';
        
        // ตรวจสอบว่าเป็น language code เดี่ยวๆ หรือไม่ (เช่น "en" หรือ "en/")
        $languages_pattern = implode('|', self::SUPPORTED_LANGUAGES);
        
        if (preg_match('#^(' . $languages_pattern . ')/?$#', $request, $matches)) {
            $lang = $matches[1];
            
            // Set query vars สำหรับ homepage
            $wp->query_vars[self::LANG_QUERY_VAR] = $lang;
            
            // บอก WordPress ว่าเป็น homepage
            $show_on_front = get_option('show_on_front');
            $page_on_front = get_option('page_on_front');
            
            if ($show_on_front === 'page' && $page_on_front) {
                // Static front page
                $wp->query_vars['page_id'] = $page_on_front;
            }
            
            // ลบ matched request เพื่อไม่ให้ WP พยายาม parse ต่อ
            $wp->matched_rule = '^' . $lang . '/?$';
            $wp->matched_query = self::LANG_QUERY_VAR . '=' . $lang;
        }
    }
    
    /**
     * แก้ไข 404 status สำหรับ language homepage
     * 
     * WordPress ตัดสินใจ 404 หลังจาก pre_get_posts ทำงาน
     * ต้องใช้ template_redirect (ก่อน template โหลด) เพื่อ override
     * 
     * @since 2.1.1
     */
    public function fix_language_homepage_404() {
        global $wp_query;
        
        $lang = get_query_var(self::LANG_QUERY_VAR);
        if (!$lang || !in_array($lang, self::SUPPORTED_LANGUAGES)) {
            return;
        }
        
        // ตรวจสอบว่าเป็น language homepage หรือไม่
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        
        // Pattern: /en/ หรือ /en (ท้าย URL) หรือ /wordpress/en/
        if (!preg_match('#/(' . implode('|', self::SUPPORTED_LANGUAGES) . ')/?(\?.*)?$#', $request_uri)) {
            return;
        }
        
        // ถ้าเป็น 404 ให้แก้ไข
        if ($wp_query->is_404) {
            $show_on_front = get_option('show_on_front');
            $page_on_front = get_option('page_on_front');
            
            // Reset query ใหม่
            if ($show_on_front === 'page' && $page_on_front) {
                // Query หน้าแรก Static Page
                $wp_query->is_404 = false;
                $wp_query->is_page = true;
                $wp_query->is_singular = true;
                $wp_query->is_front_page = true;
                
                // ดึง post data ใหม่
                $wp_query->query_vars['page_id'] = $page_on_front;
                $wp_query->query(['page_id' => $page_on_front]);
                
                // Set status header กลับเป็น 200
                status_header(200);
            } else {
                // Blog posts
                $wp_query->is_404 = false;
                $wp_query->is_home = true;
                $wp_query->is_front_page = true;
                
                // Query posts ใหม่
                $wp_query->query(['posts_per_page' => get_option('posts_per_page')]);
                
                status_header(200);
            }
        }
    }
    
    /**
     * แก้ไข 404 สำหรับหน้าแปล (ไม่ใช่ homepage)
     * 
     * Hook นี้ทำงานหลัง fix_language_homepage_404
     * จัดการกรณี /en/page-slug/ ที่ WordPress ไม่พบ
     * 
     * @since 2.1.2
     */
    public function fix_translated_content_404() {
        global $wp_query;
        
        // ถ้าไม่ใช่ 404 ไม่ต้องทำอะไร
        if (!$wp_query->is_404) {
            return;
        }
        
        $lang = get_query_var(self::LANG_QUERY_VAR);
        
        if (!$lang || !in_array($lang, self::SUPPORTED_LANGUAGES)) {
            return;
        }
        
        // ดึง slug จาก REQUEST_URI
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        
        // Pattern: /en/slug/ หรือ /wordpress/en/slug/
        // ใช้ (.+?) เพื่อจับ slug ทั้งหมด รวม hyphen (-) และ multi-segment
        $languages_pattern = implode('|', self::SUPPORTED_LANGUAGES);
        if (!preg_match('#/(' . $languages_pattern . ')/(.+?)/?(?:\?|$)#', $request_uri, $matches)) {
            return;
        }
        
        $slug = sanitize_title($matches[2]);
        
        if (empty($slug)) {
            return;
        }
        
        // หา post/page ที่มี slug ตรงกัน
        global $wpdb;
        $post = $wpdb->get_row($wpdb->prepare(
            "SELECT ID, post_type FROM {$wpdb->posts} 
             WHERE post_name = %s AND post_status = 'publish' 
             AND post_type IN ('page', 'post') 
             LIMIT 1",
            $slug
        ));
        
        if (!$post) {
            return; // ไม่พบ post/page
        }
        
        // Reset query และตั้งค่าใหม่
        $wp_query->is_404 = false;
        $wp_query->is_singular = true;
        
        if ($post->post_type === 'page') {
            $wp_query->is_page = true;
            $wp_query->is_single = false;
            $wp_query->query_vars['page_id'] = $post->ID;
            $wp_query->query(['page_id' => $post->ID]);
        } else {
            $wp_query->is_single = true;
            $wp_query->is_page = false;
            $wp_query->query_vars['p'] = $post->ID;
            $wp_query->query(['p' => $post->ID]);
        }
        
        // === สำคัญ: Setup global $post สำหรับ template tags ===
        // ต้อง setup ทั้ง global $post และ postdata เพื่อให้ get_the_ID() ทำงานถูกต้อง
        $GLOBALS['post'] = get_post($post->ID);
        setup_postdata($GLOBALS['post']);
        
        if ($wp_query->have_posts()) {
            $wp_query->the_post();
            rewind_posts();
        }
        
        // Set status header กลับเป็น 200
        status_header(200);
    }
    
    /**
     * Setup Translated Content Early (ก่อน Avada's set_page_id)
     * 
     * Avada's Fusion::set_page_id() ทำงานที่ 'wp' hook priority 10
     * ถ้าเราไม่ set query flags ก่อน Avada จะได้ page_id ผิด
     * ทำให้ header/footer ไม่โหลด
     * 
     * @since 2.1.3
     */
    public function setup_translated_content_early() {
        // ข้ามถ้าเป็น admin หรือไม่ใช่ translated URL
        if (is_admin()) {
            return;
        }
        
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $supported_languages = self::SUPPORTED_LANGUAGES;
        $languages_pattern = implode('|', $supported_languages);
        
        // ตรวจสอบว่าเป็น language homepage หรือไม่ 
        $is_lang_homepage = (bool) preg_match('#/(' . $languages_pattern . ')/?(?:\?.*)?$#', parse_url($request_uri, PHP_URL_PATH));
        
        if ($is_lang_homepage) {
            return; // Homepage มี handler แยกต่างหาก
        }
        
        // Pattern: /wordpress/en/slug/ หรือ /en/slug/
        if (!preg_match('#/(' . $languages_pattern . ')/(.+?)/?(?:\?|$)#', $request_uri, $matches)) {
            return;
        }
        
        $lang = $matches[1];
        $slug = sanitize_title($matches[2]);
        
        if (empty($slug)) {
            return;
        }
        
        // หา post/page
        global $wpdb, $wp_query;
        $post = $wpdb->get_row($wpdb->prepare(
            "SELECT ID, post_type FROM {$wpdb->posts} 
             WHERE post_name = %s AND post_status = 'publish' 
             AND post_type IN ('page', 'post') 
             LIMIT 1",
            $slug
        ));
        
        if (!$post) {
            return;
        }
        
        // === ตั้งค่า query flags สำหรับ Avada ===
        $wp_query->is_404 = false;
        $wp_query->is_singular = true;
        
        if ($post->post_type === 'page') {
            $wp_query->is_page = true;
            $wp_query->is_single = false;
            $wp_query->queried_object_id = $post->ID;
            $wp_query->queried_object = get_post($post->ID);
        } else {
            $wp_query->is_single = true;
            $wp_query->is_page = false;
            $wp_query->queried_object_id = $post->ID;
            $wp_query->queried_object = get_post($post->ID);
        }
        
        // === Setup global $post ===
        $GLOBALS['post'] = get_post($post->ID);
        setup_postdata($GLOBALS['post']);
    }
    
    /**
     * ทำให้ /en/ โหลดเนื้อหา homepage
     * 
     * ปัญหา: /en/ สร้างแค่ ?ght_lang=en แต่ไม่ได้บอก WordPress ว่าเป็น homepage
     * แก้ไข: Force is_home และ is_front_page flags
     * 
     * @param WP_Query $query Main WordPress query
     */
    public function handle_language_homepage($query) {
        if (!$query->is_main_query() || is_admin()) {
            return;
        }
        
        // ตรวจสอบว่าเป็น language homepage หรือไม่
        $lang = $query->get(self::LANG_QUERY_VAR);
        if (!$lang || !in_array($lang, self::SUPPORTED_LANGUAGES)) {
            return;
        }
        
        // ตรวจสอบว่าไม่มี pagename หรือ name (เป็น homepage)
        $pagename = $query->get('pagename');
        $name = $query->get('name');
        $p = $query->get('p');
        $page_id = $query->get('page_id');
        
        // ถ้าไม่มีระบุ post/page ใดๆ = เป็น homepage
        if (empty($pagename) && empty($name) && empty($p) && empty($page_id)) {
            // ดึง page_on_front ถ้า reading settings ตั้งเป็น static page
            $show_on_front = get_option('show_on_front');
            $page_on_front = get_option('page_on_front');
            
            if ($show_on_front === 'page' && $page_on_front) {
                // Static front page
                $query->set('page_id', $page_on_front);
                $query->set('post_type', 'page');
                $query->is_home = false;
                $query->is_page = true;
                $query->is_singular = true;
                $query->is_front_page = true;
                $query->is_404 = false;  // *** สำคัญ: บอก WP ว่าไม่ใช่ 404 ***
            } else {
                // Blog posts
                $query->is_home = true;
                $query->is_front_page = true;
                $query->is_404 = false;  // *** สำคัญ: บอก WP ว่าไม่ใช่ 404 ***
            }
        }
    }
    
    /**
     * จัดการ Query สำหรับหน้าแปล (ไม่ใช่ homepage)
     * 
     * แก้ไขปัญหา:
     * - WordPress หา post/page ไม่เจอเพราะใช้ pagename กับ posts
     * - posts ต้องใช้ 'name' ไม่ใช่ 'pagename'
     * 
     * @param WP_Query $query
     * @since 2.1.2
     */
    public function handle_translated_content($query) {
        if (!$query->is_main_query() || is_admin()) {
            return;
        }
        
        $lang = $query->get(self::LANG_QUERY_VAR);
        if (!$lang || !in_array($lang, self::SUPPORTED_LANGUAGES)) {
            return;
        }
        
        $pagename = $query->get('pagename');
        $name = $query->get('name');
        $slug = $pagename ?: $name;
        
        if (empty($slug)) {
            return; // Homepage, handled by handle_language_homepage()
        }
        
        // === หา post หรือ page ที่มี slug ตรงกัน ===
        global $wpdb;
        
        // ลองหา page ก่อน
        $post = $wpdb->get_row($wpdb->prepare(
            "SELECT ID, post_type FROM {$wpdb->posts} 
             WHERE post_name = %s AND post_status = 'publish' 
             AND post_type IN ('page', 'post') 
             LIMIT 1",
            sanitize_title($slug)
        ));
        
        if ($post) {
            if ($post->post_type === 'page') {
                $query->set('page_id', $post->ID);
                $query->set('pagename', '');
                $query->set('name', '');
                $query->is_page = true;
                $query->is_singular = true;
            } else {
                $query->set('p', $post->ID);
                $query->set('pagename', '');
                $query->set('name', '');
                $query->is_single = true;
                $query->is_singular = true;
            }
            $query->is_404 = false;
        }
    }
    
    /**
     * โหลด template ที่ถูกต้องสำหรับ translated pages
     * 
     * จัดการทั้ง:
     * 1. Language homepage (/en/)
     * 2. Translated pages (/en/about/)
     * 3. Translated posts (/en/hello-world/)
     * 
     * @param string $template Current template path
     * @return string Modified template path
     * @since 2.1.2 - Extended to handle internal pages
     */
    public function maybe_load_home_template($template) {
        $lang = get_query_var(self::LANG_QUERY_VAR);
        if (!$lang || !in_array($lang, self::SUPPORTED_LANGUAGES)) {
            return $template;
        }
        
        // ตรวจสอบว่าเป็น language homepage หรือไม่
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        // Pattern รองรับทั้ง root install (/en/) และ subdirectory (/wordpress/en/)
        $languages_pattern = implode('|', self::SUPPORTED_LANGUAGES);
        $is_lang_homepage = (bool) preg_match('#/(' . $languages_pattern . ')/?(?:\?.*)?$#', parse_url($request_uri, PHP_URL_PATH));
        
        if ($is_lang_homepage) {
            // ใช้ front-page.php หรือ home.php หรือ index.php
            $show_on_front = get_option('show_on_front');
            
            if ($show_on_front === 'page') {
                $front_template = locate_template(['front-page.php', 'page.php', 'singular.php', 'index.php']);
            } else {
                $front_template = locate_template(['front-page.php', 'home.php', 'index.php']);
            }
            
            if ($front_template) {
                return $front_template;
            }
        }
        
        // === สำหรับ translated pages/posts ไม่ต้อง override template ===
        // ปล่อยให้ WordPress/Avada theme ใช้ template ของตัวเอง
        // เพื่อให้ header/footer โหลดถูกต้อง
        
        return $template;
    }

    /**
     * เพิ่ม Rewrite Rules สำหรับแต่ละภาษา
     * 
     * สร้าง rules สำหรับ:
     * - หน้าแรก: ^en/?$ → index.php?ght_lang=en
     * - หน้าอื่น: ^en/(.+)/?$ → index.php?ght_lang=en&pagename=$1
     * 
     * ลำดับความสำคัญ:
     * - ใช้ 'top' เพื่อให้ rules เหล่านี้มี priority สูงสุด
     * - จะจับ URL ก่อน WordPress default rules
     */
    public function add_rewrite_rules() {
        // เพิ่ม rewrite tag สำหรับภาษา
        // Pattern: [a-z]{2} = รหัสภาษา 2 ตัวอักษร
        add_rewrite_tag('%' . self::LANG_QUERY_VAR . '%', '([a-z]{2})');

        foreach (self::SUPPORTED_LANGUAGES as $lang) {
            // === Rule 1: หน้าแรก ===
            // URL: /en/ หรือ /en
            // Result: index.php?ght_lang=en
            add_rewrite_rule(
                '^' . $lang . '/?$',
                'index.php?' . self::LANG_QUERY_VAR . '=' . $lang,
                'top'
            );

            // === Rule 2: Single-level slug (Posts/Pages) ===
            // URL: /en/about/ หรือ /en/my-post/
            // ใช้ 'name' แทน 'pagename' เพื่อหาทั้ง posts และ pages
            add_rewrite_rule(
                '^' . $lang . '/([^/]+)/?$',
                'index.php?' . self::LANG_QUERY_VAR . '=' . $lang . '&name=$matches[1]',
                'top'
            );
            
            // === Rule 2b: Hierarchical Pages (parent/child) ===
            // URL: /en/parent/child/
            add_rewrite_rule(
                '^' . $lang . '/(.+?)/?$',
                'index.php?' . self::LANG_QUERY_VAR . '=' . $lang . '&pagename=$matches[1]',
                'top'
            );
            
            // === Rule 3: Archive/Category ===
            // URL: /en/category/news/
            // Result: index.php?ght_lang=en&category_name=news
            add_rewrite_rule(
                '^' . $lang . '/category/(.+?)/?$',
                'index.php?' . self::LANG_QUERY_VAR . '=' . $lang . '&category_name=$matches[1]',
                'top'
            );
            
            // === Rule 4: Single Post ===
            // URL: /en/2024/12/post-name/
            // Result: index.php?ght_lang=en&name=post-name
            add_rewrite_rule(
                '^' . $lang . '/[0-9]{4}/[0-9]{2}/([^/]+)/?$',
                'index.php?' . self::LANG_QUERY_VAR . '=' . $lang . '&name=$matches[1]',
                'top'
            );
        }
    }

    /**
     * เพิ่ม Query Variable สำหรับภาษา
     * 
     * WordPress ต้องรู้จัก query var ก่อนถึงจะใช้ get_query_var() ได้
     * 
     * @param array $vars Query variables ที่มีอยู่
     * @return array Query variables พร้อม ght_lang
     */
    public function add_query_vars($vars) {
        $vars[] = self::LANG_QUERY_VAR;
        return $vars;
    }

    /**
     * Flush Rewrite Rules ถ้าจำเป็น
     * 
     * ตรวจสอบ option 'ght_flush_rewrite_rules' และ flush ถ้าต้องการ
     * Option นี้จะถูก set เมื่อ:
     * 1. Plugin ถูก activate
     * 2. มีการเปลี่ยนแปลง settings ที่เกี่ยวกับ URL
     * 
     * หลัง flush จะลบ option เพื่อไม่ให้ flush ซ้ำ
     */
    public function maybe_flush_rewrite_rules() {
        if (get_option('ght_flush_rewrite_rules')) {
            flush_rewrite_rules(false);
            delete_option('ght_flush_rewrite_rules');
        }
    }

    /**
     * ขอให้ Flush Rewrite Rules
     * 
     * เรียกใช้เมื่อต้องการ flush rewrite rules ครั้งถัดไป
     * ใช้ใน:
     * - Plugin activation hook
     * - Settings save ที่เกี่ยวกับ URL
     * 
     * @static
     */
    public static function request_flush_rules() {
        update_option('ght_flush_rewrite_rules', true);
    }

    /**
     * ดึงภาษาจาก Query Variable
     * 
     * ใช้สำหรับดึงภาษาจาก URL ที่ถูก parse แล้ว
     * 
     * @static
     * @return string|null รหัสภาษา หรือ null ถ้าไม่มี
     */
    public static function get_language_from_query() {
        // ตรวจสอบว่า $wp_query พร้อมใช้งานหรือยัง
        // ถ้าเรียกก่อนที่ WordPress จะสร้าง query object จะเกิด fatal error
        global $wp_query;
        if (!$wp_query instanceof \WP_Query) {
            return null;
        }
        
        $lang = get_query_var(self::LANG_QUERY_VAR);
        
        if ($lang && in_array($lang, self::SUPPORTED_LANGUAGES)) {
            return $lang;
        }
        
        return null;
    }

    /**
     * ตรวจสอบว่า URL มี language prefix หรือไม่
     * 
     * ใช้สำหรับตรวจสอบก่อน redirect หรือ filter
     * รองรับทั้ง URL ที่มี trailing slash และไม่มี
     * 
     * @static
     * @param string $url URL ที่ต้องการตรวจสอบ
     * @return bool true ถ้ามี language prefix
     */
    public static function has_language_prefix($url) {
        $langs = implode('|', self::SUPPORTED_LANGUAGES);
        // Pattern รองรับทั้ง:
        // - /en/ (มี trailing slash)
        // - /en (ไม่มี trailing slash, ท้าย URL)
        // - /en?query (มี query string)
        $pattern = '#/(' . $langs . ')(/|\\?|$)#';
        return preg_match($pattern, $url) === 1;
    }

    /**
     * ลบ Language Prefix ออกจาก URL
     * 
     * รองรับทั้ง URL ที่มี trailing slash และไม่มี
     * 
     * @static
     * @param string $url URL ที่มี language prefix
     * @return string URL ที่ไม่มี language prefix
     */
    public static function remove_language_prefix($url) {
        $langs = implode('|', self::SUPPORTED_LANGUAGES);
        // ลบ /en/ หรือ /en (ท้าย URL) หรือ /en?
        // กรณี /en/ → /
        // กรณี /en$ → '' (root)
        $url = preg_replace('#/(' . $langs . ')/#', '/', $url);
        // กรณี URL จบด้วย /en (ไม่มี trailing slash)
        $url = preg_replace('#/(' . $langs . ')(\?|$)#', '$2', $url);
        return $url;
    }

    /**
     * เพิ่ม Language Prefix ใน URL
     * 
     * ข้อควรระวัง:
     * - ห้ามใช้ home_url() โดยตรง เพราะจะเกิด infinite loop กับ filter_home_url
     * - ใช้ get_option('home') แทน
     * - รองรับกรณี http/https mismatch (เช่น reverse proxy, SSL termination)
     * 
     * @static
     * @param string $url URL ที่ต้องการเพิ่ม prefix
     * @param string $lang รหัสภาษา
     * @return string URL พร้อม language prefix
     */
    public static function add_language_prefix($url, $lang) {
        // ภาษาไทยหรือภาษาที่ไม่รองรับ = ไม่เพิ่ม prefix
        if ($lang === 'th' || !in_array($lang, self::SUPPORTED_LANGUAGES)) {
            return $url;
        }
        
        // ตรวจสอบว่ามี prefix อยู่แล้วหรือไม่
        if (self::has_language_prefix($url)) {
            // ลบ prefix เก่าก่อน
            $url = self::remove_language_prefix($url);
        }
        
        // ใช้ get_option('home') แทน home_url() เพื่อป้องกัน infinite loop
        $home = trailingslashit(get_option('home'));
        
        // === แก้ไขปัญหา Protocol Mismatch ===
        // กรณี: get_option('home') คืน http:// แต่ URL จริงเป็น https://
        // (เกิดจาก reverse proxy, SSL termination, หรือ WordPress ตั้งค่าไม่ตรง)
        $home_https = str_replace('http://', 'https://', $home);
        $home_http = str_replace('https://', 'http://', $home);
        
        // ลองแทนที่ด้วย https ก่อน
        if (strpos($url, $home_https) === 0) {
            return str_replace($home_https, $home_https . $lang . '/', $url);
        }
        
        // ลองแทนที่ด้วย http
        if (strpos($url, $home_http) === 0) {
            return str_replace($home_http, $home_http . $lang . '/', $url);
        }
        
        // Fallback: ลองแทนที่แบบเดิม
        $result = str_replace($home, $home . $lang . '/', $url);
        
        // ถ้ายังไม่เปลี่ยน ให้ลองแทรก /{lang}/ หลังจาก domain โดยตรง
        if ($result === $url) {
            // Pattern: https://example.com/path → https://example.com/en/path
            $result = preg_replace(
                '#^(https?://[^/]+)(/.*)?$#',
                '$1/' . $lang . '$2',
                $url
            );
        }
        
        return $result;
    }
}
