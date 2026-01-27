<?php
/**
 * Glossary Management View
 * 
 * @var \GovHybridTranslator\Modules\GlossaryManager $glossary_manager
 */

if (!defined('ABSPATH')) exit;

// Initialize Glossary Manager (พร้อม defensive check)
$categories = [];
$initial_data = ['terms' => [], 'pages' => 1, 'current_page' => 1];
if (class_exists('\GovHybridTranslator\Modules\GlossaryManager')) {
    $glossary_manager = new \GovHybridTranslator\Modules\GlossaryManager();
    $glossary_manager->ensure_default_categories();
    $categories = $glossary_manager->get_categories();
    $initial_data = $glossary_manager->get_glossary_terms();
}
?>

<div id="view-glossary" class="view-section hidden space-y-6">
<div class="ght-glossary-container">
    <!-- Header -->
    <div class="ght-glossary-header">
        <div class="ght-search-bar">
            <input type="text" id="ght-glossary-search" placeholder="ค้นหาคำศัพท์... (Thai or English)" />
            <button type="button" id="ght-search-btn" class="button">
                <span class="dashicons dashicons-search"></span> ค้นหา
            </button>
            <button type="button" id="ght-clear-search-btn" class="button" style="display:none;">
                <span class="dashicons dashicons-no"></span> ล้างการค้นหา
            </button>
        </div>
        
        <div class="ght-glossary-actions">
            <select id="ght-category-filter" class="ght-filter">
                <option value="">ทุกหมวดหมู่</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo esc_attr($cat['slug']); ?>">
                        <?php echo esc_html($cat['name']); ?> (<?php echo $cat['count']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button type="button" id="ght-add-term-btn" class="button button-primary">
                <span class="dashicons dashicons-plus-alt"></span> เพิ่มคำศัพท์ใหม่
            </button>
        </div>
    </div>

    <!-- Terms Table -->
    <div class="ght-glossary-table-wrapper">
        <table class="ght-glossary-table">
            <thead>
                <tr>
                    <th class="col-thai">คำภาษาไทย</th>
                    <th class="col-english">คำภาษาอังกฤษ</th>
                    <th class="col-category">หมวดหมู่</th>
                    <th class="col-actions">จัดการ</th>
                </tr>
            </thead>
            <tbody id="ght-glossary-tbody">
                <?php if (!empty($initial_data['terms'])): ?>
                    <?php foreach ($initial_data['terms'] as $term): ?>
                        <tr data-term-id="<?php echo esc_attr($term['id']); ?>">
                            <td class="col-thai"><?php echo esc_html($term['thai_term']); ?></td>
                            <td class="col-english"><?php echo esc_html($term['english_term']); ?></td>
                            <td class="col-category">
                                <span class="ght-category-badge ght-cat-<?php echo esc_attr($term['category_slug']); ?>">
                                    <?php echo esc_html($term['category']); ?>
                                </span>
                            </td>
                            <td class="col-actions">
                                <button type="button" class="button button-small ght-edit-term" data-term-id="<?php echo esc_attr($term['id']); ?>">
                                    <span class="dashicons dashicons-edit"></span> แก้ไข
                                </button>
                                <button type="button" class="button button-small ght-delete-term" data-term-id="<?php echo esc_attr($term['id']); ?>">
                                    <span class="dashicons dashicons-trash"></span> ลบ
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr class="ght-no-terms">
                        <td colspan="4" style="text-align:center; padding: 40px;">
                            ไม่พบคำศัพท์ กรุณาเพิ่มคำศัพท์ใหม่
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="ght-pagination" id="ght-glossary-pagination">
        <?php if ($initial_data['pages'] > 1): ?>
            <button type="button" class="button ght-page-btn" data-page="1" <?php echo $initial_data['current_page'] == 1 ? 'disabled' : ''; ?>>
                « แรก
            </button>
            <button type="button" class="button ght-page-btn" data-page="<?php echo max(1, $initial_data['current_page'] - 1); ?>" <?php echo $initial_data['current_page'] == 1 ? 'disabled' : ''; ?>>
                ‹ ก่อนหน้า
            </button>
            <span class="ght-page-info">
                หน้า <?php echo $initial_data['current_page']; ?> จาก <?php echo $initial_data['pages']; ?>
                (ทั้งหมด <?php echo $initial_data['total']; ?> คำ)
            </span>
            <button type="button" class="button ght-page-btn" data-page="<?php echo min($initial_data['pages'], $initial_data['current_page'] + 1); ?>" <?php echo $initial_data['current_page'] == $initial_data['pages'] ? 'disabled' : ''; ?>>
                ถัดไป ›
            </button>
            <button type="button" class="button ght-page-btn" data-page="<?php echo $initial_data['pages']; ?>" <?php echo $initial_data['current_page'] == $initial_data['pages'] ? 'disabled' : ''; ?>>
                สุดท้าย »
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="ght-term-modal" class="ght-modal" style="display:none;">
    <div class="ght-modal-content">
        <div class="ght-modal-header">
            <h2 id="ght-modal-title">เพิ่มคำศัพท์ใหม่</h2>
            <button type="button" class="ght-modal-close">&times;</button>
        </div>
        
        <div class="ght-modal-body">
            <form id="ght-term-form">
                <input type="hidden" id="ght-term-id" value="" />
                
                <div class="ght-form-group">
                    <label for="ght-thai-term">คำภาษาไทย <span class="required">*</span></label>
                    <input type="text" id="ght-thai-term" class="widefat" required />
                </div>
                
                <div class="ght-form-group">
                    <label for="ght-english-term">คำภาษาอังกฤษ <span class="required">*</span></label>
                    <input type="text" id="ght-english-term" class="widefat" required />
                </div>
                
                <div class="ght-form-group">
                    <label for="ght-term-category">หมวดหมู่ <span class="required">*</span></label>
                    <select id="ght-term-category" class="widefat" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo esc_attr($cat['slug']); ?>">
                                <?php echo esc_html($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
        
        <div class="ght-modal-footer">
            <button type="button" class="button button-large ght-modal-close">ยกเลิก</button>
            <button type="button" id="ght-save-term-btn" class="button button-primary button-large">
                <span class="dashicons dashicons-yes"></span> บันทึก
            </button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="ght-delete-modal" class="ght-modal" style="display:none;">
    <div class="ght-modal-content ght-modal-small">
        <div class="ght-modal-header">
            <h2>ยืนยันการลบ</h2>
            <button type="button" class="ght-modal-close">&times;</button>
        </div>
        
        <div class="ght-modal-body">
            <p>คุณแน่ใจหรือไม่ว่าต้องการลบคำศัพท์นี้?</p>
            <p><strong id="ght-delete-term-name"></strong></p>
            <input type="hidden" id="ght-delete-term-id" value="" />
        </div>
        
        <div class="ght-modal-footer">
            <button type="button" class="button button-large ght-modal-close">ยกเลิก</button>
            <button type="button" id="ght-confirm-delete-btn" class="button button-primary button-large">
                <span class="dashicons dashicons-trash"></span> ลบ
            </button>
        </div>
    </div>
</div>
</div>
