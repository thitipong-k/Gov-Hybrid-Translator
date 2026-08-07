<?php
/**
 * Plugin Name: Gov Hybrid Translator
 * Plugin URI:  https://example.go.th
 * Description: ระบบแปลภาษาแบบ Hybrid (Manual + AI) พร้อม Glossary, รองรับ Gutenberg, Elementor และ Avada Theme
 * Version:     2.5.4
 * Author:      Gov Tech Team
 * Text Domain: gov-hybrid-translator
 * Domain Path: /languages
 * 
 * Changelog:
 * 2.5.4 - ปรับปรุงความปลอดภัยแยกส่วนการตรวจสอบ Nonce (CSRF) และสิทธิ์การใช้งาน (Privilege Escalation) ในการตรวจสอบ AJAX
 * 2.5.3 - แก้ไขปุ่มซ้อนทับกันใน Modal ท้ายหน้าจอ และแก้ไข Internal Server Error (500) จากฟังก์ชันล้างแคช wp_cache_delete_group
 * 2.5.2 - Pre-Translation Glossary Protection & Restoration (Fix Glossary replacement issue), Caching & Auto-Invalidation, REST API Glossary Integration
 * 2.5.1 - แก้ไขบั๊กการบันทึกค่า Settings (API Key, สิทธิ์ผู้ใช้, Content & SEO, UI ปุ่มสลับภาษา) และปรับโครงสร้าง JS
 * 2.5.0 - ระบบแก้ไขหน้าบ้าน (Frontend Editor), Workflow การอนุมัติขั้นสูง, แดชบอร์ดตรวจสอบ, แจ้งเตือนทางอีเมล
 * 2.4.0 - Workflow การแปลขั้นสูง, ระบบคลังคำศัพท์อัจฉริยะ (Smart Glossary), บันทึกกิจกรรม (Activity Logs), แก้ไขข้อมูล Dashboard, เชื่อมต่อ GitHub Auto-Update
 * 2.3.0 - แก้ไขการแปล HTML ซับซ้อน, ปุ่มลบคำแปล, รองรับ Custom HTML Block
 * 2.2.0 - แท็บดูต้นฉบับ/คำแปล, รองรับ Avada Theme Builder
 * 2.1.1 - คิวแปลแยกตามหมวดหมู่, แก้ไขข้อผิดพลาด 404 เมื่อสลับภาษา
 * 2.1.0 - รองรับหลายภาษา, URL แบบ path-based
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// กำหนดค่าคงที่ของ Plugin
define( 'GOV_HYBRID_TRANSLATOR_VERSION', '2.5.4' );
define( 'GOV_HYBRID_TRANSLATOR_FILE', __FILE__ );
define( 'GOV_HYBRID_TRANSLATOR_PATH', plugin_dir_path( __FILE__ ) );
define( 'GOV_HYBRID_TRANSLATOR_URL', plugin_dir_url( __FILE__ ) );

// Autoloader (Simple implementation if Composer is not run)
spl_autoload_register( function ( $class ) {
	$prefix = 'GovHybridTranslator\\';
	$base_dir = GOV_HYBRID_TRANSLATOR_PATH . 'inc/';

	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, $len );
	$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

	if ( file_exists( $file ) ) {
		require $file;
	}
} );

// Initialize the Plugin
function gov_hybrid_translator_init() {
	$plugin = new \GovHybridTranslator\Core\Loader();
	$plugin->run();
}
add_action( 'plugins_loaded', 'gov_hybrid_translator_init' );

// Activation Hook
register_activation_hook( __FILE__, function() {
	// Flush rewrite rules on activation
	\GovHybridTranslator\Core\Loader::activate();
} );

// Deactivation Hook
register_deactivation_hook( __FILE__, function() {
	// ลบ Custom Capabilities และล้าง Rewrite Rules
	\GovHybridTranslator\Core\Loader::deactivate();
} );

/**
 * Auto Update Check (GitHub)
 * Requires 'plugin-update-checker' library in inc/Libraries/
 */
$puc_path = plugin_dir_path( __FILE__ ) . 'inc/Libraries/plugin-update-checker/plugin-update-checker.php';


if ( file_exists( $puc_path ) ) {
	require $puc_path;
	$myUpdateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/thitipong-k/Gov-Hybrid-Translator', // GitHub Repo URL
		__FILE__,
		'gov-hybrid-translator'
	);
	$myUpdateChecker->setBranch('main');

	// เพิ่มรูปภาพไอคอนของปลั๊กอินในการแสดงผลหน้าอัปเดต
	$myUpdateChecker->addResultFilter(function ($info) {
		if ($info) {
			$plugin_url = plugin_dir_url(__FILE__);
			$info->icons = [
				'1x'      => $plugin_url . 'assets/images/icon-128x128.png',
				'2x'      => $plugin_url . 'assets/images/icon-256x256.png',
				'default' => $plugin_url . 'assets/images/icon-256x256.png'
			];
		}
		return $info;
	});
}
