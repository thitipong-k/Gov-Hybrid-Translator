<?php
/**
 * Language Switcher Frontend Class
 * 
 * แสดงปุ่มสลับภาษาบนหน้าเว็บ
 * รองรับ: Floating Button, Menu Integration, Shortcode
 * 
 * === การทำงานหลัก ===
 * 1. ตรวจจับภาษาปัจจุบันจาก URL path (/en/, /zh/)
 * 2. สร้างปุ่มสลับภาษาแบบต่างๆ
 * 3. Filter permalinks ให้มี language prefix
 * 
 * === URL Pattern ===
 * - Thai (default): example.com/about/
 * - English: example.com/en/about/
 * - Chinese: example.com/zh/about/
 * 
 * @package GovHybridTranslator
 * @since 1.0.0
 * @modified 1.3.0 - ใช้ centralized settings แทน get_option()
 * @modified 1.8.0 - ใช้ Path-based URLs (/en/) แทน Query Params (?lang=en)
 * @see Router.php สำหรับ rewrite rules
 * @see UrlGenerator.php สำหรับ URL generation
 */
namespace GovHybridTranslator\Frontend;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Modules\Settings;
use GovHybridTranslator\Routing\Router;
use GovHybridTranslator\Routing\UrlGenerator;

class LanguageSwitcher {

	/**
	 * @var Settings ออบเจ็กต์สำหรับดึงค่า settings
	 */
	private $settings;

	/**
	 * Constructor
	 * สร้าง Settings object สำหรับใช้ดึงค่าตั้งค่า
	 */
	public function __construct() {
		$this->settings = new Settings();
	}

	/**
	 * ลงทะเบียน hooks ทั้งหมด
	 * 
	 * Hooks ที่ลงทะเบียน:
	 * - wp_enqueue_scripts: โหลด CSS
	 * - wp_footer: แสดง floating button
	 * - wp_nav_menu_items: เพิ่มปุ่มในเมนู
	 * - shortcode: สร้าง shortcode [gov_translator_switcher]
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
		add_action( 'wp_footer', [ $this, 'render_floating_button' ] );
		add_shortcode( 'gov_translator_switcher', [ $this, 'render_shortcode' ] );
		add_filter( 'wp_nav_menu_items', [ $this, 'inject_menu_item' ], 10, 2 );
		
		// === Language Persistence Filters ===
		// เพิ่ม /en/ prefix ให้ลิงก์ทุกอันเมื่อไม่ใช่ภาษาไทย
		add_filter( 'the_permalink', [ $this, 'filter_permalink' ] );
		add_filter( 'post_link', [ $this, 'filter_permalink' ] );
		add_filter( 'page_link', [ $this, 'filter_permalink' ], 10, 2 );
		add_filter( 'nav_menu_link_attributes', [ $this, 'filter_menu_link' ], 10, 4 );
		add_filter( 'home_url', [ $this, 'filter_home_url' ], 10, 2 );
		
		// === Custom Logo Filter ===
		// filter get_custom_logo output เพื่อแก้ไขลิงก์โลโก้
		add_filter( 'get_custom_logo', [ $this, 'filter_custom_logo' ] );
	}
	
	/**
	 * Filter custom logo HTML เพื่อเพิ่ม language prefix
	 * 
	 * โลโก้ WordPress ใช้ home_url แต่บาง themes cache ไว้หรือใช้วิธีอื่น
	 * filter นี้แก้ไข HTML output โดยตรง
	 * 
	 * @param string $html Custom logo HTML
	 * @return string Modified HTML with language prefix
	 */
	public function filter_custom_logo( $html ) {
		$lang = self::get_current_language();
		
		if ( $lang === 'th' ) {
			return $html;
		}
		
		// ดึง home URL โดยไม่ผ่าน filter (ใช้ get_option)
		$home = trailingslashit( get_option('home') );
		$lang_home = $home . $lang . '/';
		
		// แทนที่ลิงก์ home ใน HTML ด้วย lang home
		// Pattern: href="http://localhost/wordpress/" → href="http://localhost/wordpress/en/"
		$html = str_replace( 'href="' . $home . '"', 'href="' . $lang_home . '"', $html );
		
		return $html;
	}

	/**
	 * Filter permalinks เพิ่ม language prefix
	 * 
	 * === การทำงาน ===
	 * - เมื่อ user อยู่หน้า /en/about/ แล้วคลิกลิงก์ไป /contact/
	 * - Filter นี้จะแปลงเป็น /en/contact/ อัตโนมัติ
	 * 
	 * @param string $url URL เดิม
	 * @return string URL พร้อม language prefix
	 * @see Router::add_language_prefix()
	 */
	public function filter_permalink( $url ) {
		// ข้าม admin
		if ( is_admin() ) {
			return $url;
		}
		
		$lang = self::get_current_language();
		if ( $lang !== 'th' ) {
			// ใช้ path-based: /en/about/ แทน ?lang=en
			$url = Router::add_language_prefix($url, $lang);
		}
		return $url;
	}

	/**
	 * Filter menu link attributes เพิ่ม language prefix
	 * 
	 * === การทำงาน ===
	 * - เมื่อ render menu items จะเพิ่ม /en/ prefix ใน href
	 * 
	 * @param array $atts Menu link attributes
	 * @param object $item Menu item object
	 * @param object $args Menu args
	 * @param int $depth Menu depth
	 * @return array Modified attributes
	 */
	public function filter_menu_link( $atts, $item, $args, $depth ) {
		if ( is_admin() ) {
			return $atts;
		}
		
		$lang = self::get_current_language();
		if ( $lang !== 'th' && isset( $atts['href'] ) ) {
			// ใช้ path-based: /en/menu-item/ แทน ?lang=en
			$atts['href'] = Router::add_language_prefix($atts['href'], $lang);
		}
		return $atts;
	}

	/**
	 * Filter home_url เพิ่ม language prefix
	 * 
	 * === การทำงาน ===
	 * - home_url() จะคืนค่า /en/ สำหรับภาษาอังกฤษ
	 * - ใช้สำหรับ logo links, breadcrumbs, etc.
	 * 
	 * @param string $url Home URL
	 * @param string $path Path ที่ต่อท้าย
	 * @return string URL พร้อม language prefix
	 */
	public function filter_home_url( $url, $path ) {
		// ข้ามถ้าเป็น admin หรือ AJAX
		if ( is_admin() || wp_doing_ajax() ) {
			return $url;
		}
		
		// ข้ามถ้ามี path พิเศษ (api, wp-admin, etc.)
		if ( strpos( $path, 'wp-' ) === 0 || strpos( $path, 'admin' ) !== false ) {
			return $url;
		}
		
		$lang = self::get_current_language();
		if ( $lang !== 'th' ) {
			// ใช้ path-based: /en/ แทน ?lang=en
			$url = Router::add_language_prefix($url, $lang);
		}
		return $url;
	}

	/**
	 * ตรวจจับภาษาปัจจุบัน
	 * 
	 * === ลำดับการตรวจสอบ (Path-based) ===
	 * 1. WordPress Query Var: get_query_var('ght_lang') (จาก rewrite rules)
	 * 2. URL path: /en/, /zh/, /ja/ (fallback)
	 * 3. Default: 'th' (ภาษาไทยไม่มี prefix)
	 * 
	 * === การทำงาน ===
	 * - Router.php เพิ่ม rewrite rules ที่แปลง /en/about/ → ?ght_lang=en&pagename=about
	 * - WordPress set query_var('ght_lang') = 'en'
	 * - Function นี้อ่านค่าจาก query var
	 * 
	 * @return string รหัสภาษา ('th', 'en', 'zh', etc.)
	 * @see Router::add_rewrite_rules()
	 */
	public static function get_current_language() {
		// === 1. ตรวจสอบ WordPress Query Var (จาก Router rewrite rules) ===
		// ค่านี้ถูก set โดย rewrite rules ใน Router.php
		$lang = Router::get_language_from_query();
		if ($lang) {
			return $lang;
		}
		
		// === 2. ตรวจสอบ URL path (Fallback) ===
		// ใช้เมื่อ query var ยังไม่ถูก set (เช่น ก่อน parse_request)
		// รองรับทั้ง root (/en/) และ subdirectory (/wordpress/en/)
		$request_uri = $_SERVER['REQUEST_URI'] ?? '';
		$languages_pattern = implode('|', Router::SUPPORTED_LANGUAGES);
		
		// Pattern: /en/ หรือ /wordpress/en/ หรือ /en (ท้าย URL)
		if (preg_match('#/(' . $languages_pattern . ')(/|$|\?)#', $request_uri, $matches)) {
			return $matches[1];
		}
		
		// === 3. Default: ภาษาไทย ===
		return 'th';
	}

	/**
	 * โหลด CSS สำหรับ Language Switcher
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			'gov-hybrid-translator-style',
			GOV_HYBRID_TRANSLATOR_URL . 'assets/css/style.css',
			[],
			GOV_HYBRID_TRANSLATOR_VERSION
		);

		// === Language Detection & Auto-redirect ===
		// ตรวจสอบว่าเปิดใช้ auto_redirect หรือไม่
		$auto_redirect = $this->settings->get_setting('auto_redirect', false);
		if ($auto_redirect && !is_admin()) {
			$this->handle_auto_redirect();
		}
	}

	/**
	 * ตรวจจับภาษา Browser จาก Accept-Language header
	 * 
	 * ลำดับการตรวจสอบ:
	 * 1. Cookie (ถ้ามี) → ใช้ค่าที่ผู้ใช้เลือกไว้
	 * 2. Accept-Language header → ตรวจจับจาก browser
	 * 3. Default → ภาษาต้นฉบับจาก settings
	 * 
	 * @return string รหัสภาษาที่แนะนำ
	 */
	public function detect_browser_language() {
		// === ตรวจสอบ Cookie ก่อน ===
		if (isset($_COOKIE['ght_lang_preference'])) {
			$pref = sanitize_text_field($_COOKIE['ght_lang_preference']);
			if (in_array($pref, ['th', 'en'])) {
				return $pref;
			}
		}

		// === ตรวจสอบ Accept-Language header ===
		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			$browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
			
			// Map รหัสภาษา browser เป็นรหัสที่เราใช้
			$lang_map = [
				'th' => 'th',
				'en' => 'en',
				'zh' => 'zh',
				'ja' => 'ja',
				'ko' => 'ko',
			];

			if (isset($lang_map[$browser_lang])) {
				return $lang_map[$browser_lang];
			}
		}

		// === Default: ภาษาต้นฉบับ ===
		return $this->settings->get_setting('source_language', 'th');
	}

	/**
	 * จัดการ Auto-redirect ตามภาษา browser
	 * 
	 * ขั้นตอน:
	 * 1. ตรวจสอบว่าอยู่หน้าแรกหรือไม่
	 * 2. ตรวจสอบว่ามี Cookie หรือยัง
	 * 3. ถ้ายังไม่มี → redirect ไปภาษาที่ detect ได้
	 */
	private function handle_auto_redirect() {
		// ข้ามถ้าเป็น admin หรือ AJAX
		if (is_admin() || wp_doing_ajax()) {
			return;
		}

		// ข้ามถ้ามี Cookie แล้ว (ผู้ใช้เคยเลือกไว้)
		if (isset($_COOKIE['ght_lang_preference'])) {
			return;
		}

		// ข้ามถ้าไม่ใช่หน้าแรก
		if (!is_front_page() && !is_home()) {
			return;
		}

		// ตรวจจับภาษาจาก browser
		$detected_lang = $this->detect_browser_language();
		$current_lang = self::get_current_language();
		$source_lang = $this->settings->get_setting('source_language', 'th');

		// ถ้าภาษาที่ detect ≠ ภาษาปัจจุบัน และไม่ใช่ภาษาต้นฉบับ → redirect
		if ($detected_lang !== $current_lang && $detected_lang !== $source_lang) {
			// สร้าง URL ใหม่
			$redirect_url = home_url('/' . $detected_lang . '/');
			
			// Redirect (302 = temporary)
			wp_redirect($redirect_url, 302);
			exit;
		}
	}

	/**
	 * บันทึก Language Preference ลง Cookie
	 * เรียกใช้เมื่อผู้ใช้คลิกเลือกภาษา
	 * 
	 * @param string $lang รหัสภาษา
	 */
	public static function save_language_preference($lang) {
		$lang = sanitize_text_field($lang);
		
		// Cookie หมดอายุใน 30 วัน
		$expire = time() + (30 * DAY_IN_SECONDS);
		
		setcookie('ght_lang_preference', $lang, $expire, COOKIEPATH, COOKIE_DOMAIN);
	}

	/**
	 * แสดง Floating Button (ปุ่มลอย)
	 * แสดงเมื่อ 'floating' อยู่ใน placement array
	 */
	public function render_floating_button() {
		// ตรวจสอบว่า placement มี 'floating' หรือไม่
		$placement = $this->settings->get_setting( 'placement', ['floating', 'menu'] );
		if ( ! is_array( $placement ) || ! in_array( 'floating', $placement ) ) {
			return;
		}

		// ดึงตำแหน่งและระยะห่างจาก settings
		$position = $this->settings->get_setting( 'floating_position', 'bottom-right' );
		$margin_x = intval( $this->settings->get_setting( 'floating_margin_x', 20 ) );
		$margin_y = intval( $this->settings->get_setting( 'floating_margin_y', 20 ) );
		$switcher_type = $this->settings->get_setting( 'switcher_type', 'flags' );
		
		$this->render_button( 'floating', $position, $margin_x, $margin_y, $switcher_type );
	}

	/**
	 * Render Shortcode [gov_translator_switcher]
	 * 
	 * @param array $atts Shortcode attributes
	 * @return string HTML output
	 */
	public function render_shortcode( $atts ) {
		ob_start();
		$switcher_type = $this->settings->get_setting( 'switcher_type', 'flags' );
		$this->render_button( 'shortcode', '', 0, 0, $switcher_type );
		return ob_get_clean();
	}

	/**
	 * เพิ่มปุ่มสลับภาษาเข้าไปในเมนู
	 * แสดงเมื่อ 'menu' อยู่ใน placement array
	 *
	 * @param string $items Menu items HTML
	 * @param object $args Menu arguments
	 * @return string Updated menu items
	 */
	public function inject_menu_item( $items, $args ) {
		// ตรวจสอบว่า placement มี 'menu' หรือไม่
		$placement = $this->settings->get_setting( 'placement', ['floating', 'menu'] );
		if ( ! is_array( $placement ) || ! in_array( 'menu', $placement ) ) {
			return $items;
		}

		// เพิ่มเฉพาะใน primary menu
		if ( 'primary' === $args->theme_location || empty( $args->theme_location ) ) {
			ob_start();
			$switcher_type = $this->settings->get_setting( 'switcher_type', 'flags' );
			$this->render_button( 'menu', '', 0, 0, $switcher_type );
			$button_html = ob_get_clean();
			$items .= '<li class="menu-item gov-translator-menu-item">' . $button_html . '</li>';
		}

		return $items;
	}

	/**
	 * Render ปุ่มสลับภาษา (หลัก)
	 * 
	 * ขั้นตอน:
	 * 1. ตรวจจับภาษาปัจจุบัน
	 * 2. กำหนดภาษาเป้าหมาย
	 * 3. สร้าง URL ใหม่
	 * 4. แสดงปุ่มตาม switcher_type
	 *
	 * @param string $mode โหมดการแสดง: floating, menu, shortcode
	 * @param string $position ตำแหน่ง (สำหรับ floating): top-left, top-right, bottom-left, bottom-right
	 * @param int $margin_x ระยะห่างแนวนอน (pixels)
	 * @param int $margin_y ระยะห่างแนวตั้ง (pixels)
	 * @param string $switcher_type ประเภท: dropdown, flags, flag_pair, text
	 */
	private function render_button( $mode = 'floating', $position = '', $margin_x = 20, $margin_y = 20, $switcher_type = 'flags' ) {
		// === ขั้นตอน 1: ตรวจจับภาษาปัจจุบัน ===
		$current_lang = self::get_current_language();
		$is_en = ($current_lang !== 'th');

		// === ขั้นตอน 2: กำหนดภาษาเป้าหมาย ===
		$target_lang = $is_en ? 'th' : 'en';
		$label       = $is_en ? 'TH' : 'EN';
		
		// ดึง URL ของธงชาติ
		$flag_th_url = GOV_HYBRID_TRANSLATOR_URL . 'assets/images/flag-th.svg';
		$flag_en_url = GOV_HYBRID_TRANSLATOR_URL . 'assets/images/flag-en.svg';
		$icon_url = $is_en ? $flag_th_url : $flag_en_url;

		// === ขั้นตอน 3: สร้าง URL ใหม่ (Path-based) ===
		// ใช้ UrlGenerator สำหรับสร้าง URL ที่ถูกต้อง
		$current_url = UrlGenerator::get_current_url();
		$target_url = UrlGenerator::get_translated_url($target_lang);

		// === ขั้นตอน 4: กำหนด CSS classes และ inline style ===
		$classes = 'gov-hybrid-translator-switcher';
		$inline_style = '';
		
		if ( 'floating' === $mode ) {
			$classes .= ' gov-switcher-floating';
			
			// สร้าง inline style จาก position และ margin
			$style_parts = [];
			if ( strpos( $position, 'top' ) !== false ) {
				$style_parts[] = 'top: ' . $margin_y . 'px';
			} else {
				$style_parts[] = 'bottom: ' . $margin_y . 'px';
			}
			if ( strpos( $position, 'left' ) !== false ) {
				$style_parts[] = 'left: ' . $margin_x . 'px';
			} else {
				$style_parts[] = 'right: ' . $margin_x . 'px';
			}
			$inline_style = implode( '; ', $style_parts ) . ';';
			
		} elseif ( 'menu' === $mode ) {
			$classes .= ' gov-switcher-menu';
		} else {
			$classes .= ' gov-switcher-inline';
		}

		// === ขั้นตอน 5: แสดงปุ่มตาม switcher_type ===
		$content_mode = $this->settings->get_setting( 'button_content', 'both' );
		$show_flags = $this->settings->get_setting( 'show_flags', true );
		$show_names = $this->settings->get_setting( 'show_names', true );

		// flag_pair ใช้ dual buttons, อื่นๆ ใช้ single
		if ( 'flag_pair' === $switcher_type ) {
			// แสดงแบบสองปุ่ม: 🇹🇭 ⟷ 🇬🇧
			$this->render_dual_buttons( $is_en, $current_url, $classes, $content_mode, $flag_th_url, $flag_en_url, $inline_style );
		} else {
			// แสดงแบบปุ่มเดียว สลับไปมา
			$this->render_single_button( $target_url, $classes, $content_mode, $icon_url, $label, $inline_style );
		}
	}

	/**
	 * Render ปุ่มเดี่ยว (Single Button)
	 * แสดงปุ่มที่คลิกแล้วสลับไปอีกภาษา
	 *
	 * @param string $target_url URL ปลายทาง
	 * @param string $classes CSS classes
	 * @param string $content_mode เนื้อหา: both, flag_only, text_only
	 * @param string $icon_url URL ของไอคอนธง
	 * @param string $label ชื่อภาษา (TH/EN)
	 * @param string $inline_style CSS inline style
	 */
	private function render_single_button( $target_url, $classes, $content_mode, $icon_url, $label, $inline_style = '' ) {
		$style_attr = $inline_style ? ' style="' . esc_attr( $inline_style ) . '"' : '';
		?>
		<a href="<?php echo esc_url( $target_url ); ?>" class="<?php echo esc_attr( $classes ); ?>"<?php echo $style_attr; ?>>
			<?php if ( 'both' === $content_mode || 'flag_only' === $content_mode ) : ?>
				<img src="<?php echo esc_url( $icon_url ); ?>" alt="<?php echo esc_attr( $label ); ?>">
			<?php endif; ?>
			
			<?php if ( 'both' === $content_mode || 'text_only' === $content_mode ) : ?>
				<span><?php echo esc_html( $label ); ?></span>
			<?php endif; ?>
		</a>
		<?php
	}

	/**
	 * Render ปุ่มคู่ (Dual Buttons)
	 * แสดง TH | EN เคียงกัน โดยปุ่มที่ active จะ highlight
	 * 
	 * === การสร้าง URLs (Path-based) ===
	 * - URL ไทย: example.com/about/ (ไม่มี prefix)
	 * - URL อังกฤษ: example.com/en/about/
	 *
	 * @param bool $is_en อยู่ในโหมดภาษาอังกฤษหรือไม่
	 * @param string $current_url URL ปัจจุบัน (ไม่ใช้แล้ว - ใช้ UrlGenerator แทน)
	 * @param string $classes CSS classes
	 * @param string $content_mode เนื้อหา: both, flag_only, text_only
	 * @param string $flag_th_url URL ธงไทย
	 * @param string $flag_en_url URL ธงอังกฤษ
	 * @param string $inline_style CSS inline style
	 * @see UrlGenerator::get_translated_url()
	 */
	private function render_dual_buttons( $is_en, $current_url, $classes, $content_mode, $flag_th_url, $flag_en_url, $inline_style = '' ) {
		// === สร้าง URLs ด้วย UrlGenerator (Path-based) ===
		$url_th = UrlGenerator::get_translated_url('th');
		$url_en = UrlGenerator::get_translated_url('en');
		
		// เพิ่ม class dual
		$classes .= ' gov-switcher-dual';
		$style_attr = $inline_style ? ' style="' . esc_attr( $inline_style ) . '"' : '';
		?>
		<div class="<?php echo esc_attr( $classes ); ?>"<?php echo $style_attr; ?>>
			<!-- ปุ่มภาษาไทย -->
			<a href="<?php echo esc_url( $url_th ); ?>" class="gov-lang-btn <?php echo !$is_en ? 'active' : ''; ?>">
				<?php if ( 'both' === $content_mode || 'flag_only' === $content_mode ) : ?>
					<img src="<?php echo esc_url( $flag_th_url ); ?>" alt="TH">
				<?php endif; ?>
				<?php if ( 'both' === $content_mode || 'text_only' === $content_mode ) : ?>
					<span>TH</span>
				<?php endif; ?>
			</a>
			<span class="gov-separator">|</span>
			<!-- ปุ่มภาษาอังกฤษ -->
			<a href="<?php echo esc_url( $url_en ); ?>" class="gov-lang-btn <?php echo $is_en ? 'active' : ''; ?>">
				<?php if ( 'both' === $content_mode || 'flag_only' === $content_mode ) : ?>
					<img src="<?php echo esc_url( $flag_en_url ); ?>" alt="EN">
				<?php endif; ?>
				<?php if ( 'both' === $content_mode || 'text_only' === $content_mode ) : ?>
					<span>EN</span>
				<?php endif; ?>
			</a>
		</div>
		<?php
	}
}
