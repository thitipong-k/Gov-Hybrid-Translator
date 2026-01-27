<?php
/**
 * Translator Service Class
 * 
 * จัดการการแปลเนื้อหาและเก็บใน post_meta (Meta-based)
 * ไม่สร้าง Post ใหม่
 * 
 * @package GovHybridTranslator
 * @since 1.0.0
 * @modified 1.8.0 - เปลี่ยนเป็น Meta-based only
 */
namespace GovHybridTranslator\Service;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Core\TranslationMeta;

class Translator {

	/**
	 * แปล Post และเก็บใน post_meta (Meta-based)
	 *
	 * @param int $post_id Post ID ต้นฉบับ
	 * @param string|null $custom_title ชื่อที่กำหนดเอง (Optional)
	 * @return bool|WP_Error true ถ้าสำเร็จ หรือ error
	 */
	public function translate_to_english( $post_id, $custom_title = null ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'invalid_post', 'Post not found.' );
		}

		// 1. Prepare Content
		$thai_content = $post->post_content;
        $thai_title = $post->post_title;
        
        // AI Translation
        $ai_service = new AIService();
        $translated_content = $ai_service->translate_html($thai_content, 'en');
        
        if ( ! empty( $custom_title ) ) {
            $translated_title = $custom_title;
        } else {
            $translated_title = $ai_service->translate_html($thai_title, 'en');
        }

        // Apply Glossary (Force replace after AI)
		$english_content = $this->replace_glossary_terms( $translated_content );
        $english_title = $this->replace_glossary_terms( $translated_title );

		// 2. Save to Meta (ไม่สร้าง Post ใหม่)
		$result = TranslationMeta::save(
			$post_id,
			'en',
			$english_title,
			$english_content,
			'' // excerpt
		);

		return $result ? true : new \WP_Error( 'save_failed', 'Failed to save translation' );
	}


	/**
	 * Replace Thai terms with English terms from Glossary.
	 *
	 * @param string $content
	 * @return string
	 */
	private function replace_glossary_terms( $content ) {
		// Fetch all glossary terms
		$args = [
			'post_type'      => 'gov_glossary',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		];
		$glossary_posts = get_posts( $args );

		foreach ( $glossary_posts as $term ) {
			$thai_word = $term->post_title;
			// Ideally, English word is stored in a meta field or content. 
			// For this implementation, let's assume it's in the content or a specific meta.
			// Let's use a meta field '_gov_glossary_en_term' for better structure, 
			// but if using standard editor, maybe content. Let's stick to a meta for clarity.
			$english_word = get_post_meta( $term->ID, '_gov_glossary_en_term', true );

			if ( empty( $english_word ) ) {
				// Fallback to content if meta is empty
				$english_word = $term->post_content;
			}

			if ( ! empty( $thai_word ) && ! empty( $english_word ) ) {
				// Simple str_replace, can be improved with regex for word boundaries
				$content = str_replace( $thai_word, $english_word, $content );
			}
		}

		return $content;
	}
}
