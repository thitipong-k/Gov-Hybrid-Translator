<?php
/**
 * Email Notifier Module - ระบบแจ้งเตือนทางอีเมล
 * 
 * รับผิดชอบในการ:
 * - ส่งอีเมลแจ้งเตือนเมื่อมีคำแปลรอตรวจสอบ
 * - ส่งอีเมลแจ้งเตือนผลการอนุมัติ
 * 
 * @package GovHybridTranslator
 * @since 2.5.0
 */

namespace GovHybridTranslator\Modules;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

use GovHybridTranslator\Core\Capabilities;

class EmailNotifier {

    /**
     * ส่งคำร้องขอให้ตรวจสอบ (Send Review Request)
     * ส่งหา Administrator หรือ Editor ทั้งหมด
     * 
     * @param int $post_id Post ID
     * @param string $lang ภาษา
     * @param int $user_id ผู้ขอ (Default: current user)
     */
    public static function send_review_request($post_id, $lang, $user_id = 0) {
        if (!$user_id) $user_id = get_current_user_id();
        $user_info = get_userdata($user_id);
        $user_name = $user_info ? $user_info->display_name : 'Unknown User';
        
        $post_title = get_the_title($post_id);
        $edit_link = get_edit_post_link($post_id);
        
        // Subject
        $subject = sprintf(__('[Gov Translator] New Translation Pending Review: "%s" (%s)', 'gov-hybrid-translator'), $post_title, strtoupper($lang));
        
        // Message
        $message  = sprintf(__('User "%s" has submitted a translation for review.', 'gov-hybrid-translator'), $user_name) . "\r\n\r\n";
        $message .= sprintf(__('Post: %s', 'gov-hybrid-translator'), $post_title) . "\r\n";
        $message .= sprintf(__('Language: %s', 'gov-hybrid-translator'), strtoupper($lang)) . "\r\n\r\n";
        $message .= __('Please review and approve/reject via the Review Queue:', 'gov-hybrid-translator') . "\r\n";
        $message .= admin_url('admin.php?page=gov-hybrid-translator-review') . "\r\n";
        
        // Get recipients: users with 'ght_approve_translation' capability
        $users = get_users(['role__in' => ['administrator', 'editor']]); // Strict query first
        $recipients = [];
        
        foreach ($users as $user) {
            if ($user->has_cap('ght_approve_translation')) {
                $recipients[] = $user->user_email;
            }
        }
        
        if (!empty($recipients)) {
            wp_mail($recipients, $subject, $message);
        }
    }

    /**
     * ส่งแจ้งเตือนผลการอนุมัติ (Send Approval Notification)
     * ส่งหาเจ้าของคำแปล (คนล่าสุดที่แก้ไข)
     * 
     * @param int $post_id Post ID
     * @param string $lang ภาษา
     * @param string $status สถานะ (approved/published/draft)
     */
    public static function send_approval_notification($post_id, $lang, $status) {
        global $wpdb;
        
        // หาคนล่าสุดที่แปล (จาก log) หรือเจ้าของโพสต์
        $last_log = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}ght_activity_logs 
             WHERE action IN ('translation_saved', 'translation_updated') 
             AND target_id = %d 
             ORDER BY id DESC LIMIT 1",
             $post_id
        ));
        
        $recipient_id = $last_log ? $last_log->user_id : get_post_field('post_author', $post_id);
        $recipient = get_userdata($recipient_id);
        
        if (!$recipient) return;
        
        $post_title = get_the_title($post_id);
        $post_link = get_permalink($post_id); // This will link to default lang if not handled
        $preview_link = \GovHybridTranslator\Routing\Router::add_language_prefix($post_link, $lang);

        if ($status === 'published' || $status === 'approved') {
            $subject = sprintf(__('[Gov Translator] Translation Approved: "%s" (%s)', 'gov-hybrid-translator'), $post_title, strtoupper($lang));
            $message  = sprintf(__('Congratulations! Your translation for "%s" has been approved and published.', 'gov-hybrid-translator'), $post_title) . "\r\n\r\n";
            $message .= __('View live:', 'gov-hybrid-translator') . "\r\n";
            $message .= $preview_link . "\r\n";
        } else {
            // Rejected
            $subject = sprintf(__('[Gov Translator] Translation Returned: "%s" (%s)', 'gov-hybrid-translator'), $post_title, strtoupper($lang));
            $message  = sprintf(__('Your translation for "%s" has been returned to draft.', 'gov-hybrid-translator'), $post_title) . "\r\n\r\n";
            $message .= __('Please edit and submit again.', 'gov-hybrid-translator') . "\r\n";
        }
        
        wp_mail($recipient->user_email, $subject, $message);
    }
}
