<?php
/**
 * Router Class
 * 
 * จัดการ URL Rewriting สำหรับเนื้อหาหลายภาษา
 * รองรับการสร้าง URL เช่น /en/page-name/, /zh/page-name/ ฯลฯ
 * 
 * @package GovHybridTranslator
 * @since 1.0.0
 * @modified 1.3.0 - เพิ่มรองรับหลายภาษาแบบ dynamic
 */
namespace GovHybridTranslator\Frontend;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Core\Languages;
use GovHybridTranslator\Modules\Settings;

class Router {

	/**
	 * @var Settings ออบเจ็กต์สำหรับดึงค่า settings
	 */
	private $settings;

	/**
	 * Constructor
	 * 
	 * เริ่มต้น Router และลงทะเบียน hooks ที่จำเป็น:
	 * - init: สร้าง rewrite rules
	 * - query_vars: เพิ่ม query var 'lang'
	 * - post_link/page_link/post_type_link: แก้ไข permalinks
	 */
	public function __construct() {
		$this->settings = new Settings();
		
		// ลงทะเบียน hooks
		add_action( 'init', [ $this, 'add_rewrite_rules' ] );
		add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
		add_filter( 'post_link', [ $this, 'filter_permalink' ], 10, 2 );
		add_filter( 'page_link', [ $this, 'filter_permalink' ], 10, 2 );
		add_filter( 'post_type_link', [ $this, 'filter_permalink' ], 10, 2 );
	}

	/**
	 * ดึงรายการภาษาเป้าหมายจาก Settings
	 * 
	 * @return array รายการ language codes เช่น ['en', 'zh', 'ja']
	 */
	private function get_target_languages() {
		$target_langs = $this->settings->get_setting( 'target_languages', ['en'] );
		return is_array( $target_langs ) ? $target_langs : ['en'];
	}

	/**
	 * ดึงภาษาต้นฉบับจาก Settings
	 * 
	 * @return string language code เช่น 'th'
	 */
	private function get_source_language() {
		return $this->settings->get_setting( 'source_language', 'th' );
	}

	/**
	 * สร้าง Rewrite Rules สำหรับทุกภาษาที่ตั้งค่าไว้
	 * 
	 * ตัวอย่าง Rules ที่สร้าง:
	 * - ^en/(.+?)/?$ => index.php?name=$1&lang=en
	 * - ^zh/(.+?)/?$ => index.php?name=$1&lang=zh
	 * - ^ja/(.+?)/?$ => index.php?name=$1&lang=ja
	 * 
	 * หมายเหตุ: ต้อง Flush Rewrite Rules หลังเปลี่ยนแปลง target_languages
	 */
	public function add_rewrite_rules() {
		$target_langs = $this->get_target_languages();
		
		// วนลูปสร้าง rules สำหรับแต่ละภาษา
		foreach ( $target_langs as $lang_code ) {
			// ตรวจสอบว่าภาษา supported หรือไม่
			if ( Languages::is_supported( $lang_code ) ) {
				// Rule สำหรับ Posts: example.go.th/{lang}/some-slug
				add_rewrite_rule( 
					'^' . preg_quote( $lang_code, '/' ) . '/(.+?)/?$', 
					'index.php?name=$matches[1]&lang=' . $lang_code, 
					'top' 
				);
				
				// Rule สำหรับ Pages: example.go.th/{lang}/some-page
				add_rewrite_rule( 
					'^' . preg_quote( $lang_code, '/' ) . '/(.+?)/?$', 
					'index.php?pagename=$matches[1]&lang=' . $lang_code, 
					'top' 
				);
			}
		}
	}

	/**
	 * เพิ่ม 'lang' เข้าไปใน query vars ของ WordPress
	 * ทำให้สามารถใช้ get_query_var('lang') ได้
	 *
	 * @param array $vars Query vars ที่มีอยู่
	 * @return array Query vars ที่อัพเดทแล้ว
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'lang';
		return $vars;
	}

	/**
	 * แก้ไข Permalink ของ posts/pages ที่แปลแล้ว
	 * เพิ่ม language prefix เข้าไปใน URL
	 * 
	 * ตัวอย่าง:
	 * - Post ภาษาไทย: https://site.com/post-name/
	 * - Post ภาษาอังกฤษ: https://site.com/en/post-name/
	 * - Post ภาษาจีน: https://site.com/zh/post-name/
	 *
	 * @param string $permalink URL เดิม
	 * @param mixed $post Post object หรือ ID
	 * @return string URL ที่แก้ไขแล้ว
	 */
	public function filter_permalink( $permalink, $post ) {
		$post_id = is_object( $post ) ? $post->ID : $post;

		// ดึงภาษาของ post นี้
		$lang = get_post_meta( $post_id, '_gov_translator_lang', true );
		$source_lang = $this->get_source_language();

		// ถ้าเป็นภาษาอื่นที่ไม่ใช่ภาษาต้นฉบับ ให้เพิ่ม prefix
		if ( $lang && $lang !== $source_lang && Languages::is_supported( $lang ) ) {
			$home_url = home_url();
			// แทรก /{lang}/ หลัง domain
			$permalink = str_replace( $home_url, $home_url . '/' . $lang, $permalink );
		}

		return $permalink;
	}

	/**
	 * ตรวจจับภาษาปัจจุบันจาก URL
	 * 
	 * ลำดับการตรวจสอบ:
	 * 1. Query parameter: ?lang=en
	 * 2. URL path: /en/page-name/
	 * 3. Default: ภาษาต้นฉบับจาก settings
	 *
	 * @return string Language code (เช่น 'th', 'en', 'zh')
	 */
	public static function detect_current_language() {
		// ตรวจสอบ query parameter ก่อน
		if ( isset( $_GET['lang'] ) ) {
			$lang = sanitize_text_field( $_GET['lang'] );
			if ( Languages::is_supported( $lang ) ) {
				return $lang;
			}
		}

		// ตรวจสอบ URL path
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
		$supported_langs = array_keys( Languages::get_supported_languages() );
		
		foreach ( $supported_langs as $lang_code ) {
			// Pattern: /en/, /zh/, /ja/ ฯลฯ
			if ( preg_match( '#^/' . preg_quote( $lang_code, '#' ) . '/#', $request_uri ) ) {
				return $lang_code;
			}
		}

		// ใช้ภาษาต้นฉบับเป็น default
		$settings = new Settings();
		return $settings->get_setting( 'source_language', 'th' );
	}
}
