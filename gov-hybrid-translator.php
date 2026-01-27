<?php
/**
 * Plugin Name: Gov Hybrid Translator
 * Plugin URI:  https://example.go.th
 * Description: ระบบแปลภาษาแบบ Hybrid (Manual + AI) พร้อม Glossary, รองรับ Gutenberg, Elementor และ Avada Theme
 * Version:     2.4.0
 * Author:      Gov Tech Team
 * Text Domain: gov-hybrid-translator
 * Domain Path: /languages
 * 
 * Changelog:
 * 2.4.0 - Advanced Translation Workflow (Draft/Publish), Smart Glossary (Regex), Status Management
 * 2.3.0 - Complex HTML translation fix, Delete Translation button, Custom HTML block support
 * 2.2.0 - View Original/Translated tabs, Avada Theme Builder support
 * 2.1.1 - Category-Based Translation Queue, Fixed 404 error on language switch
 * 2.1.0 - Multi-language support, path-based URLs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// กำหนดค่าคงที่ของ Plugin
define( 'GOV_HYBRID_TRANSLATOR_VERSION', '2.4.0' );
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
