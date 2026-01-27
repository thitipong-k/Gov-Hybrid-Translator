<?php
/**
 * Batch Translator Class
 * 
 * จัดการการแปลหลาย Posts พร้อมกัน
 * รองรับ Bulk Action ใน Posts/Pages list
 * ใช้ WordPress Cron สำหรับ background processing
 * 
 * @package GovHybridTranslator
 * @since 1.5.0
 */
namespace GovHybridTranslator\Modules;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

class BatchTranslator {

    /**
     * @var string Cron hook name
     */
    const CRON_HOOK = 'ght_batch_translate_process';

    /**
     * @var string Option name สำหรับเก็บ queue
     */
    const QUEUE_OPTION = 'ght_batch_translate_queue';

    /**
     * ลงทะเบียน hooks
     * 
     * Hooks ที่ใช้:
     * - bulk_actions-edit-post: เพิ่ม Bulk Action
     * - handle_bulk_actions-edit-post: จัดการ Bulk Action
     * - admin_notices: แสดง progress
     * - ght_batch_translate_process: Cron job
     */
    public function register() {
        // === Bulk Actions สำหรับ Posts ===
        add_filter('bulk_actions-edit-post', [$this, 'add_bulk_action']);
        add_filter('handle_bulk_actions-edit-post', [$this, 'handle_bulk_action'], 10, 3);

        // === Bulk Actions สำหรับ Pages ===
        add_filter('bulk_actions-edit-page', [$this, 'add_bulk_action']);
        add_filter('handle_bulk_actions-edit-page', [$this, 'handle_bulk_action'], 10, 3);

        // === Admin Notices ===
        add_action('admin_notices', [$this, 'show_progress_notice']);

        // === Cron Hook ===
        add_action(self::CRON_HOOK, [$this, 'process_queue']);

        // === AJAX สำหรับ progress ===
        add_action('wp_ajax_ght_batch_progress', [$this, 'ajax_get_progress']);
    }

    /**
     * เพิ่ม Bulk Action "Translate to English"
     * 
     * @param array $bulk_actions Bulk actions ที่มีอยู่
     * @return array Bulk actions ที่อัพเดท
     */
    public function add_bulk_action($bulk_actions) {
        $bulk_actions['ght_translate_en'] = '🌐 Translate to English';
        return $bulk_actions;
    }

    /**
     * จัดการเมื่อผู้ใช้เลือก Bulk Action
     * 
     * ขั้นตอน:
     * 1. รับ Post IDs ที่เลือก
     * 2. เพิ่มเข้า Queue
     * 3. Schedule Cron job
     * 4. Redirect กลับพร้อม message
     * 
     * @param string $redirect_to URL ที่จะ redirect
     * @param string $doaction Action ที่เลือก
     * @param array $post_ids Post IDs ที่เลือก
     * @return string URL ที่จะ redirect
     */
    public function handle_bulk_action($redirect_to, $doaction, $post_ids) {
        if ($doaction !== 'ght_translate_en') {
            return $redirect_to;
        }

        // กรอง Posts ที่ยังไม่มี translation
        $to_translate = [];
        foreach ($post_ids as $post_id) {
            $group_id = get_post_meta($post_id, '_gov_translator_group_id', true);
            $original_id = get_post_meta($post_id, '_gov_translator_original_id', true);
            
            // ข้ามถ้าเป็น translation อยู่แล้ว
            if (!empty($original_id)) {
                continue;
            }
            
            $to_translate[] = $post_id;
        }

        if (empty($to_translate)) {
            $redirect_to = add_query_arg('ght_batch_error', 'no_posts', $redirect_to);
            return $redirect_to;
        }

        // เพิ่มเข้า Queue
        $this->add_to_queue($to_translate, 'en');

        // Schedule Cron job (ถ้ายังไม่มี)
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_single_event(time(), self::CRON_HOOK);
        }

        // Redirect พร้อมจำนวนที่จะแปล
        $redirect_to = add_query_arg([
            'ght_batch_started' => count($to_translate),
        ], $redirect_to);

        return $redirect_to;
    }

    /**
     * เพิ่ม Posts เข้า Queue
     * 
     * @param array $post_ids Post IDs ที่จะแปล
     * @param string $target_lang ภาษาเป้าหมาย
     */
    private function add_to_queue($post_ids, $target_lang) {
        $queue = get_option(self::QUEUE_OPTION, []);

        foreach ($post_ids as $post_id) {
            $queue[] = [
                'post_id' => $post_id,
                'target_lang' => $target_lang,
                'status' => 'pending',
                'added_at' => current_time('mysql'),
            ];
        }

        update_option(self::QUEUE_OPTION, $queue);
    }

    /**
     * ประมวลผล Queue (เรียกจาก Cron)
     * 
     * ขั้นตอน:
     * 1. ดึง item แรกจาก Queue
     * 2. แปลด้วย Post::translate_to_meta() (Meta-based)
     * 3. อัพเดทสถานะ
     * 4. Schedule ตัวเองอีกครั้งถ้ายังมี items
     * 
     * @since 1.8.0 - เปลี่ยนจาก Clone เป็น Meta-based
     */
    public function process_queue() {
        $queue = get_option(self::QUEUE_OPTION, []);

        if (empty($queue)) {
            return;
        }

        // ดึง item แรกที่ยัง pending
        $index_to_process = null;
        foreach ($queue as $index => $item) {
            if ($item['status'] === 'pending') {
                $index_to_process = $index;
                break;
            }
        }

        if ($index_to_process === null) {
            // ไม่มี pending items → ล้าง queue
            delete_option(self::QUEUE_OPTION);
            return;
        }

        $item = $queue[$index_to_process];

        // === แปล Post และเก็บใน Meta (ไม่สร้าง post ใหม่) ===
        $post_translator = new Post();
        $result = $post_translator->translate_to_meta($item['post_id'], $item['target_lang']);

        // อัพเดทสถานะ
        if (is_wp_error($result)) {
            $queue[$index_to_process]['status'] = 'error';
            $queue[$index_to_process]['error'] = $result->get_error_message();
        } else {
            $queue[$index_to_process]['status'] = 'completed';
            $queue[$index_to_process]['post_id'] = $item['post_id'];
        }

        $queue[$index_to_process]['processed_at'] = current_time('mysql');

        update_option(self::QUEUE_OPTION, $queue);

        // ตรวจสอบว่ายังมี pending อีกไหม
        $has_pending = false;
        foreach ($queue as $q_item) {
            if ($q_item['status'] === 'pending') {
                $has_pending = true;
                break;
            }
        }

        // Schedule ตัวเองอีกครั้งถ้ายังมี pending
        if ($has_pending) {
            wp_schedule_single_event(time() + 5, self::CRON_HOOK);
        }
    }

    /**
     * แสดง Progress Notice ใน Admin
     */
    public function show_progress_notice() {
        // แสดง success message
        if (isset($_GET['ght_batch_started'])) {
            $count = intval($_GET['ght_batch_started']);
            echo '<div class="notice notice-info is-dismissible">';
            echo '<p><strong>🌐 Batch Translation Started!</strong></p>';
            echo '<p>' . sprintf('%d posts จะถูกแปล background', $count) . '</p>';
            echo '</div>';
        }

        // แสดง error message
        if (isset($_GET['ght_batch_error']) && $_GET['ght_batch_error'] === 'no_posts') {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p>ไม่มี Posts ที่สามารถแปลได้ (อาจแปลไปแล้วหรือเป็น translation อยู่)</p>';
            echo '</div>';
        }

        // แสดง progress ถ้ามี Queue
        $queue = get_option(self::QUEUE_OPTION, []);
        if (!empty($queue)) {
            $total = count($queue);
            $completed = 0;
            $errors = 0;

            foreach ($queue as $item) {
                if ($item['status'] === 'completed') $completed++;
                if ($item['status'] === 'error') $errors++;
            }

            $pending = $total - $completed - $errors;

            if ($pending > 0) {
                echo '<div class="notice notice-info">';
                echo '<p><strong>🌐 Batch Translation Progress</strong></p>';
                echo '<p>Completed: ' . $completed . ' / ' . $total;
                if ($errors > 0) {
                    echo ' (Errors: ' . $errors . ')';
                }
                echo '</p>';
                echo '<p><em>Page จะ refresh อัตโนมัติเมื่อเสร็จ</em></p>';
                echo '</div>';
            }
        }
    }

    /**
     * AJAX: ดึง Progress
     */
    public function ajax_get_progress() {
        $queue = get_option(self::QUEUE_OPTION, []);

        $total = count($queue);
        $completed = 0;
        $errors = 0;

        foreach ($queue as $item) {
            if ($item['status'] === 'completed') $completed++;
            if ($item['status'] === 'error') $errors++;
        }

        wp_send_json_success([
            'total' => $total,
            'completed' => $completed,
            'errors' => $errors,
            'pending' => $total - $completed - $errors,
        ]);
    }

    /**
     * ล้าง Queue (ใช้หลังเสร็จหรือ cancel)
     */
    public static function clear_queue() {
        delete_option(self::QUEUE_OPTION);
    }
}
