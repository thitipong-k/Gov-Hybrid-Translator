<?php
/**
 * REST API Class
 * 
 * จัดการ REST API endpoints สำหรับ Gov Hybrid Translator
 * รองรับการเรียกใช้จาก headless WordPress หรือ external apps
 * 
 * Namespace: /wp-json/gov-translator/v1/
 * 
 * @package GovHybridTranslator
 * @since 1.5.0
 */
namespace GovHybridTranslator\API;

// ป้องกันการเข้าถึงไฟล์โดยตรง
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Core\TranslationStatus;
use GovHybridTranslator\Core\TranslationCache;
use GovHybridTranslator\Integrations\Post;
use GovHybridTranslator\Service\AIService;

class RestAPI {

    /**
     * @var string API Namespace
     */
    const NAMESPACE = 'gov-translator/v1';

    /**
     * ลงทะเบียน REST routes
     */
    public function register() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * ลงทะเบียน REST routes ทั้งหมด
     */
    public function register_routes() {
        // === GET /translations ===
        // ดึง translations ทั้งหมด
        register_rest_route(self::NAMESPACE, '/translations', [
            'methods' => 'GET',
            'callback' => [$this, 'get_translations'],
            'permission_callback' => [$this, 'check_read_permission'],
            'args' => [
                'page' => [
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                ],
                'per_page' => [
                    'default' => 10,
                    'sanitize_callback' => 'absint',
                ],
                'status' => [
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // === GET /translations/{id} ===
        // ดึง translation เดี่ยว
        register_rest_route(self::NAMESPACE, '/translations/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_translation'],
            'permission_callback' => [$this, 'check_read_permission'],
            'args' => [
                'id' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                ],
            ],
        ]);

        // === POST /translate ===
        // แปล content on-demand
        register_rest_route(self::NAMESPACE, '/translate', [
            'methods' => 'POST',
            'callback' => [$this, 'translate_content'],
            'permission_callback' => [$this, 'check_translate_permission'],
            'args' => [
                'content' => [
                    'required' => true,
                    'sanitize_callback' => 'wp_kses_post',
                ],
                'target_lang' => [
                    'default' => 'en',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // === GET /stats ===
        // ดึงสถิติ
        register_rest_route(self::NAMESPACE, '/stats', [
            'methods' => 'GET',
            'callback' => [$this, 'get_stats'],
            'permission_callback' => [$this, 'check_read_permission'],
        ]);

        // === POST /translate-post ===
        // แปล Post ทั้งหมด
        register_rest_route(self::NAMESPACE, '/translate-post', [
            'methods' => 'POST',
            'callback' => [$this, 'translate_post'],
            'permission_callback' => [$this, 'check_translate_permission'],
            'args' => [
                'post_id' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                ],
                'target_lang' => [
                    'default' => 'en',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
    }

    /**
     * Permission: ตรวจสอบสิทธิ์อ่าน
     * 
     * @return bool|WP_Error
     */
    public function check_read_permission() {
        // อนุญาตให้ทุกคนอ่านได้
        return true;
    }

    /**
     * Permission: ตรวจสอบสิทธิ์แปล
     * 
     * @return bool|WP_Error
     */
    public function check_translate_permission() {
        // ต้อง login และมีสิทธิ์ edit_posts
        if (!current_user_can('edit_posts')) {
            return new \WP_Error(
                'rest_forbidden',
                'You do not have permission to translate content.',
                ['status' => 403]
            );
        }
        return true;
    }

    /**
     * GET /translations
     * ดึง translations ทั้งหมด
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_translations($request) {
        $page = $request->get_param('page');
        $per_page = min($request->get_param('per_page'), 100); // จำกัดที่ 100
        $status_filter = $request->get_param('status');

        // ดึง Posts ที่มี translation
        $args = [
            'post_type' => ['post', 'page'],
            'post_status' => 'any',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'meta_query' => [
                [
                    'key' => '_gov_translator_group_id',
                    'compare' => 'EXISTS',
                ],
            ],
        ];

        $query = new \WP_Query($args);
        $translation_status = new TranslationStatus();

        $translations = [];
        foreach ($query->posts as $post) {
            $status = $translation_status->get_status($post->ID);

            // กรองตาม status ถ้าระบุ
            if (!empty($status_filter) && $status !== $status_filter) {
                continue;
            }

            $translations[] = $this->format_post_response($post, $status);
        }

        return new \WP_REST_Response([
            'translations' => $translations,
            'total' => $query->found_posts,
            'total_pages' => $query->max_num_pages,
            'page' => $page,
            'per_page' => $per_page,
        ], 200);
    }

    /**
     * GET /translations/{id}
     * ดึง translation เดี่ยว
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_translation($request) {
        $post_id = $request->get_param('id');
        $post = get_post($post_id);

        if (!$post) {
            return new \WP_Error(
                'not_found',
                'Translation not found.',
                ['status' => 404]
            );
        }

        $translation_status = new TranslationStatus();
        $status = $translation_status->get_status($post_id);

        // ดึง related translations
        $related = $translation_status->get_translated_posts($post_id);
        $related_data = [];

        foreach ($related as $rel) {
            $related_data[] = [
                'id' => $rel->ID,
                'title' => $rel->post_title,
                'lang' => get_post_meta($rel->ID, '_gov_translator_lang', true),
                'url' => get_permalink($rel->ID),
            ];
        }

        $response = $this->format_post_response($post, $status);
        $response['related_translations'] = $related_data;

        return new \WP_REST_Response($response, 200);
    }

    /**
     * POST /translate
     * แปล content on-demand
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function translate_content($request) {
        $content = $request->get_param('content');
        $target_lang = $request->get_param('target_lang');

        if (empty($content)) {
            return new \WP_Error(
                'invalid_content',
                'Content is required.',
                ['status' => 400]
            );
        }

        $glossary_replacer = new \GovHybridTranslator\Service\GlossaryReplacer();
        $glossary_hash = $glossary_replacer->get_glossary_hash();

        // ตรวจสอบ cache (รวม glossary_hash ใน cache key)
        $cache = new TranslationCache();
        $cache_key = md5($content . '_' . $target_lang . '_' . $glossary_hash);
        $cached = $cache->get($cache_key);

        if ($cached !== false) {
            return new \WP_REST_Response([
                'translated' => $cached,
                'from_cache' => true,
            ], 200);
        }

        // Pre-process (Protect Glossary Terms ด้วย Placeholder)
        $protected = $glossary_replacer->protect_glossary_terms($content, $target_lang);

        // แปลด้วย AI
        $ai_service = new AIService();
        $translated_raw = $ai_service->translate_html($protected['protected_content'], $target_lang);

        // Post-process (Restore Glossary Terms)
        $translated = $glossary_replacer->restore_glossary_terms($translated_raw, $protected['map']);

        // บันทึก cache
        $cache->set($cache_key, $translated);

        return new \WP_REST_Response([
            'translated' => $translated,
            'from_cache' => false,
        ], 200);
    }

    /**
     * POST /translate-post
     * แปล Post ทั้งหมด และเก็บใน post_meta (Meta-based)
     * 
     * @since 1.8.0 - เปลี่ยนจาก Clone เป็น Meta-based
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function translate_post($request) {
        $post_id = $request->get_param('post_id');
        $target_lang = $request->get_param('target_lang');

        $post = get_post($post_id);
        if (!$post) {
            return new \WP_Error(
                'not_found',
                'Post not found.',
                ['status' => 404]
            );
        }

        // แปล Post และเก็บใน Meta (ไม่สร้าง post ใหม่)
        $post_translator = new Post();
        $result = $post_translator->translate_to_meta($post_id, $target_lang);

        if (is_wp_error($result)) {
            return new \WP_Error(
                'translation_failed',
                $result->get_error_message(),
                ['status' => 500]
            );
        }

        return new \WP_REST_Response([
            'post_id' => $post_id,
            'target_lang' => $target_lang,
            'message' => 'Translation saved to meta',
            'url' => get_permalink($post_id),
        ], 200);
    }

    /**
     * GET /stats
     * ดึงสถิติ
     * 
     * @return WP_REST_Response
     */
    public function get_stats($request) {
        $translation_status = new TranslationStatus();
        $stats = $translation_status->get_statistics();

        $cache_stats = TranslationCache::get_stats();

        return new \WP_REST_Response([
            'translation_stats' => $stats,
            'cache_stats' => $cache_stats,
        ], 200);
    }

    /**
     * Format Post response
     * 
     * @param WP_Post $post
     * @param string $status
     * @return array
     */
    private function format_post_response($post, $status) {
        return [
            'id' => $post->ID,
            'title' => $post->post_title,
            'type' => $post->post_type,
            'status' => $status,
            'lang' => get_post_meta($post->ID, '_gov_translator_lang', true) ?: 'th',
            'group_id' => get_post_meta($post->ID, '_gov_translator_group_id', true),
            'original_id' => get_post_meta($post->ID, '_gov_translator_original_id', true),
            'url' => get_permalink($post->ID),
            'modified' => $post->post_modified,
        ];
    }
}
