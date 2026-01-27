<?php
/**
 * UrlGenerator Class - สร้าง URL สำหรับแต่ละภาษา
 * 
 * ไฟล์นี้รับผิดชอบในการ:
 * 1. สร้าง URL ที่ถูกต้องสำหรับแต่ละภาษา
 * 2. แปลง URL ระหว่างภาษา
 * 3. จัดการ canonical URLs สำหรับ SEO
 * 
 * ตัวอย่างการใช้งาน:
 * ```php
 * // สร้าง URL ภาษาอังกฤษจากหน้าปัจจุบัน
 * $en_url = UrlGenerator::get_translated_url('en');
 * 
 * // สร้าง URL ภาษาไทย (ลบ prefix)
 * $th_url = UrlGenerator::get_translated_url('th');
 * 
 * // สร้าง home URL สำหรับภาษา
 * $en_home = UrlGenerator::get_home_url('en');
 * ```
 * 
 * @package GovHybridTranslator
 * @since 1.8.0
 * @see Router.php สำหรับ rewrite rules
 * @see PATH_BASED_URL_PLAN.md สำหรับรายละเอียดแผนการพัฒนา
 */

namespace GovHybridTranslator\Routing;

// ป้องกันการเข้าถึงไฟล์โดยตรง
if (!defined('ABSPATH')) exit;

class UrlGenerator {

    /**
     * ดึง URL ที่แปลสำหรับภาษาที่กำหนด
     * 
     * แปลง URL ปัจจุบันเป็น URL สำหรับภาษาที่ต้องการ
     * 
     * ตัวอย่าง:
     * - อยู่ที่: example.com/about/
     * - lang: 'en' → example.com/en/about/
     * 
     * - อยู่ที่: example.com/en/contact/
     * - lang: 'th' → example.com/contact/
     * 
     * @static
     * @param string $lang รหัสภาษาเป้าหมาย (en, zh, th, etc.)
     * @param int|null $post_id Post ID (optional, ถ้าไม่ระบุจะใช้หน้าปัจจุบัน)
     * @return string URL สำหรับภาษาที่กำหนด
     */
    public static function get_translated_url($lang, $post_id = null) {
        // ดึง URL ปัจจุบันหรือ URL ของ post ที่ระบุ
        if ($post_id) {
            $current_url = get_permalink($post_id);
        } else {
            $current_url = self::get_current_url();
        }
        
        // ลบ language prefix เดิมออก (ถ้ามี)
        $clean_url = Router::remove_language_prefix($current_url);
        
        // ถ้าเป็นภาษาไทย ไม่ต้องเพิ่ม prefix
        if ($lang === 'th') {
            return $clean_url;
        }
        
        // เพิ่ม language prefix
        return Router::add_language_prefix($clean_url, $lang);
    }

    /**
     * ดึง Home URL สำหรับภาษาที่กำหนด
     * 
     * ข้อควรระวัง: 
     * - ใช้ get_option('home') แทน home_url() เพื่อป้องกัน infinite loop
     * - แก้ไข protocol ให้ตรงกับ request ปัจจุบัน (รองรับ reverse proxy)
     * 
     * @static
     * @param string $lang รหัสภาษา
     * @return string Home URL สำหรับภาษานั้น
     */
    public static function get_home_url($lang = 'th') {
        // ใช้ get_option แทน home_url เพื่อป้องกัน infinite loop
        $home = trailingslashit(get_option('home'));
        
        // === แก้ไข Protocol ให้ตรงกับ Request ปัจจุบัน ===
        // เพื่อป้องกันปัญหา http/https mismatch
        if (is_ssl() && strpos($home, 'http://') === 0) {
            $home = str_replace('http://', 'https://', $home);
        }
        
        if ($lang === 'th') {
            return $home;
        }
        
        return $home . $lang . '/';
    }

    /**
     * ดึง URL ปัจจุบัน
     * 
     * สร้าง URL จาก $_SERVER variables
     * รวม protocol, host, และ request URI
     * 
     * @static
     * @return string URL ปัจจุบัน
     */
    public static function get_current_url() {
        $protocol = is_ssl() ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        return $protocol . $host . $uri;
    }

    /**
     * ดึง URL ปัจจุบันโดยไม่มี Query String
     * 
     * ใช้สำหรับสร้าง clean URL ก่อนเพิ่ม language prefix
     * 
     * @static
     * @return string URL ปัจจุบันไม่มี query string
     */
    public static function get_current_url_without_query() {
        $url = self::get_current_url();
        return strtok($url, '?');
    }

    /**
     * สร้าง Permalink สำหรับ Post ในภาษาที่กำหนด
     * 
     * ใช้สำหรับ filter 'post_link' และ 'page_link'
     * 
     * @static
     * @param string $permalink Permalink เดิม
     * @param int $post_id Post ID
     * @param string $lang รหัสภาษา
     * @return string Permalink สำหรับภาษานั้น
     */
    public static function get_post_permalink($permalink, $post_id, $lang) {
        if ($lang === 'th') {
            return Router::remove_language_prefix($permalink);
        }
        
        return Router::add_language_prefix($permalink, $lang);
    }

    /**
     * สร้าง Term Link สำหรับ Category/Tag ในภาษาที่กำหนด
     * 
     * @static
     * @param string $termlink Term link เดิม
     * @param object $term Term object
     * @param string $lang รหัสภาษา
     * @return string Term link สำหรับภาษานั้น
     */
    public static function get_term_link($termlink, $term, $lang) {
        if ($lang === 'th') {
            return Router::remove_language_prefix($termlink);
        }
        
        return Router::add_language_prefix($termlink, $lang);
    }

    /**
     * สร้าง Archive Link ในภาษาที่กำหนด
     * 
     * @static
     * @param string $link Archive link เดิม
     * @param string $lang รหัสภาษา
     * @return string Archive link สำหรับภาษานั้น
     */
    public static function get_archive_link($link, $lang) {
        if ($lang === 'th') {
            return Router::remove_language_prefix($link);
        }
        
        return Router::add_language_prefix($link, $lang);
    }

    /**
     * ตรวจสอบว่า URL เป็นภาษาอะไร
     * 
     * @static
     * @param string $url URL ที่ต้องการตรวจสอบ
     * @return string รหัสภาษา (th ถ้าไม่พบ prefix)
     */
    public static function detect_language_from_url($url) {
        $pattern = '#/(' . implode('|', Router::SUPPORTED_LANGUAGES) . ')/#';
        
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
        
        return 'th';
    }

    /**
     * สร้าง Absolute URL จาก Relative URL
     * 
     * ข้อควรระวัง: ใช้ get_option แทน home_url เพื่อป้องกัน infinite loop
     * 
     * @static
     * @param string $relative_url Relative URL
     * @return string Absolute URL
     */
    public static function to_absolute_url($relative_url) {
        // ถ้าเป็น absolute อยู่แล้ว
        if (strpos($relative_url, 'http') === 0) {
            return $relative_url;
        }
        
        // ใช้ get_option แทน home_url เพื่อป้องกัน infinite loop
        $home = trailingslashit(get_option('home'));
        return $home . ltrim($relative_url, '/');
    }

    /**
     * สร้าง Canonical URL สำหรับ SEO
     * 
     * Canonical URL ควรเป็น URL ที่สะอาด ไม่มี query params ที่ไม่จำเป็น
     * 
     * @static
     * @param string $lang รหัสภาษา
     * @param int|null $post_id Post ID (optional)
     * @return string Canonical URL
     */
    public static function get_canonical_url($lang, $post_id = null) {
        $url = self::get_translated_url($lang, $post_id);
        
        // ลบ query string ออก
        return strtok($url, '?');
    }

    /**
     * สร้าง Hreflang Links สำหรับ SEO
     * 
     * ใช้สำหรับบอก Search Engines ว่ามีหน้าเดียวกันในภาษาอื่น
     * 
     * ตัวอย่าง output:
     * <link rel="alternate" hreflang="th" href="example.com/about/" />
     * <link rel="alternate" hreflang="en" href="example.com/en/about/" />
     * 
     * @static
     * @param int|null $post_id Post ID (optional)
     * @return array Array ของ hreflang data
     */
    public static function get_hreflang_links($post_id = null) {
        $links = [];
        
        // เพิ่มภาษาไทย (default)
        $links[] = [
            'hreflang' => 'th',
            'href' => self::get_canonical_url('th', $post_id),
        ];
        
        // เพิ่มภาษาที่รองรับ
        foreach (Router::SUPPORTED_LANGUAGES as $lang) {
            $links[] = [
                'hreflang' => $lang,
                'href' => self::get_canonical_url($lang, $post_id),
            ];
        }
        
        // เพิ่ม x-default (ใช้สำหรับ language selector pages)
        $links[] = [
            'hreflang' => 'x-default',
            'href' => self::get_canonical_url('th', $post_id),
        ];
        
        return $links;
    }
}
