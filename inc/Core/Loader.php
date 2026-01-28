<?php
/**
 * Loader Class
 * 
 * คลาสหลักสำหรับเริ่มต้นการทำงานของปลั๊กอิน
 * ทำหน้าที่โหลด dependencies ทั้งหมดและลงทะเบียน hooks
 * 
 * === Module Loading Order ===
 * 1. Routing: Router (rewrite rules) ต้องโหลดก่อน
 * 2. Frontend: LanguageSwitcher, ContentFilter
 * 3. Integrations: Term, Menu translations
 * 4. Admin: Dashboard, Settings, AJAX handlers
 * 
 * @package GovHybridTranslator
 * @since 1.0.0
 * @updated 1.5.0 - เพิ่ม Capabilities class
 * @updated 1.8.0 - เพิ่ม Routing\Router สำหรับ path-based URLs
 */
namespace GovHybridTranslator\Core;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

// นำเข้า classes ที่จำเป็น
use GovHybridTranslator\Modules\MetaBox;
use GovHybridTranslator\Routing\Router as UrlRouter;  // Path-based URL Router
use GovHybridTranslator\Frontend\LanguageSwitcher;
use GovHybridTranslator\PostType\Glossary;
use GovHybridTranslator\Integrations\Term;
use GovHybridTranslator\Integrations\Menu;
use GovHybridTranslator\Admin\Ajax;
use GovHybridTranslator\Modules\ContentReviewer;
use GovHybridTranslator\Core\Capabilities;
use GovHybridTranslator\Modules\ActivityLogger;
use GovHybridTranslator\Modules\FrontendEditor;


class Loader {

	/**
	 * เริ่มต้นการทำงานของปลั๊กอิน
	 * เรียกใช้เมื่อ WordPress โหลด plugins_loaded hook
	 */
	public function run() {
		$this->load_dependencies();
		$this->check_version();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}



	/**
	 * โหลด dependencies และ initialize classes ทั้งหมด
	 * 
	 * ลำดับการโหลด:
	 * 1. Routing (Router for URL rewrite rules)
	 * 2. Custom Post Types (Glossary)
	 * 3. Frontend (LanguageSwitcher)
	 * 4. Integrations (Term, Menu translations)
	 * 5. Admin modules (เฉพาะใน admin area)
	 */
	private function load_dependencies() {
		// === ส่วน Routing (โหลดก่อนเพื่อให้ rewrite rules ทำงาน) ===
		// Router สำหรับ path-based URLs เช่น /en/, /zh/
		// ต้องโหลดก่อน LanguageSwitcher เพื่อให้ query_var('ght_lang') พร้อมใช้
		$router = new UrlRouter();
		$router->register();
		
		// === ส่วน Custom Post Types ===
		// สร้าง Glossary CPT สำหรับเก็บคำศัพท์
		new Glossary();
		
		// === ส่วน Frontend ===
		// สร้างปุ่มสลับภาษา (Language Switcher)
		// ใช้ Router สำหรับ get_current_language() และ URL generation
		$switcher = new LanguageSwitcher();
		$switcher->register();

		// === Phase 2: SEO Manager ===
		// เพิ่ม hreflang tags สำหรับ SEO
		$seo_manager = new \GovHybridTranslator\Frontend\SEOManager();
		$seo_manager->register();

		// === Phase 7: Content Filter ===
		// Filter title/content/excerpt ตามภาษาที่เลือก
		$content_filter = new \GovHybridTranslator\Frontend\ContentFilter();
		$content_filter->register();

		// === 404 Handler ===
		// รองรับหน้า 404 หลายภาษา
		$not_found_handler = new \GovHybridTranslator\Frontend\NotFoundHandler();
		$not_found_handler->register();

		// โหลด Language Switcher Widget
		// Widget ลงทะเบียนตัวเองผ่าน widgets_init hook
		require_once GOV_HYBRID_TRANSLATOR_PATH . 'inc/Frontend/LanguageSwitcherWidget.php';

		// === ส่วน Integrations ===
		// จัดการแปลชื่อ Categories/Tags
		$termTranslator = new Term();
		$termTranslator->register();

		// จัดการแปลรายการเมนู
		$menuTranslator = new Menu();
		$menuTranslator->register();

		// === Design Tabs Integration ===
		// จัดการแปล Tab titles สำหรับ Design Tabs plugin
		$designTabsIntegration = new \GovHybridTranslator\Integrations\DesignTabsIntegration();
		$designTabsIntegration->register();

		// === Theme Integrations ===
		// Avada Theme - แปล Header ที่สร้างด้วย Fusion Builder
		$avadaIntegration = new \GovHybridTranslator\Integrations\Avada();
		$avadaIntegration->register();
		
		// === ส่วน Admin (โหลดเฉพาะใน Dashboard) ===
		if ( is_admin() ) {
			// Meta Box สำหรับจัดการแปลใน Post Editor
			new MetaBox();

			// === Translation Column ===
			// เพิ่มคอลัม Translation ใน Posts/Pages list แสดงธงชาติ
			$translation_column = new \GovHybridTranslator\Admin\TranslationColumn();
			$translation_column->register();
			
			// Dashboard สำหรับจัดการแปลทั้งหมด
			$dashboard = new \GovHybridTranslator\Modules\Dashboard();
			$dashboard->register();
			
			// Settings page สำหรับตั้งค่าปลั๊กอิน
			$settings = new \GovHybridTranslator\Modules\Settings();
			$settings->register();
			
			// AJAX handlers สำหรับ Glossary, Translation, Settings
			$ajax = new Ajax();
			$ajax->register();

			// Editor Enhancer เพิ่มความสามารถให้ Gutenberg/Classic Editor
			$editor_enhancer = new \GovHybridTranslator\Modules\EditorEnhancer();
			$editor_enhancer->register();

			// Assets (CSS/JS) สำหรับหน้า Admin
			$assets = new \GovHybridTranslator\Modules\Assets();
			$assets->register();

			// Content Reviewer สำหรับตรวจสอบเนื้อหาก่อนแปล
			// ใช้งานผ่าน AJAX ไม่ต้องเรียก register()
			new ContentReviewer();

			// === Phase 1: Translation Status ===
			// ตรวจจับสถานะการแปลอัตโนมัติ
			$translation_status = new \GovHybridTranslator\Core\TranslationStatus();
			$translation_status->register();

			// === Phase 3: Batch Translation ===
			// รองรับ Bulk Action แปลหลาย Posts พร้อมกัน
			$batch_translator = new \GovHybridTranslator\Modules\BatchTranslator();
			$batch_translator->register();

			// Translation Memory - เก็บประโยคที่แปลแล้วใช้ซ้ำ
			$translation_memory = new \GovHybridTranslator\Modules\TranslationMemory();
			$translation_memory->register();

			// === Phase 4: Performance ===
			// Translation Cache - cache translated content
			$translation_cache = new \GovHybridTranslator\Core\TranslationCache();
			$translation_cache->register();

			// === Phase 8: Auto-Translate on Publish ===
			// แปลอัตโนมัติเมื่อ Publish post/page ใหม่
			// รองรับ: เลือกภาษาเป้าหมาย, เลือก post types, first publish only
			$auto_translator = new \GovHybridTranslator\Modules\AutoTranslator();
			$auto_translator->register();

		// === Phase 3.1: Activity Logs ===
			$activity_logger = new ActivityLogger();
			$activity_logger->register();
		}
		
		// === Phase 4: Frontend Visual Editor ===
		// ปุ่มแก้ไขคำแปลบน Admin Bar (แสดงเฉพาะหน้า Frontend)
		$frontend_editor = new FrontendEditor();
		$frontend_editor->run();


		// === REST API (ใช้ได้ทั้ง Frontend และ Admin) ===
		$rest_api = new \GovHybridTranslator\API\RestAPI();
		$rest_api->register();
	}

	/**
	 * ตรวจสอบเวอร์ชั่นและอัพเดต Database ถ้าจำเป็น
	 */
	private function check_version() {
		if ( is_admin() && get_option( 'gov_hybrid_translator_db_version' ) !== GOV_HYBRID_TRANSLATOR_VERSION ) {
			ActivityLogger::install_table();
		}
	}

	/**
	 * ลงทะเบียน Admin hooks เพิ่มเติม

	 * (hooks ส่วนใหญ่จัดการใน classes แต่ละตัว)
	 */
	private function define_admin_hooks() {
		// Admin specific hooks can go here if not handled in classes
	}

	/**
	 * ลงทะเบียน Public hooks เพิ่มเติม
	 * (hooks ส่วนใหญ่จัดการใน classes แต่ละตัว)
	 */
	private function define_public_hooks() {
		// Public hooks
	}

	/**
	 * ทำงานเมื่อ Activate plugin
	 * 
	 * ขั้นตอน:
	 * 1. ลงทะเบียน Custom Capabilities ให้ Roles
	 * 2. ลงทะเบียน Glossary CPT
	 * 3. สร้าง Rewrite Rules สำหรับภาษาต่างๆ
	 * 4. ลงทะเบียน Language Switcher
	 * 5. Flush rewrite rules เพื่อให้ URL ใหม่ทำงานได้
	 * 
	 * @see Router::request_flush_rules() สำหรับ flush rules แบบ lazy
	 */
	public static function activate() {
		// === ลงทะเบียน Custom Capabilities ===
		// ให้ Administrator และ Editor มีสิทธิ์เข้าถึง Plugin ทันที
		Capabilities::register();
		
		// ลงทะเบียน Custom Post Type ทันที
		$glossary = new Glossary();
		$glossary->register();

		// สร้าง Table สำหรับ Activity Logs
		ActivityLogger::install_table();
		
		// === สร้าง Rewrite Rules สำหรับ path-based URLs ===

		// Router จะสร้าง rules สำหรับ /en/, /zh/, etc.
		$router = new UrlRouter();
		$router->add_query_vars([]);  // ลงทะเบียน query vars
		$router->add_rewrite_rules(); // สร้าง rewrite rules

		// ลงทะเบียน Language Switcher
		$switcher = new LanguageSwitcher();
		$switcher->register();
		
		// === Flush Rewrite Rules ===
		// ต้อง flush เพื่อให้ .htaccess อัพเดท
		// ใช้ flush_rewrite_rules() โดยตรงเพราะเป็น activation
		flush_rewrite_rules();
	}

	/**
	 * ทำงานเมื่อ Deactivate plugin
	 * ลบ Custom Capabilities ออกจากทุก Roles
	 */
	public static function deactivate() {
		Capabilities::unregister();
		flush_rewrite_rules();
	}
}
