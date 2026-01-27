<?php
/**
 * Glossary Post Type
 * 
 * สร้าง Custom Post Type สำหรับเก็บคำศัพท์/อภิธานศัพท์
 * ใช้สำหรับแปลศัพท์เฉพาะทางของราชการ เช่น ชื่อตำแหน่ง, หน่วยงาน
 * 
 * คุณสมบัติ:
 * - รองรับเฉพาะ title (คำศัพท์ภาษาไทย)
 * - เก็บคำแปลใน meta field
 * - มี Taxonomy สำหรับจัดกลุ่มประเภทคำศัพท์
 * 
 * @package GovHybridTranslator
 * @since 1.0.0
 */
namespace GovHybridTranslator\PostType;

// ป้องกันการเข้าถึงไฟล์โดยตรง
if (!defined('ABSPATH')) exit;

/**
 * Class Glossary
 * จัดการ Custom Post Type สำหรับอภิธานศัพท์
 */
class Glossary {

	public function __construct() {
		add_action( 'init', [ $this, 'register' ] );
	}

	public function register() {
		$labels = [
			'name'               => 'Glossary Terms',
			'singular_name'      => 'Glossary Term',
			'menu_name'          => 'Gov Glossary',
			'add_new'            => 'Add New Term',
			'add_new_item'       => 'Add New Glossary Term',
			'edit_item'          => 'Edit Glossary Term',
			'new_item'           => 'New Glossary Term',
			'view_item'          => 'View Glossary Term',
			'search_items'       => 'Search Glossary',
			'not_found'          => 'No terms found',
			'not_found_in_trash' => 'No terms found in Trash',
		];

		$args = [
			'labels'              => $labels,
			'public'              => false, // Not public on frontend directly
			'show_ui'             => true,
			'show_in_menu'        => false, // Hide from WP admin menu (managed in plugin dashboard)
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'supports'            => [ 'title' ], // Title = Thai Word
			'menu_icon'           => 'dashicons-translation',
			'rewrite'             => false,
		];

		register_post_type( 'gov_glossary', $args );

		// Register Taxonomy for Type (Person, Position, Unit)
		register_taxonomy( 'gov_glossary_type', 'gov_glossary', [
			'labels' => [
				'name' => 'Term Types',
				'singular_name' => 'Term Type',
			],
			'hierarchical' => true,
			'show_ui' => true,
			'show_admin_column' => true,
		] );
	}
}
