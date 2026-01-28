<?php
/**
 * Review Dashboard View
 * 
 * แสดงรายการคำแปลที่รอการตรวจสอบ (Status: reviewing)
 * 
 * @package GovHybridTranslator
 */

use GovHybridTranslator\Core\Capabilities;
use GovHybridTranslator\Core\TranslationMeta;
use GovHybridTranslator\Modules\EmailNotifier;

// ตรวจสอบสิทธิ์ (ต้องมีสิทธิ์ในการอนุมัติ)
if (!Capabilities::can_approve_translation()) {
    wp_die(__('You do not have permission to access this page.', 'gov-hybrid-translator'));
}

// Handle Actions (Approve/Reject)
// จัดการการอนุมัติหรือปฏิเสธคำแปลเมื่อมีการ Submit Form
$action_message = '';
if (isset($_POST['ght_review_action']) && check_admin_referer('ght_review_action_nonce')) {
    $post_id = intval($_POST['post_id']);
    $lang = sanitize_text_field($_POST['lang']);
    $action = sanitize_text_field($_POST['ght_review_action']); // approve / reject

    if ($action === 'approve') {
        // Change status to 'approved' (or 'published' depending on workflow preference)
        // เปลี่ยนสถานะเป็น published (อนุมัติแล้วเผยแพร่ทันที)
        // For strict workflow: Approved -> Published manual step
        // For auto workflow: Approved = Published
        
        $current_data = TranslationMeta::get($post_id, $lang);
        if ($current_data) {
            TranslationMeta::save(
                $post_id, $lang, 
                $current_data['title'], 
                $current_data['content'], 
                $current_data['excerpt'], 
                'published' // Auto publish on approve for convenience
            );
            
            // Send Notification
            if (class_exists('\GovHybridTranslator\Modules\EmailNotifier')) {
                EmailNotifier::send_approval_notification($post_id, $lang, 'published');
            }
            
            $action_message = '<div class="notice notice-success is-dismissible"><p>Translation approved and published.</p></div>';
        }
    } elseif ($action === 'reject') {
        // Change status back to 'draft'
        $current_data = TranslationMeta::get($post_id, $lang);
        if ($current_data) {
            TranslationMeta::save(
                $post_id, $lang, 
                $current_data['title'], 
                $current_data['content'], 
                $current_data['excerpt'], 
                'draft'
            );
            
            // Send Notification
            if (class_exists('\GovHybridTranslator\Modules\EmailNotifier')) {
                EmailNotifier::send_approval_notification($post_id, $lang, 'draft'); // draft = rejected
            }
            
            $action_message = '<div class="notice notice-warning is-dismissible"><p>Translation rejected and reverted to draft.</p></div>';
        }
    }
}

// Query Pending Items directly from DB for performance
// ดึงรายการที่รอตรวจสอบจากฐานข้อมูลโดยตรง (meta_value = 'reviewing')
global $wpdb;
$pending_items = $wpdb->get_results(
    "SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} 
     WHERE meta_key LIKE '_ght_status_%' 
     AND meta_value = 'reviewing'"
);

?>

<div class="wrap">
    <h1><?php _e('Translation Review Queue', 'gov-hybrid-translator'); ?></h1>
    
    <?php echo $action_message; ?>

    <div class="card" style="margin-top: 20px; padding: 0;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-title"><?php _e('Original Post', 'gov-hybrid-translator'); ?></th>
                    <th scope="col" class="manage-column column-lang"><?php _e('Language', 'gov-hybrid-translator'); ?></th>
                    <th scope="col" class="manage-column column-author"><?php _e('Submitted By', 'gov-hybrid-translator'); ?></th>
                    <th scope="col" class="manage-column column-date"><?php _e('Date', 'gov-hybrid-translator'); ?></th>
                    <th scope="col" class="manage-column column-actions"><?php _e('Actions', 'gov-hybrid-translator'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pending_items)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 20px;">
                            <?php _e('No pending translations found.', 'gov-hybrid-translator'); ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pending_items as $item): 
                        $post_id = $item->post_id;
                        $lang = str_replace('_ght_status_', '', $item->meta_key);
                        $post = get_post($post_id);
                        
                        if (!$post) continue;

                        $last_log = $wpdb->get_row($wpdb->prepare(
                            "SELECT user_id, timestamp FROM {$wpdb->prefix}ght_activity_logs 
                             WHERE action IN ('translation_saved', 'translation_updated') 
                             AND target_id = %d 
                             ORDER BY id DESC LIMIT 1",
                             $post_id
                        ));
                        
                        $author_id = $last_log ? $last_log->user_id : $post->post_author;
                        $user_info = get_userdata($author_id);
                        $submitted_by = $user_info ? $user_info->display_name : 'Unknown';
                    ?>
                    <tr>
                        <td>
                            <strong><a href="<?php echo get_edit_post_link($post_id); ?>"><?php echo get_the_title($post_id); ?></a></strong>
                            <div class="row-actions">
                                <span class="view"><a href="<?php echo get_permalink($post_id); ?>" target="_blank">View Original</a></span>
                            </div>
                        </td>
                        <td>
                            <span class="ght-flag ght-flag-<?php echo esc_attr($lang); ?>"></span> 
                            <?php echo strtoupper($lang); ?>
                        </td>
                        <td><?php echo esc_html($submitted_by); ?></td>
                        <td>
                            <?php echo $last_log ? $last_log->timestamp : '-'; ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <!-- Preview Button (Frontend Editor) -->
                                <?php 
                                    // Generate URL for frontend editor check
                                    // Normally we go to the page with ?ght_lang=xx
                                    $preview_url = GovHybridTranslator\Routing\Router::add_language_prefix(get_permalink($post_id), $lang);
                                ?>
                                <a href="<?php echo $preview_url; ?>" target="_blank" class="button button-secondary">
                                    <span class="dashicons dashicons-visibility" style="margin-top: 4px;"></span> Preview & Edit
                                </a>

                                <!-- Approve Form -->
                                <form method="post" style="display: inline-block;">
                                    <?php wp_nonce_field('ght_review_action_nonce'); ?>
                                    <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                                    <input type="hidden" name="lang" value="<?php echo $lang; ?>">
                                    <input type="hidden" name="ght_review_action" value="approve">
                                    <button type="submit" class="button button-primary">Approve & Publish</button>
                                </form>
                                
                                <!-- Reject Form -->
                                <form method="post" style="display: inline-block;">
                                    <?php wp_nonce_field('ght_review_action_nonce'); ?>
                                    <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                                    <input type="hidden" name="lang" value="<?php echo $lang; ?>">
                                    <input type="hidden" name="ght_review_action" value="reject">
                                    <button type="submit" class="button button-link-delete" style="color: #b32d2e;" onclick="return confirm('Reject this translation? It will revert to draft.')">Reject</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
