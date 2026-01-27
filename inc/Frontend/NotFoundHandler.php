<?php
/**
 * Not Found (404) Handler - รองรับ 404 หลายภาษา
 * 
 * ไฟล์นี้จัดการการแสดงหน้า 404 ตามภาษาที่ผู้ใช้เลือก
 * เมื่อเข้าถึง URL ที่ไม่มีอยู่ (เช่น /en/notfound/) 
 * ระบบจะแสดงข้อความ 404 ในภาษาอังกฤษ
 * 
 * === การทำงาน ===
 * 1. ตรวจจับภาษาจาก URL path (/en/, /zh/, etc.)
 * 2. Filter document title ให้เป็นภาษาที่ถูกต้อง
 * 3. ให้ helper functions สำหรับ theme ใช้แสดงข้อความ 404
 * 
 * @package GovHybridTranslator
 * @since 2.1.0
 */

namespace GovHybridTranslator\Frontend;

// ป้องกันการเข้าถึงไฟล์โดยตรง
if (!defined('ABSPATH')) exit;

class NotFoundHandler {

    /**
     * ข้อความ 404 เริ่มต้นสำหรับแต่ละภาษา
     * 
     * @var array
     */
    private $default_messages = [
        'th' => [
            'title'   => 'ไม่พบหน้าที่ต้องการ',
            'heading' => '404 - ไม่พบหน้า',
            'message' => 'ขออภัย ไม่พบหน้าที่คุณกำลังค้นหา กรุณาตรวจสอบ URL หรือกลับไปหน้าหลัก',
            'button'  => 'กลับหน้าหลัก'
        ],
        'en' => [
            'title'   => 'Page Not Found',
            'heading' => '404 - Page Not Found',
            'message' => 'Sorry, the page you are looking for could not be found. Please check the URL or return to the homepage.',
            'button'  => 'Back to Homepage'
        ],
        'zh' => [
            'title'   => '找不到页面',
            'heading' => '404 - 页面未找到',
            'message' => '抱歉，找不到您要查找的页面。请检查网址或返回首页。',
            'button'  => '返回首页'
        ],
        'ja' => [
            'title'   => 'ページが見つかりません',
            'heading' => '404 - ページが見つかりません',
            'message' => '申し訳ありませんが、お探しのページが見つかりませんでした。URLを確認するか、ホームページに戻ってください。',
            'button'  => 'ホームページへ戻る'
        ],
        'ko' => [
            'title'   => '페이지를 찾을 수 없습니다',
            'heading' => '404 - 페이지를 찾을 수 없음',
            'message' => '죄송합니다. 찾고 계신 페이지를 찾을 수 없습니다. URL을 확인하거나 홈페이지로 돌아가세요.',
            'button'  => '홈페이지로 돌아가기'
        ],
        'vi' => [
            'title'   => 'Không tìm thấy trang',
            'heading' => '404 - Không tìm thấy trang',
            'message' => 'Xin lỗi, không tìm thấy trang bạn đang tìm kiếm. Vui lòng kiểm tra URL hoặc quay lại trang chủ.',
            'button'  => 'Quay lại trang chủ'
        ],
        'my' => [
            'title'   => 'စာမျက်နှာ မတွေ့ပါ',
            'heading' => '404 - စာမျက်နှာ မတွေ့ပါ',
            'message' => 'တောင်းပန်ပါတယ်၊ သင်ရှာဖွေနေသော စာမျက်နှာကို ရှာမတွေ့ပါ။ URL ကို စစ်ဆေးပါ သို့မဟုတ် ပင်မစာမျက်နှာသို့ ပြန်သွားပါ။',
            'button'  => 'ပင်မစာမျက်နှာသို့ ပြန်သွားပါ'
        ],
    ];

    /**
     * ลงทะเบียน hooks
     * 
     * Hooks ที่ใช้:
     * - document_title_parts: เปลี่ยน title ของหน้า 404
     */
    public function register() {
        // Filter document title สำหรับ 404
        add_filter('document_title_parts', [$this, 'filter_404_title']);
    }

    /**
     * Filter document title สำหรับหน้า 404
     * 
     * เปลี่ยน title ของหน้า 404 ให้เป็นภาษาที่ตรงกับ URL path
     * 
     * @param array $title_parts Title parts array
     * @return array Modified title parts
     */
    public function filter_404_title($title_parts) {
        // ตรวจสอบว่าเป็นหน้า 404 หรือไม่
        if (!is_404()) {
            return $title_parts;
        }

        // ดึงภาษาปัจจุบัน
        $lang = LanguageSwitcher::get_current_language();
        
        // ดึงข้อความ 404 สำหรับภาษานี้
        $messages = $this->get_messages($lang);
        
        // เปลี่ยน title
        $title_parts['title'] = $messages['title'];
        
        return $title_parts;
    }

    /**
     * ดึงข้อความ 404 สำหรับภาษาที่กำหนด
     * 
     * @param string $lang รหัสภาษา (th, en, zh, etc.)
     * @return array ข้อความ 404 (title, heading, message, button)
     */
    public function get_messages($lang = null) {
        // ใช้ภาษาปัจจุบันถ้าไม่ระบุ
        if ($lang === null) {
            $lang = LanguageSwitcher::get_current_language();
        }

        // ตรวจสอบว่ามีข้อความสำหรับภาษานี้หรือไม่
        if (isset($this->default_messages[$lang])) {
            return $this->default_messages[$lang];
        }

        // Fallback เป็นภาษาอังกฤษ
        return $this->default_messages['en'];
    }

    /**
     * ดึง heading สำหรับหน้า 404
     * 
     * @param string $lang รหัสภาษา (optional)
     * @return string Heading text
     */
    public function get_heading($lang = null) {
        $messages = $this->get_messages($lang);
        return $messages['heading'];
    }

    /**
     * ดึง message สำหรับหน้า 404
     * 
     * @param string $lang รหัสภาษา (optional)
     * @return string Message text
     */
    public function get_message($lang = null) {
        $messages = $this->get_messages($lang);
        return $messages['message'];
    }

    /**
     * ดึง button text สำหรับหน้า 404
     * 
     * @param string $lang รหัสภาษา (optional)
     * @return string Button text
     */
    public function get_button_text($lang = null) {
        $messages = $this->get_messages($lang);
        return $messages['button'];
    }

    /**
     * ดึง home URL ตามภาษาปัจจุบัน
     * 
     * @return string Home URL with language prefix if needed
     */
    public function get_home_url() {
        $lang = LanguageSwitcher::get_current_language();
        
        if ($lang === 'th') {
            return home_url('/');
        }
        
        return home_url('/' . $lang . '/');
    }

    /**
     * Render 404 content (สำหรับใช้ใน theme)
     * 
     * แสดง HTML สำหรับหน้า 404 ตามภาษาปัจจุบัน
     * Theme สามารถเรียกใช้ใน 404.php ได้
     * 
     * @return string HTML content
     */
    public function render_404_content() {
        $lang = LanguageSwitcher::get_current_language();
        $messages = $this->get_messages($lang);
        $home_url = $this->get_home_url();

        ob_start();
        ?>
        <div class="ght-404-content" style="text-align: center; padding: 60px 20px;">
            <h1 style="font-size: 72px; margin: 0; color: #ccc;">404</h1>
            <h2 style="margin: 20px 0;"><?php echo esc_html($messages['heading']); ?></h2>
            <p style="color: #666; max-width: 500px; margin: 20px auto;">
                <?php echo esc_html($messages['message']); ?>
            </p>
            <a href="<?php echo esc_url($home_url); ?>" 
               class="ght-404-button" 
               style="display: inline-block; margin-top: 20px; padding: 12px 24px; 
                      background: #0073aa; color: #fff; text-decoration: none; 
                      border-radius: 4px;">
                <?php echo esc_html($messages['button']); ?>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }
}

/**
 * Helper function สำหรับใช้ใน theme
 * 
 * ใช้ใน 404.php:
 * <?php echo ght_get_404_content(); ?>
 * 
 * @return string HTML content for 404 page
 */
function ght_get_404_content() {
    $handler = new NotFoundHandler();
    return $handler->render_404_content();
}

/**
 * Helper function ดึงข้อความ 404 ตามภาษา
 * 
 * @param string $lang รหัสภาษา (optional)
 * @return array ข้อความ 404
 */
function ght_get_404_messages($lang = null) {
    $handler = new NotFoundHandler();
    return $handler->get_messages($lang);
}
