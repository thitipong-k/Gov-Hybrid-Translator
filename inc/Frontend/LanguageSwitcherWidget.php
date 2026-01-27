<?php
/**
 * Language Switcher Widget
 * 
 * Widget สำหรับแสดง Language Switcher ใน Sidebar
 * รองรับการตั้งค่า Title และ Display style
 * 
 * @package GovHybridTranslator
 * @since 1.5.0
 */
namespace GovHybridTranslator\Frontend;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Modules\Settings;

class LanguageSwitcherWidget extends \WP_Widget {

    /**
     * @var Settings ออบเจ็กต์สำหรับดึงค่า settings
     */
    private $settings;

    /**
     * Constructor
     * 
     * กำหนด ID, ชื่อ และ description ของ Widget
     */
    public function __construct() {
        $widget_ops = [
            'classname' => 'gov_translator_widget',
            'description' => 'แสดงปุ่มสลับภาษาสำหรับ Gov Hybrid Translator',
        ];
        parent::__construct(
            'gov_translator_switcher',      // Base ID
            'Language Switcher',            // Name
            $widget_ops
        );

        $this->settings = new Settings();
    }

    /**
     * แสดง Widget บน Frontend
     * 
     * @param array $args Widget arguments จาก theme
     * @param array $instance Widget instance settings
     */
    public function widget($args, $instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        $display_style = !empty($instance['display_style']) ? $instance['display_style'] : 'flags';

        // แสดง widget wrapper (escaped for safety)
        echo wp_kses_post($args['before_widget']);

        // แสดง title (ถ้ามี) - escaped for safety
        if (!empty($title)) {
            echo wp_kses_post($args['before_title']) . esc_html(apply_filters('widget_title', $title)) . wp_kses_post($args['after_title']);
        }

        // แสดง Language Switcher
        $this->render_switcher($display_style);

        echo wp_kses_post($args['after_widget']);
    }

    /**
     * Render Language Switcher
     * 
     * @param string $display_style รูปแบบการแสดง: flags, text, dropdown
     */
    private function render_switcher($display_style) {
        // ตรวจจับภาษาปัจจุบัน
        $current_lang = LanguageSwitcher::get_current_language();
        $is_en = ($current_lang === 'en');

        // เตรียม URLs
        $current_url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $home_url = home_url();

        if ($is_en) {
            $url_th = str_replace('/en/', '/', $current_url);
            $url_en = $current_url;
        } else {
            $url_th = $current_url;
            $url_en = str_replace($home_url, $home_url . '/en', $current_url);
        }

        // URLs ของธง
        $flag_th = GOV_HYBRID_TRANSLATOR_URL . 'assets/images/flag-th.svg';
        $flag_en = GOV_HYBRID_TRANSLATOR_URL . 'assets/images/flag-en.svg';

        echo '<div class="gov-translator-widget-content">';

        switch ($display_style) {
            case 'dropdown':
                $this->render_dropdown($current_lang, $url_th, $url_en);
                break;

            case 'text':
                $this->render_text_buttons($is_en, $url_th, $url_en);
                break;

            case 'flags':
            default:
                $this->render_flag_buttons($is_en, $url_th, $url_en, $flag_th, $flag_en);
                break;
        }

        echo '</div>';
    }

    /**
     * Render แบบ Dropdown
     */
    private function render_dropdown($current_lang, $url_th, $url_en) {
        ?>
        <select onchange="window.location.href=this.value" class="gov-widget-select">
            <option value="<?php echo esc_url($url_th); ?>" <?php selected($current_lang, 'th'); ?>>
                🇹🇭 ไทย
            </option>
            <option value="<?php echo esc_url($url_en); ?>" <?php selected($current_lang, 'en'); ?>>
                🇬🇧 English
            </option>
        </select>
        <?php
    }

    /**
     * Render แบบ Text Buttons
     */
    private function render_text_buttons($is_en, $url_th, $url_en) {
        ?>
        <div class="gov-widget-buttons">
            <a href="<?php echo esc_url($url_th); ?>" 
               class="gov-widget-btn <?php echo !$is_en ? 'active' : ''; ?>">
                ไทย
            </a>
            <span class="gov-widget-separator">|</span>
            <a href="<?php echo esc_url($url_en); ?>" 
               class="gov-widget-btn <?php echo $is_en ? 'active' : ''; ?>">
                EN
            </a>
        </div>
        <?php
    }

    /**
     * Render แบบ Flag Buttons
     */
    private function render_flag_buttons($is_en, $url_th, $url_en, $flag_th, $flag_en) {
        ?>
        <div class="gov-widget-flags">
            <a href="<?php echo esc_url($url_th); ?>" 
               class="gov-widget-flag <?php echo !$is_en ? 'active' : ''; ?>"
               title="ภาษาไทย">
                <img src="<?php echo esc_url($flag_th); ?>" alt="TH" width="24" height="16">
            </a>
            <a href="<?php echo esc_url($url_en); ?>" 
               class="gov-widget-flag <?php echo $is_en ? 'active' : ''; ?>"
               title="English">
                <img src="<?php echo esc_url($flag_en); ?>" alt="EN" width="24" height="16">
            </a>
        </div>
        <?php
    }

    /**
     * แสดง Form ใน Admin สำหรับตั้งค่า Widget
     * 
     * @param array $instance Widget settings
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        $display_style = !empty($instance['display_style']) ? $instance['display_style'] : 'flags';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">Title:</label>
            <input class="widefat" 
                   id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" 
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('display_style')); ?>">Display Style:</label>
            <select class="widefat" 
                    id="<?php echo esc_attr($this->get_field_id('display_style')); ?>" 
                    name="<?php echo esc_attr($this->get_field_name('display_style')); ?>">
                <option value="flags" <?php selected($display_style, 'flags'); ?>>🚩 Flags</option>
                <option value="text" <?php selected($display_style, 'text'); ?>>Aa Text</option>
                <option value="dropdown" <?php selected($display_style, 'dropdown'); ?>>🌐 Dropdown</option>
            </select>
        </p>
        <?php
    }

    /**
     * อัพเดท settings เมื่อกด Save
     * 
     * @param array $new_instance New settings
     * @param array $old_instance Old settings
     * @return array Settings ที่ sanitize แล้ว
     */
    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = sanitize_text_field($new_instance['title']);
        $instance['display_style'] = sanitize_text_field($new_instance['display_style']);
        return $instance;
    }
}

/**
 * ลงทะเบียน Widget
 * เรียกใช้ใน widgets_init hook
 */
function gov_translator_register_widget() {
    register_widget('GovHybridTranslator\Frontend\LanguageSwitcherWidget');
}
add_action('widgets_init', 'GovHybridTranslator\Frontend\gov_translator_register_widget');
