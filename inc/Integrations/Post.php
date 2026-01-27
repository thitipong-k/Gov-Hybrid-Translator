<?php
/**
 * Post Integration Class
 * 
 * จัดการการแปล Posts และ Pages ไปยังภาษาเป้าหมาย
 * ใช้ Meta-based architecture - เก็บ title + content ใน post_meta
 * ไม่สร้าง Post ใหม่
 * 
 * รองรับ:
 * - Classic Editor content
 * - Gutenberg blocks
 * - Elementor page builder
 * - Fusion Builder (Avada Theme)
 * 
 * @package GovHybridTranslator
 * @since 1.0.0
 * @modified 1.8.0 - เปลี่ยนเป็น Meta-based only (ลบ Clone functions)
 * @modified 2.0.0 - เพิ่ม Gutenberg และ Elementor support
 */
namespace GovHybridTranslator\Integrations;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Service\AIService;
use GovHybridTranslator\Parsers\GutenbergParser;
use GovHybridTranslator\Parsers\ElementorParser;
use GovHybridTranslator\Parsers\FusionParser;

class Post {

	/**
	 * แปล Post/Page เก็บใน post_meta (ไม่สร้าง Post ใหม่)
	 * 
	 * ขั้นตอนการทำงาน:
	 * 1. ตรวจสอบประเภท content (Classic/Gutenberg/Elementor)
	 * 2. ดึงเนื้อหาต้นฉบับ
	 * 3. แปลด้วย AI Service (ผ่าน parser ที่เหมาะสม)
	 * 4. แทนที่คำด้วย Glossary
	 * 5. บันทึกลง post_meta ด้วย TranslationMeta
	 *
	 * @param int $post_id Post ID ต้นฉบับ
	 * @param string $target_lang รหัสภาษาเป้าหมาย (เช่น 'en', 'zh', 'ja')
	 * @param string|null $custom_title ชื่อแปลที่กำหนดเอง (ถ้ามี)
	 * @return bool|WP_Error true ถ้าสำเร็จ หรือ error
	 */
	public function translate_to_meta( $post_id, $target_lang = 'en', $custom_title = null ) {
		// ดึง Post ต้นฉบับ
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'invalid_post', 'ไม่พบโพสต์' );
		}

		// === ขั้นตอนที่ 1: เตรียมเนื้อหาและ AI Service ===
		$original_content = $post->post_content;
        $original_title = $post->post_title;
        $original_excerpt = $post->post_excerpt;
        
        // สร้าง AI Service
        $ai_service = new AIService();
        
        // === ตรวจสอบว่า AI Provider พร้อมใช้งาน ===
        // ถ้าไม่มี API Key จะ return error แทนที่จะ save Thai content
        if ( ! $ai_service->isReady() ) {
            $ai_error = $ai_service->getLastError();
            return new \WP_Error( 
                'ai_not_ready', 
                'ไม่สามารถแปลได้: ' . ($ai_error ?: 'กรุณาตั้งค่า API Key ใน Settings')
            );
        }

        // === ขั้นตอนที่ 2: ตรวจสอบประเภท content และแปล ===
        $translated_content = $this->translate_content_by_type(
            $post_id,
            $original_content,
            $target_lang,
            $ai_service
        );

        // แปล excerpt ด้วย AI โดยตรง (ไม่มี blocks)
        $translated_excerpt = !empty($original_excerpt) 
            ? $ai_service->translate_html( $original_excerpt, $target_lang )
            : '';
        
        // === ขั้นตอนที่ 3: แปล Title ===
        // ใช้ชื่อที่กำหนดเอง หรือแปลด้วย AI
        if ( ! empty( $custom_title ) ) {
            $translated_title = $custom_title;
        } else {
            $translated_title = $ai_service->translate_html( $original_title, $target_lang );
        }

        // === ขั้นตอนที่ 4: แทนที่คำด้วย Glossary ===
		$final_content = $this->replace_glossary_terms( $translated_content, $target_lang );
        $final_title = $this->replace_glossary_terms( $translated_title, $target_lang );
        $final_excerpt = $this->replace_glossary_terms( $translated_excerpt, $target_lang );

		// === ขั้นตอนที่ 5: บันทึกลง post_meta ===
		$result = \GovHybridTranslator\Core\TranslationMeta::save(
			$post_id,
			$target_lang,
			$final_title,
			$final_content,
			$final_excerpt
		);

		return $result ? true : new \WP_Error( 'save_failed', 'ไม่สามารถบันทึกคำแปลได้' );
	}

	/**
	 * แปล content ตามประเภท (Classic/Gutenberg/Elementor)
	 * 
	 * ตรวจสอบประเภท content แล้วเรียกใช้ parser ที่เหมาะสม
	 * 
	 * @param int $post_id Post ID
	 * @param string $content Original content
	 * @param string $target_lang Target language
	 * @param AIService $ai_service AI Service instance
	 * @return string Translated content
	 */
	private function translate_content_by_type( $post_id, $content, $target_lang, $ai_service ) {
		// === ตรวจสอบ Elementor ===
		if ( ElementorParser::is_elementor_post( $post_id ) ) {
			return $this->translate_elementor_content( $post_id, $content, $target_lang, $ai_service );
		}

		// === ตรวจสอบ Fusion Builder (Avada) ===
		if ( FusionParser::is_fusion_post( $post_id ) ) {
			try {
				return FusionParser::translate( $content, $target_lang, $ai_service );
			} catch ( \Exception $e ) {
				// Fallback: แปลแบบ plain HTML
				return $ai_service->translate_html( $content, $target_lang );
			}
		}

		// === ตรวจสอบ Gutenberg Blocks ===
		if ( GutenbergParser::has_blocks( $content ) ) {
			return $this->translate_gutenberg_content( $content, $target_lang, $ai_service );
		}

		// === Classic Editor - แปลด้วย AI โดยตรง ===
		return $ai_service->translate_html( $content, $target_lang );
	}

	/**
	 * แปล Gutenberg blocks content
	 * 
	 * @param string $content Content with blocks
	 * @param string $target_lang Target language
	 * @param AIService $ai_service AI Service
	 * @return string Translated content with blocks preserved
	 */
	private function translate_gutenberg_content( $content, $target_lang, $ai_service ) {
		try {
			return GutenbergParser::translate( $content, $target_lang, $ai_service );
		} catch ( \Exception $e ) {
			// Fallback: แปลแบบ plain HTML
			return $ai_service->translate_html( $content, $target_lang );
		}
	}

	/**
	 * แปล Elementor content
	 * 
	 * สำหรับ Elementor จะแปล 2 ส่วน:
	 * 1. Elementor data (JSON) - บันทึกแยกใน meta
	 * 2. post_content (fallback) - แปลปกติ
	 * 
	 * @param int $post_id Post ID
	 * @param string $content Post content (fallback)
	 * @param string $target_lang Target language
	 * @param AIService $ai_service AI Service
	 * @return string Translated content
	 */
	private function translate_elementor_content( $post_id, $content, $target_lang, $ai_service ) {
		try {
			// แปล Elementor data และบันทึกแยก
			$translated_data = ElementorParser::translate( $post_id, $target_lang, $ai_service );
			
			if ( $translated_data ) {
				// บันทึก Elementor data ที่แปลแล้ว
				ElementorParser::save_translated_data( $post_id, $target_lang, $translated_data );
			}

			// แปล post_content ด้วย (สำหรับ SEO และ fallback)
			return $ai_service->translate_html( $content, $target_lang );

		} catch ( \Exception $e ) {
			// Fallback: แปลแบบ plain HTML
			return $ai_service->translate_html( $content, $target_lang );
		}
	}

	/**
	 * ตรวจสอบประเภท content
	 * 
	 * @param int $post_id Post ID
	 * @param string $content Post content
	 * @return string Content type: 'elementor', 'gutenberg', 'classic'
	 */
	private function detect_content_type( $post_id, $content ) {
		if ( ElementorParser::is_elementor_post( $post_id ) ) {
			return 'elementor';
		}
		
		if ( FusionParser::is_fusion_post( $post_id ) ) {
			return 'fusion';
		}
		
		if ( GutenbergParser::has_blocks( $content ) ) {
			return 'gutenberg';
		}

		return 'classic';
	}

	/**
	 * บันทึกคำแปลโดยไม่ใช้ AI (manual)
	 * 
	 * @param int $post_id Post ID
	 * @param string $lang รหัสภาษา
	 * @param string $title ชื่อแปล
	 * @param string $content เนื้อหาแปล
	 * @return bool
	 */
	public function save_translation( $post_id, $lang, $title, $content ) {
		return \GovHybridTranslator\Core\TranslationMeta::save(
			$post_id,
			$lang,
			$title,
			$content
		);
	}

	/**
	 * แทนที่คำศัพท์ต้นฉบับด้วยคำแปลจาก Glossary
	 * 
	 * ลำดับการค้นหาคำแปล:
	 * 1. ค้นหา meta key ตามภาษา: _gov_glossary_{lang}_term
	 * 2. Fallback เป็นภาษาอังกฤษ: _gov_glossary_en_term
	 * 3. Fallback เป็น post_content
	 *
	 * @param string $content เนื้อหาที่จะประมวลผล
	 * @param string $target_lang รหัสภาษาเป้าหมาย
	 * @return string เนื้อหาที่แทนที่คำแล้ว
	 */
	private function replace_glossary_terms( $content, $target_lang = 'en' ) {
		// ดึงคำศัพท์ทั้งหมดจาก Glossary
		$args = [
			'post_type'      => 'gov_glossary',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		];
		$glossary_posts = get_posts( $args );

		// วนลูปแทนที่คำทีละคำ
		foreach ( $glossary_posts as $term ) {
			$source_word = $term->post_title; // คำต้นฉบับ (ภาษาไทย)
			
			// === ค้นหาคำแปลตามภาษาเป้าหมาย ===
			// สร้าง meta key: _gov_glossary_en_term, _gov_glossary_zh_term ฯลฯ
			$meta_key = '_gov_glossary_' . $target_lang . '_term';
			$target_word = get_post_meta( $term->ID, $meta_key, true );
			
			// Fallback 1: ใช้ภาษาอังกฤษถ้าไม่มีภาษาเป้าหมาย
			if ( empty( $target_word ) && $target_lang !== 'en' ) {
				$target_word = get_post_meta( $term->ID, '_gov_glossary_en_term', true );
			}

			// Fallback 2: ใช้ post_content ถ้าไม่มี meta
			if ( empty( $target_word ) ) {
				$target_word = $term->post_content;
			}

			// แทนที่คำถ้ามีทั้งคู่
			if ( ! empty( $source_word ) && ! empty( $target_word ) ) {
				$content = str_replace( $source_word, $target_word, $content );
			}
		}

		return $content;
	}
}
