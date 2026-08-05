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
use GovHybridTranslator\Service\GlossaryReplacer;
use GovHybridTranslator\Parsers\GutenbergParser;
use GovHybridTranslator\Parsers\ElementorParser;
use GovHybridTranslator\Parsers\FusionParser;

class Post {

	/**
	 * แปล Post/Page เก็บใน post_meta (ไม่สร้าง Post ใหม่)
	 * 
	 * ขั้นตอนการทำงาน:
	 * 1. ดึงเนื้อหาต้นฉบับ
	 * 2. Pre-process ซ่อนคำ Glossary ด้วย Placeholder (GlossaryReplacer)
	 * 3. แปลเนื้อหาด้วย AI Service (ผ่าน parser ที่เหมาะสม)
	 * 4. Post-process คืนค่าคำแปลจาก Glossary เข้าสู่ Placeholder
	 * 5. บันทึกลง post_meta ด้วย TranslationMeta
	 *
	 * @param int $post_id Post ID ต้นฉบับ
	 * @param string $target_lang รหัสภาษาเป้าหมาย (เช่น 'en', 'zh', 'ja')
	 * @param string|null $custom_title ชื่อแปลที่กำหนดเอง (ถ้ามี)
	 * @param string $status สถานะ (draft, published) Default: published
	 * @return bool|WP_Error true ถ้าสำเร็จ หรือ error
	 */
	public function translate_to_meta( $post_id, $target_lang = 'en', $custom_title = null, $status = 'published' ) {
		// ดึง Post ต้นฉบับ
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'invalid_post', 'ไม่พบโพสต์' );
		}

		// === ขั้นตอนที่ 1: เตรียมเนื้อหา, AI Service และ GlossaryReplacer ===
		$original_content = $post->post_content;
        $original_title = $post->post_title;
        $original_excerpt = $post->post_excerpt;
        
        // สร้าง AI Service & GlossaryReplacer
        $ai_service = new AIService();
        $glossary_replacer = new GlossaryReplacer();
        
        // === ตรวจสอบว่า AI Provider พร้อมใช้งาน ===
        // ถ้าไม่มี API Key จะ return error แทนที่จะ save Thai content
        if ( ! $ai_service->isReady() ) {
            $ai_error = $ai_service->getLastError();
            return new \WP_Error( 
                'ai_not_ready', 
                'ไม่สามารถแปลได้: ' . ($ai_error ?: 'กรุณาตั้งค่า API Key ใน Settings')
            );
        }

        // === ขั้นตอนที่ 2: Pre-process (Protect Glossary Terms ด้วย Placeholder) ===
        $protected_content = $glossary_replacer->protect_glossary_terms( $original_content, $target_lang );
        $protected_title   = $glossary_replacer->protect_glossary_terms( $original_title, $target_lang );
        $protected_excerpt = !empty($original_excerpt) 
            ? $glossary_replacer->protect_glossary_terms( $original_excerpt, $target_lang ) 
            : ['protected_content' => '', 'map' => []];

        // === ขั้นตอนที่ 3: ตรวจสอบประเภท content และแปลด้วย AI ===
        $translated_content_raw = $this->translate_content_by_type(
            $post_id,
            $protected_content['protected_content'],
            $target_lang,
            $ai_service
        );

        // แปล excerpt ด้วย AI โดยตรง
        $translated_excerpt_raw = !empty($protected_excerpt['protected_content']) 
            ? $ai_service->translate_html( $protected_excerpt['protected_content'], $target_lang )
            : '';
        
        // === ขั้นตอนที่ 4: แปล Title ===
        if ( ! empty( $custom_title ) ) {
            $translated_title_raw = $custom_title;
        } else {
            $translated_title_raw = $ai_service->translate_html( $protected_title['protected_content'], $target_lang );
        }

        // === ขั้นตอนที่ 5: Post-process (Restore Glossary Terms จาก Placeholder) ===
        $final_content = $glossary_replacer->restore_glossary_terms( $translated_content_raw, $protected_content['map'] );
        $final_title   = $glossary_replacer->restore_glossary_terms( $translated_title_raw, $protected_title['map'] );
        $final_excerpt = $glossary_replacer->restore_glossary_terms( $translated_excerpt_raw, $protected_excerpt['map'] );

        // Fallback: หากยังมีคำภาษาไทยหลงเหลืออยู่ ให้ลองแทนที่ด้วย replace_glossary_terms
		$final_content = $this->replace_glossary_terms( $final_content, $target_lang );
        $final_title   = $this->replace_glossary_terms( $final_title, $target_lang );
        $final_excerpt = $this->replace_glossary_terms( $final_excerpt, $target_lang );

		// === ขั้นตอนที่ 6: บันทึกลง post_meta ===
		$result = \GovHybridTranslator\Core\TranslationMeta::save(
			$post_id,
			$target_lang,
			$final_title,
			$final_content,
			$final_excerpt,
			$status
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

		if ( empty( $glossary_posts ) ) {
			return $content;
		}

		// เตรียมข้อมูลสำหรับ Regex
		$replacements = [];
		foreach ( $glossary_posts as $term ) {
			$source_word = trim($term->post_title); // คำต้นฉบับ (ภาษาไทย)
			
			if ( empty($source_word) ) continue;

			// === ค้นหาคำแปลตามภาษาเป้าหมาย ===
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

			if ( ! empty( $target_word ) ) {
				// สร้าง Regex Pattern
				// 1. preg_quote: ป้องกันอักขระพิเศษในคำศัพท์
				// 2. /u: รองรับ Unicode (ภาษาไทย)
				// 3. /i: Case Insensitive (ไม่สนใจตัวพิมพ์เล็กใหญ่)
				// หมายเหตุ: ไม่ใช้ \b เพราะภาษาไทยไม่มี word boundary ชัดเจนเหมือนอังกฤษ
				$replacements[$source_word] = $target_word;
			}
		}

		if ( empty( $replacements ) ) {
			return $content;
		}

		// === Smart Replacement Strategy ===
		// แยก HTML Tags ออกจากเนื้อหา เพื่อไม่ให้แทนที่ใน Attribute หรือ Tag Name
		// Pattern: แยกด้วย <...>
		$parts = preg_split( '/(<[^>]*>)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
		
		foreach ( $parts as &$part ) {
			// ถ้าเป็น Tag (เริ่มด้วย < และจบด้วย >) ให้ข้าม
			if ( strpos( $part, '<' ) === 0 && substr( $part, -1 ) === '>' ) {
				continue;
			}
			
			// แทนที่ในส่วนที่เป็น Text Content
			foreach ( $replacements as $source => $target ) {
				// ใช้ preg_replace เพื่อรองรับ Case Insensitive
				// ใช้ Pattern ที่ปลอดภัย
				$pattern = '/' . preg_quote($source, '/') . '/ui';
				$part = preg_replace( $pattern, $target, $part );
			}
		}
		
		// ประกอบร่างกลับคืน
		return implode('', $parts);
	}
}
