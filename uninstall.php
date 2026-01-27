<?php
/**
 * ไฟล์ Uninstall สำหรับ Gov Hybrid Translator
 * 
 * ทำงานเมื่อผู้ใช้ลบ plugin ออกจาก WordPress
 * ลบข้อมูลทั้งหมดที่ plugin สร้างไว้เพื่อไม่ให้เหลือ "ขยะ" ใน database
 *
 * @package GovHybridTranslator
 * @since 1.0.0
 * @updated 2.3.0 - เพิ่มการลบ _ght_ meta keys
 */

// ป้องกันการเรียกไฟล์โดยตรง - ต้องเรียกจาก WordPress เท่านั้น
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// ลบ plugin options ทั้งหมด
delete_option( 'gov_hybrid_translator_settings' );
delete_option( 'ght_blogname_en' );
delete_option( 'ght_blogdescription_en' );
delete_option( 'ght_menu_translations_en' );

global $wpdb;

// === ลบ Post Meta ===
// ลบ metadata การแปล (title, content, excerpt, timestamp)
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_ght_%'" );
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_gov_translator_%'" );
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_gov_glossary_%'" );

// === ลบ Term Meta ===
// ลบ metadata การแปล categories/tags
$wpdb->query( "DELETE FROM {$wpdb->termmeta} WHERE meta_key LIKE '_ght_%'" );
$wpdb->query( "DELETE FROM {$wpdb->termmeta} WHERE meta_key LIKE '_gov_translator_%'" );

// === ลบ Transients ===
// ลบ cached data ที่ plugin สร้างไว้
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ght_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_ght_%'" );

// ล้าง cache ทั้งหมด
wp_cache_flush();

