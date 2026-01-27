<?php
/**
 * MetaBox Module
 * 
 * แสดง Meta Box สำหรับ Post Editor
 * 
 * @package GovHybridTranslator
 * @since 1.0.0
 * @modified 1.8.0 - เปลี่ยนเป็น Meta-based only
 */
namespace GovHybridTranslator\Modules;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Integrations\Post;
use GovHybridTranslator\Core\TranslationMeta;

class MetaBox {

	public function __construct() {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
		add_action( 'save_post', [ $this, 'save_meta_box' ] );
		add_action( 'admin_post_gov_create_en_draft', [ $this, 'handle_create_translation' ] );
		
		// Add meta box for Glossary CPT to enter English term
		add_action( 'add_meta_boxes_gov_glossary', [ $this, 'add_glossary_meta_box' ] );
	}

	public function add_meta_box() {
		add_meta_box(
			'gov_translator_box',
			'Gov Hybrid Translator',
			[ $this, 'render_meta_box' ],
			[ 'post', 'page' ],
			'side',
			'high'
		);
	}

	public function add_glossary_meta_box() {
		add_meta_box(
			'gov_glossary_en_term_box',
			'English Translation',
			[ $this, 'render_glossary_meta_box' ],
			'gov_glossary',
			'normal',
			'high'
		);
	}

	public function render_meta_box( $post ) {
		// ตรวจสอบว่ามี translation ใน meta หรือไม่
		$has_en_translation = TranslationMeta::has_translation($post->ID, 'en');

		echo '<div class="gov-translator-admin">';
		echo '<p><strong>Language:</strong> Thai (Default)</p>';
		
		if ( $has_en_translation ) {
			$en_title = TranslationMeta::get_title($post->ID, 'en');
			echo '<p>✅ English Translation: <em>' . esc_html($en_title) . '</em></p>';
			echo '<p><a href="' . esc_url(add_query_arg('lang', 'en', get_permalink($post->ID))) . '" target="_blank">View English Version</a></p>';
		} else {
			$create_url = admin_url( 'admin-post.php?action=gov_create_en_draft&post_id=' . $post->ID );
			echo '<a href="' . esc_url( $create_url ) . '" class="button button-primary">Translate to English</a>';
			echo '<p class="description">AI will translate and save to meta.</p>';
		}
		
		echo '</div>';
	}

	public function render_glossary_meta_box( $post ) {
		$en_term = get_post_meta( $post->ID, '_gov_glossary_en_term', true );
		?>
		<label for="gov_glossary_en_term">English Term:</label>
		<input type="text" name="gov_glossary_en_term" id="gov_glossary_en_term" value="<?php echo esc_attr( $en_term ); ?>" class="widefat">
		<?php
	}

	public function save_meta_box( $post_id ) {
		if ( isset( $_POST['gov_glossary_en_term'] ) ) {
			update_post_meta( $post_id, '_gov_glossary_en_term', sanitize_text_field( $_POST['gov_glossary_en_term'] ) );
		}
	}

	/**
	 * Handle create translation action (Meta-based)
	 * 
	 * @since 1.8.0 - เปลี่ยนจาก Clone เป็น Meta-based
	 */
	public function handle_create_translation() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( 'Unauthorized' );
		}

		$post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;
		if ( ! $post_id ) {
			wp_die( 'Invalid Post ID' );
		}

		// ใช้ Meta-based แทน Clone
		$translator = new Post();
		$result = $translator->translate_to_meta( $post_id, 'en' );

		if ( is_wp_error( $result ) ) {
			wp_die( $result->get_error_message() );
		}

		// Redirect กลับไปหน้า edit post
		wp_redirect( get_edit_post_link( $post_id, 'raw' ) . '&message=gov_translation_saved' );
		exit;
	}
}
