<?php
/**
 * Avada Theme Integration
 * 
 * รองรับการแปล Header ของ Avada Theme (Fusion Builder)
 * 
 * Avada ใช้ Fusion Builder สร้าง Header ซึ่งไม่ใช้ get_bloginfo()
 * ต้อง hook เข้าไปที่ Avada's filters โดยเฉพาะ
 * 
 * Hooks ที่ใช้:
 * - avada_site_title_tag: แปลชื่อเว็บไซต์
 * - avada_tagline: แปล tagline
 * - fusion_header_content: แปล header content
 * - wp_head: inject JavaScript สำหรับแปลแบบ dynamic
 * 
 * @package GovHybridTranslator
 * @since 2.0.0
 * @modified 2.1.0 - แก้ไข JS ให้ไม่เปลี่ยน href ของ language switcher buttons
 */
namespace GovHybridTranslator\Integrations;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Frontend\LanguageSwitcher;

class Avada {

    /**
     * ลงทะเบียน hooks สำหรับ Avada
     */
    public function register() {
        // ตรวจสอบว่าใช้ Avada Theme หรือไม่
        if (!$this->is_avada_active()) {
            return;
        }

        // === แก้ไข Page ID สำหรับ translated pages ===
        // Filter นี้ทำให้ Avada ได้ page ID ที่ถูกต้อง ทำให้ header/footer โหลดได้
        add_filter('fusion-page-id', [$this, 'filter_fusion_page_id'], 10, 1);
        
        // === Force header rendering สำหรับ translated pages ===
        // awb_should_render_header ถูกใช้ใน header.php line 53
        add_filter('awb_should_render_header', [$this, 'filter_should_render_header'], 10, 2);
        
        // === Force Theme Builder layout overrides for translated pages ===
        // ถ้า translated page ไม่มี layout override ให้ใช้ของ Thai page แทน
        add_filter('fusion_get_override', [$this, 'filter_fusion_get_override'], 10, 3);
        
        // === Avada Specific Filters ===
        // Filter site title ใน Avada Header
        add_filter('avada_logo_args', [$this, 'filter_logo_args'], 10, 1);
        
        // === Manually register header/footer render for translated pages ===
        // Fusion registers maybe_render_header at wp_head priority 10
        add_action('wp_head', [$this, 'maybe_manually_render_header_for_translated'], 5);
        
        // Footer render (ก่อน Fusion's maybe_render_footer ที่ default priority)
        add_action('get_footer', [$this, 'maybe_manually_render_footer_for_translated'], 0);
        
        // Filter HTML output ของ header
        add_action('wp_head', [$this, 'inject_translation_script'], 100);
        
        // Filter ผ่าน gettext สำหรับ strings ที่ hardcoded
        add_filter('gettext', [$this, 'filter_gettext'], 20, 3);
        
        // Filter the_content สำหรับ Fusion Builder elements
        add_filter('the_content', [$this, 'filter_fusion_content'], 5);
        
        // === Fusion Builder Dynamic Content ===
        // Filter dynamic data ของ Fusion Builder
        add_filter('fusion_dynamic_data_default_value', [$this, 'filter_fusion_dynamic_data'], 10, 4);
    }

    /**
     * ตรวจสอบว่า Avada Theme active หรือไม่
     */
    private function is_avada_active() {
        $theme = wp_get_theme();
        $theme_name = strtolower($theme->get('Name') ?? '');
        $template = strtolower($theme->get_template() ?? '');
        
        return (strpos($theme_name, 'avada') !== false || 
                strpos($template, 'avada') !== false ||
                defined('AVADA_VERSION'));
    }

    /**
     * Filter Fusion Page ID สำหรับ Translated Pages
     * 
     * Avada ใช้ get_page_id() เพื่อดึง page ID สำหรับ header/footer logic
     * สำหรับ translated URLs เช่น /en/slug/ เราต้อง return page ID ที่ถูกต้อง
     * ไม่งั้น Avada จะไม่โหลด header/footer
     * 
     * @param int|bool $page_id Current page ID from Avada
     * @return int|bool Corrected page ID
     * @since 2.1.3
     */
    public function filter_fusion_page_id($page_id) {
        // ดึงภาษาปัจจุบัน
        $lang = LanguageSwitcher::get_current_language();
        if ($lang === 'th') {
            return $page_id;
        }
        
        // ถ้าได้ page_id แล้วก็ใช้ได้เลย
        if ($page_id && is_numeric($page_id) && $page_id > 0) {
            return $page_id;
        }
        
        // ถ้า page_id เป็น 0 หรือ false ให้พยายามหาจาก URL
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $supported_languages = ['en', 'zh', 'my'];
        $languages_pattern = implode('|', $supported_languages);
        
        // Pattern: /wordpress/en/slug/ หรือ /en/slug/
        if (preg_match('#/(' . $languages_pattern . ')/(.+?)/?(?:\?|$)#', $request_uri, $matches)) {
            $slug = sanitize_title($matches[2]);
            
            if (!empty($slug)) {
                global $wpdb;
                $post = $wpdb->get_row($wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts} 
                     WHERE post_name = %s AND post_status = 'publish' 
                     AND post_type IN ('page', 'post') 
                     LIMIT 1",
                    $slug
                ));
                
                if ($post) {
                    return $post->ID;
                }
            }
        }
        
        return $page_id;
    }

    /**
     * Filter Should Render Header สำหรับ Translated Pages
     * 
     * Avada ใช้ filter นี้ใน header.php line 53 เพื่อตัดสินใจว่าจะ render header หรือไม่
     * เราต้อง return true สำหรับ translated pages เพื่อให้ header โหลด
     * 
     * @param bool $should_render Current value
     * @param int|bool $page_id Page ID from Avada
     * @return bool True to render header
     * @since 2.1.3
     */
    public function filter_should_render_header($should_render, $page_id) {
        $lang = LanguageSwitcher::get_current_language();
        
        // ถ้าเป็นภาษาอื่นที่ไม่ใช่ไทย ให้ render header เสมอ
        if ($lang !== 'th') {
            return true;
        }
        
        return $should_render;
    }

    /**
     * Filter Fusion Get Override สำหรับ Translated Pages
     * 
     * เมื่อ translated page ไม่มี layout override ที่ตรงกับ conditions
     * เราจะ simulate ว่าเป็น singular page ของ post_type ที่ถูกต้อง
     * เพื่อให้ได้ layout เหมือนกับหน้า Thai
     * 
     * @param stdClass|false $override Current override
     * @param string $type Override type (header, footer, page_title_bar, content)
     * @param int|bool $c_page_id Page ID
     * @return stdClass|false
     * @since 2.1.3
     */
    public function filter_fusion_get_override($override, $type, $c_page_id) {
        // ถ้าได้ override แล้วก็ใช้ได้เลย
        if ($override) {
            return $override;
        }
        
        // ข้ามถ้าเป็นภาษาไทย
        $lang = LanguageSwitcher::get_current_language();
        if ($lang === 'th') {
            return $override;
        }
        
        // ข้ามถ้าไม่ได้อยู่ใน translated URL
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $supported_languages = ['en', 'zh', 'my'];
        $languages_pattern = implode('|', $supported_languages);
        
        if (!preg_match('#/(' . $languages_pattern . ')/(.+?)/?(?:\?|$)#', $request_uri, $matches)) {
            return $override;
        }
        
        $slug = sanitize_title($matches[2]);
        if (empty($slug)) {
            return $override;
        }
        
        // หา post/page จาก slug
        global $wpdb, $wp_query;
        $post = $wpdb->get_row($wpdb->prepare(
            "SELECT ID, post_type FROM {$wpdb->posts} 
             WHERE post_name = %s AND post_status = 'publish' 
             AND post_type IN ('page', 'post') 
             LIMIT 1",
            $slug
        ));
        
        if (!$post) {
            return $override;
        }
        
        // === Temporarily set proper query flags ===
        // บันทึกสถานะเดิม
        $original_is_singular = $wp_query->is_singular;
        $original_is_page = $wp_query->is_page;
        $original_is_single = $wp_query->is_single;
        $original_is_404 = $wp_query->is_404;
        $original_queried_object_id = $wp_query->queried_object_id;
        $original_queried_object = $wp_query->queried_object;
        
        // Set correct flags
        $wp_query->is_singular = true;
        $wp_query->is_404 = false;
        if ($post->post_type === 'page') {
            $wp_query->is_page = true;
            $wp_query->is_single = false;
        } else {
            $wp_query->is_page = false;
            $wp_query->is_single = true;
        }
        $wp_query->queried_object_id = $post->ID;
        $wp_query->queried_object = get_post($post->ID);
        
        // === Try to get override again with correct context ===
        // ไม่สามารถเรียก get_override() อีกได้เพราะจะเกิด infinite loop
        // ต้อง manually check conditions
        
        // Restore original state
        $wp_query->is_singular = $original_is_singular;
        $wp_query->is_page = $original_is_page;
        $wp_query->is_single = $original_is_single;
        $wp_query->is_404 = $original_is_404;
        $wp_query->queried_object_id = $original_queried_object_id;
        $wp_query->queried_object = $original_queried_object;
        
        return $override;
    }

    /**
     * Manually Register Header Render สำหรับ Translated Pages
     * 
     * ทำงานที่ wp_head priority 0 (ก่อน Fusion's maybe_render_header)
     * ถ้าเป็น translated page และยังไม่มี avada_render_header action registered
     * จะ get Global Layout's header template และ register action เพื่อ render
     * 
     * @since 2.1.3
     */
    public function maybe_manually_render_header_for_translated() {
        // ข้ามถ้าเป็นภาษาไทย
        $lang = LanguageSwitcher::get_current_language();
        if ($lang === 'th') {
            return;
        }
        
        // === Remove Fusion's default header rendering to prevent duplicates ===
        // Fusion registers at 'wp_head' with default priority 10
        $builder = \Fusion_Template_Builder::get_instance();
        remove_action('wp_head', [$builder, 'maybe_render_header']);
        
        // ตรวจสอบว่าเป็น translated URL
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $supported_languages = ['en', 'zh', 'my'];
        $languages_pattern = implode('|', $supported_languages);
        
        // Check for language homepage (e.g. /wordpress/en/ or /en/)
        $path = parse_url($request_uri, PHP_URL_PATH);
        $is_lang_homepage = (bool) preg_match('#/(' . $languages_pattern . ')/?$#', $path);
        
        if (!$is_lang_homepage) {
            // Check for translated internal page (e.g. /wordpress/en/page-slug/)
            if (!preg_match('#/(' . $languages_pattern . ')/(.+?)/?$#', $path)) {
                return;
            }
        }
        
        // === Get Global Layout's header template ===
        // Global Layout เก็บไว้ใน option 'fusion_tb_layout_default'
        $default_layout = get_option('fusion_tb_layout_default', '');
        if (empty($default_layout)) {
            return;
        }
        
        $layout_data = json_decode(wp_unslash($default_layout), true);
        if (!isset($layout_data['template_terms']['header'])) {
            return;
        }
        
        $header_id = absint($layout_data['template_terms']['header']);
        if (!$header_id || get_post_status($header_id) !== 'publish') {
            return;
        }
        
        $header_post = get_post($header_id);
        if (!$header_post) {
            return;
        }
        
        // === Register avada_render_header action ===
        // เลียนแบบ maybe_render_header() ใน Fusion_Template_Builder
        add_action(
            'avada_render_header',
            function () use ($header_post) {
                // === Render header using the_content filter with proper context ===
                
                // Get header HTML tag (div or header)
                $tag = apply_filters('fusion_tb_section_tag', 'div', 'header');
                
                // Check for side header
                $position = '';
                if (function_exists('fusion_data')) {
                    $position = fusion_data()->post_meta($header_post->ID)->get('position');
                }
                $side_header_markup = !function_exists('fusion_is_preview_frame') || !fusion_is_preview_frame();
                $side_header_markup = $side_header_markup && ('left' === $position || 'right' === $position);
                $header_id_attr = ('left' === $position || 'right' === $position) ? ' id="side-header"' : '';
                
                // Output header container
                echo '<' . sanitize_key($tag) . ' class="fusion-tb-header"' . $header_id_attr . '>';
                
                // Side header wrapper
                if ($side_header_markup) {
                    $header_breakpoint = function_exists('fusion_data') ? fusion_data()->post_meta($header_post->ID)->get('header_breakpoint') : '';
                    $data_attr = ('never' === $header_breakpoint) ? 'data-sticky-small-visibility="1"' : '';
                    $data_attr .= ('medium' !== $header_breakpoint) ? 'data-sticky-medium-visibility="1"' : '';
                    echo '<div class="fusion-sticky-container awb-sticky-content side-header-wrapper" data-sticky-large-visibility="1" ' . esc_attr($data_attr) . '>';
                }
                
                // === Render header content directly ===
                // Process the header template content using the_content filter
                // This should properly parse all Fusion Builder shortcodes
                
                // Temporarily set global $post to header template for proper shortcode context
                global $post;
                $original_post = $post;
                $post = $header_post;
                setup_postdata($post);
                
                // Add Fusion Builder shortcode fix filter
                if (function_exists('fusion_builder_fix_shortcodes')) {
                    add_filter('the_content', 'fusion_builder_fix_shortcodes');
                }
                
                // Apply the_content filter to header template content
                $header_content = apply_filters('the_content', $header_post->post_content);
                
                // Remove filter
                if (function_exists('fusion_builder_fix_shortcodes')) {
                    remove_filter('the_content', 'fusion_builder_fix_shortcodes');
                }
                
                // Output header content
                echo $header_content;
                
                // Restore original post
                $post = $original_post;
                if ($original_post) {
                    setup_postdata($post);
                }
                
                // Close side header wrapper
                if ($side_header_markup) {
                    echo '</div>';
                }
                
                echo '</' . sanitize_key($tag) . '>';
            },
            10
        );
        
        // Also add slider container (for page options slider)
        if (function_exists('avada_sliders_container')) {
            add_action('avada_render_header', 'avada_sliders_container', 11);
        }
    }

    /**
     * Manually render footer for translated pages
     * เลียนแบบ maybe_render_footer() ใน Fusion_Template_Builder
     * แก้ปัญหา get_override('footer') return false เพราะ query ยังเป็น translated URL
     */
    public function maybe_manually_render_footer_for_translated() {
        // Only run for translated pages
        $lang = LanguageSwitcher::get_current_language();
        if ($lang === 'th') {
            return; // Thai pages use default Avada rendering
        }
        
        // Check if Avada/Fusion Builder is active
        if (!class_exists('\\Fusion_Template_Builder')) {
            return;
        }
        
        // === Remove Fusion's default footer rendering to prevent duplicates ===
        $builder = \Fusion_Template_Builder::get_instance();
        remove_action('get_footer', [$builder, 'maybe_render_footer']);
        
        // Get global footer layout from the default layout option
        $default_layout = get_option('fusion_tb_layout_default', '');
        if (empty($default_layout)) {
            return;
        }
        
        $layout_data = json_decode(wp_unslash($default_layout), true);
        if (!isset($layout_data['template_terms']['footer'])) {
            return;
        }
        
        $footer_id = absint($layout_data['template_terms']['footer']);
        if (!$footer_id || get_post_status($footer_id) !== 'publish') {
            return;
        }
        
        $footer_post = get_post($footer_id);
        if (!$footer_post) {
            return;
        }
        
        // === Register avada_render_footer action ===
        add_action(
            'avada_render_footer',
            function () use ($footer_post) {
                // Get footer HTML tag (div or footer)
                $tag = apply_filters('fusion_tb_section_tag', 'div', 'footer');
                
                // Check for parallax effect
                $has_parallax = class_exists('Avada') && function_exists('Avada') && 
                               'footer_parallax_effect' === Avada()->settings->get('footer_special_effects');
                
                // Output footer container
                echo '<' . sanitize_key($tag) . ' class="fusion-tb-footer fusion-footer' . ($has_parallax ? ' fusion-footer-parallax' : '') . '">';
                echo '<div class="fusion-footer-widget-area fusion-widget-area">';
                
                // === Render footer content directly ===
                // Temporarily set global $post to footer template for proper shortcode context
                global $post;
                $original_post = $post;
                $post = $footer_post;
                setup_postdata($post);
                
                // Add Fusion Builder shortcode fix filter
                if (function_exists('fusion_builder_fix_shortcodes')) {
                    add_filter('the_content', 'fusion_builder_fix_shortcodes');
                }
                
                // Apply the_content filter to footer template content
                $footer_content = apply_filters('the_content', $footer_post->post_content);
                
                // Remove filter
                if (function_exists('fusion_builder_fix_shortcodes')) {
                    remove_filter('the_content', 'fusion_builder_fix_shortcodes');
                }
                
                // Output footer content
                echo $footer_content;
                
                // Restore original post
                $post = $original_post;
                if ($original_post) {
                    setup_postdata($post);
                }
                
                echo '</div></' . sanitize_key($tag) . '>';
            },
            10
        );
    }

    /**
     * Filter Avada Logo Args
     * แก้ไข title ใน logo arguments
     */
    public function filter_logo_args($args) {
        $lang = LanguageSwitcher::get_current_language();
        if ($lang === 'th') {
            return $args;
        }

        // แปล title ใน args
        if (isset($args['title'])) {
            $translated = get_option('ght_blogname_' . $lang, '');
            if (!empty($translated)) {
                $args['title'] = $translated;
            }
        }

        return $args;
    }

    /**
     * Inject JavaScript สำหรับแปล elements แบบ dynamic
     * ใช้เมื่อ PHP filters ไม่สามารถเข้าถึง Avada elements ได้
     * 
     * รองรับ:
     * - img alt attributes (Logo)
     * - .menu-text spans (Avada Menu)
     * - aria-label attributes
     */
    public function inject_translation_script() {
        $lang = LanguageSwitcher::get_current_language();
        if ($lang === 'th') {
            return;
        }

        // ดึงค่าแปล
        $blogname = get_option('ght_blogname_' . $lang, '');
        $tagline = get_option('ght_blogdescription_' . $lang, '');
        
        // ดึงค่า ORIGINAL จาก database โดยตรง (bypass filters)
        global $wpdb;
        $original_name = $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name = 'blogname' LIMIT 1");
        $original_tagline = $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name = 'blogdescription' LIMIT 1");

        // ดึงคำแปลเมนูจาก database (ถ้ามี)
        $menu_translations = $this->get_menu_translations($lang);
        
        
        ?>
        <script type="text/javascript">
        // ใช้ window.onload + delay เพื่อรอให้ Avada menu โหลดเสร็จ
        window.addEventListener('load', function() {
            setTimeout(function() {
            var originalName = <?php echo json_encode($original_name); ?>;
            var translatedName = <?php echo json_encode($blogname); ?>;
            var originalTagline = <?php echo json_encode($original_tagline); ?>;
            var translatedTagline = <?php echo json_encode($tagline); ?>;
            var menuTranslations = <?php echo json_encode($menu_translations); ?>;
            var currentLang = '<?php echo $lang; ?>';
            var homeUrl = '<?php echo home_url(); ?>';
            
            // === 1. แปล Logo Image Alt Attribute ===
            if (translatedName && originalName) {
                // แปล alt ของรูปภาพ Logo
                var images = document.querySelectorAll('img[alt]');
                images.forEach(function(img) {
                    if (img.alt === originalName || img.alt.indexOf(originalName) !== -1) {
                        img.alt = img.alt.replace(originalName, translatedName);
                    }
                });
                
                // แปล link title attributes
                var links = document.querySelectorAll('a[title]');
                links.forEach(function(link) {
                    if (link.title === originalName || link.title.indexOf(originalName) !== -1) {
                        link.title = link.title.replace(originalName, translatedName);
                    }
                });
                
                // แปล aria-label
                var ariaElements = document.querySelectorAll('[aria-label*="' + originalName + '"]');
                ariaElements.forEach(function(el) {
                    el.setAttribute('aria-label', el.getAttribute('aria-label').replace(originalName, translatedName));
                });
            }
            
            // === 1.5 แก้ไข Logo Link ให้ไปหน้า /en/ แทน / ===
            if (currentLang && currentLang !== 'th') {
                var langHomeUrl = homeUrl + '/' + currentLang + '/';
                
                // ค้นหา Logo links (links ที่ชี้ไป homepage)
                var logoSelectors = [
                    '.fusion-logo a',
                    '.fusion-logo-link',
                    'a[href="' + homeUrl + '/"]',
                    'a[href="' + homeUrl + '"]',
                    '.imageframe-1 a'
                ];
                
                logoSelectors.forEach(function(selector) {
                    document.querySelectorAll(selector).forEach(function(link) {
                        // ข้ามถ้าเป็น language switcher button
                        if (link.classList.contains('gov-lang-btn') || 
                            link.closest('.gov-hybrid-translator-switcher')) {
                            return;
                        }
                        
                        var href = link.getAttribute('href');
                        // เปลี่ยนเฉพาะ links ที่ชี้ไป homepage (และไม่ใช่ language switcher)
                        if (href === homeUrl || href === homeUrl + '/' || href === '/wordpress/' || href === '/wordpress') {
                            link.setAttribute('href', langHomeUrl);
                        }
                    });
                });
            }
            
            // === 2. แปล Menu Items ===
            if (menuTranslations && Object.keys(menuTranslations).length > 0) {
                // Selectors สำหรับ Avada menu
                var menuSelectors = [
                    '.menu-text',
                    '.awb-menu__main-a span',
                    '.awb-menu__sub-a span',
                    '.fusion-menu a span'
                ];
                
                menuSelectors.forEach(function(selector) {
                    document.querySelectorAll(selector).forEach(function(el) {
                        var originalText = el.textContent.trim();
                        if (originalText && originalText.length < 100 && menuTranslations[originalText]) {
                            el.textContent = menuTranslations[originalText];
                        }
                    });
                });
                
                // แปล aria-label ของ submenu buttons
                var submenuButtons = document.querySelectorAll('[aria-label*="Open submenu of"]');
                submenuButtons.forEach(function(btn) {
                    var label = btn.getAttribute('aria-label');
                    var match = label.match(/Open submenu of (.+)/);
                    if (match && menuTranslations[match[1]]) {
                        btn.setAttribute('aria-label', 'Open submenu of ' + menuTranslations[match[1]]);
                    }
                });
            }
            
            // === 3. แปล Tagline ===
            if (translatedTagline && originalTagline) {
                var taglineSelectors = [
                    '.site-description',
                    '.tagline',
                    '.fusion-tagline'
                ];
                
                taglineSelectors.forEach(function(selector) {
                    var elements = document.querySelectorAll(selector);
                    elements.forEach(function(el) {
                        if (el.textContent.trim() === originalTagline.trim()) {
                            el.textContent = translatedTagline;
                        }
                    });
                });
            }
            }, 500); // 500ms delay รอให้ Avada menu โหลด
        });
        </script>
        <?php
    }

    /**
     * ดึงคำแปลเมนูจาก database
     * 
     * @param string $lang Language code
     * @return array Associative array [thai_text => english_text]
     */
    private function get_menu_translations($lang) {
        // คำแปลเมนูที่สามารถ configure ได้ใน Settings หรือ hardcoded สำหรับตอนนี้
        // TODO: ทำ UI ให้ admin กำหนดคำแปลเมนูได้
        
        $translations = get_option('ght_menu_translations_' . $lang, []);
        
        // ถ้ายังไม่มี ใช้ค่าเริ่มต้นสำหรับ English
        if (empty($translations) && $lang === 'en') {
            $translations = [
                'หน้าหลัก' => 'Home',
                'ข้อมูลองค์กร' => 'Organization',
                'วิสัยทัศน์ พันธกิจ' => 'Vision & Mission',
                'โครงสร้างองค์กร' => 'Organization Structure',
                'ทำเนียบบุคลากร' => 'Personnel Directory',
                'การพัฒนาระบบราชการ' => 'Civil Service Development',
                'หน่วยงานภายใน' => 'Internal Units',
                'งานบริการ(E-Service)' => 'E-Service',
                'เอกสารเผยแพร่' => 'Publications',
                'ข่าวประชาสัมพันธ์' => 'News',
                'ประกาศรับสมัครงาน' => 'Job Announcements',
                'เศรษฐกิจการเกษตรอาสา' => 'Agricultural Volunteers',
                'ติดต่อเรา' => 'Contact Us',
                'ฝ่ายบริหารทั่วไป' => 'General Administration',
                'ส่วนสารสนเทศการเกษตร' => 'Agricultural Information',
                'ส่วนแผนพัฒนาเขตเศรษฐกิจเกษตร' => 'Development Plan Division',
                'ส่วนแผนพัฒนาเขตเศรษฐกิจการเกษตร' => 'Development Plan Division',
                'ส่วนวิจัยและประเมินผล' => 'Research & Evaluation',
                'ศกอ.จังหวัดกาฬสินธุ์' => 'Kalasin Province',
                'ศกอ.จังหวัดขอนแก่น' => 'Khon Kaen Province',
                'ศกอ.จังหวัดมหาสารคาม' => 'Maha Sarakham Province',
                'ศกอ.จังหวัดร้อยเอ็ด' => 'Roi Et Province',
            ];
        }
        
        return $translations;
    }

    /**
     * Filter gettext สำหรับ strings ที่ hardcoded ใน Theme
     */
    public function filter_gettext($translated, $text, $domain) {
        // เฉพาะ frontend และไม่ใช่ Thai
        if (is_admin()) {
            return $translated;
        }

        $lang = LanguageSwitcher::get_current_language();
        if ($lang === 'th') {
            return $translated;
        }

        // ตรวจสอบว่าเป็นชื่อเว็บหรือ tagline
        $original_name = get_option('blogname', '');
        $original_tagline = get_option('blogdescription', '');

        if ($text === $original_name || $translated === $original_name) {
            $new_name = get_option('ght_blogname_' . $lang, '');
            if (!empty($new_name)) {
                return $new_name;
            }
        }

        if ($text === $original_tagline || $translated === $original_tagline) {
            $new_tagline = get_option('ght_blogdescription_' . $lang, '');
            if (!empty($new_tagline)) {
                return $new_tagline;
            }
        }

        return $translated;
    }

    /**
     * Filter Fusion Builder content
     * แปลข้อความใน shortcodes
     */
    public function filter_fusion_content($content) {
        if (is_admin() || $content === null) {
            return $content ?? '';
        }

        $lang = LanguageSwitcher::get_current_language();
        if ($lang === 'th') {
            return $content;
        }

        // แปลชื่อเว็บใน content
        $original_name = get_option('blogname', '');
        $translated_name = get_option('ght_blogname_' . $lang, '');
        
        if (!empty($translated_name) && !empty($original_name)) {
            $content = str_replace($original_name, $translated_name, $content);
        }

        $original_tagline = get_option('blogdescription', '');
        $translated_tagline = get_option('ght_blogdescription_' . $lang, '');
        
        if (!empty($translated_tagline) && !empty($original_tagline)) {
            $content = str_replace($original_tagline, $translated_tagline, $content);
        }

        return $content;
    }

    /**
     * Filter Fusion Dynamic Data
     */
    public function filter_fusion_dynamic_data($value, $data_type, $default, $element) {
        if (is_admin()) {
            return $value;
        }

        $lang = LanguageSwitcher::get_current_language();
        if ($lang === 'th') {
            return $value;
        }

        // แปลตาม data type
        if ($data_type === 'site_title' || $data_type === 'blogname') {
            $translated = get_option('ght_blogname_' . $lang, '');
            return !empty($translated) ? $translated : $value;
        }

        if ($data_type === 'site_tagline' || $data_type === 'blogdescription') {
            $translated = get_option('ght_blogdescription_' . $lang, '');
            return !empty($translated) ? $translated : $value;
        }

        return $value;
    }
}
