<?php
/**
 * Content Review Modal View
 * Used for reviewing content and adding custom terms before translation.
 * 
 * === Features ===
 * - Highlight text เพื่อเพิ่มเป็น custom term
 * - แสดง Existing Glossary Terms ที่พบใน content
 * - ปุ่มขยาย/ย่อ modal (Expand/Collapse)
 * 
 * @since 1.4.0
 * @updated 1.8.0 - เพิ่มปุ่ม Expand/Collapse
 */
if (!defined('ABSPATH')) exit;
?>

<div id="ght-content-review-modal" class="ght-modal" style="display:none;">
    <div class="ght-modal-content ght-modal-xl" id="ght-modal-container">
        <div class="ght-modal-header">
            <h2 id="ght-review-title">Review Content</h2>
            <div class="ght-modal-header-actions">
                <!-- ปุ่มขยาย/ย่อ Modal -->
                <button type="button" id="ght-modal-expand-btn" class="ght-modal-expand" title="Expand / Collapse">
                    <span class="dashicons dashicons-editor-expand"></span>
                </button>
                <button type="button" class="ght-modal-close">&times;</button>
            </div>
        </div>
        
        <div class="ght-modal-body ght-review-body">
            <div class="ght-review-layout">
                <!-- Left Column: Content Display -->
                <div class="ght-review-content-col">
                    <!-- View Tabs: สลับระหว่าง Original และ Translated -->
                    <div class="ght-content-view-tabs" style="margin-bottom: 15px; display: flex; gap: 5px;">
                        <button type="button" id="ght-view-original-btn" class="button active" style="flex: 1;">
                            🇹🇭 Original (Thai)
                        </button>
                        <button type="button" id="ght-view-translated-btn" class="button" style="flex: 1;">
                            🌐 Translated
                        </button>
                    </div>
                    
                    <div id="ght-term-editor-section">
                        <div class="ght-review-instructions">
                            <p><span class="dashicons dashicons-info"></span> Highlight text to add as a custom term.</p>
                            <div class="ght-highlight-legend">
                                <div class="ght-legend-item">
                                    <span class="ght-highlight-existing" style="padding: 2px 8px; font-size: 11px;">คำศัพท์</span>
                                    <span>= Existing Term (สีเขียว)</span>
                                </div>
                                <div class="ght-legend-item">
                                    <span class="ght-highlight-term" style="padding: 2px 8px; font-size: 11px;">คำศัพท์</span>
                                    <span>= New Term (สีเหลือง)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="ght-review-content-display" class="ght-content-display">
                        <!-- Content will be loaded here -->
                        <div class="ght-loading-placeholder">Loading content...</div>
                    </div>
                </div>
                
                <!-- Right Column: Term Management -->
                <div class="ght-review-sidebar-col">
                    <!-- Add Term Form -->
                    <div class="ght-review-form-card">
                        <h3>Add Custom Term</h3>
                        <div class="ght-form-group">
                            <label>Selected Text (Thai)</label>
                            <input type="text" id="ght-review-selected-term" readonly placeholder="Select text from content" class="widefat">
                        </div>
                        <div class="ght-form-group">
                            <label>English Translation</label>
                            <input type="text" id="ght-review-english-term" placeholder="Enter translation" class="widefat">
                        </div>
                        <div class="ght-form-group">
                            <label>Category</label>
                            <select id="ght-review-term-category" class="widefat">
                                <option value="person">Person (บุคคล)</option>
                                <option value="position">Position (ตำแหน่ง)</option>
                                <option value="unit">Unit (หน่วยงาน)</option>
                                <option value="other">Other (อื่นๆ)</option>
                            </select>
                        </div>
                        <button type="button" id="ght-review-add-term-btn" class="button button-secondary" disabled>
                            <span class="dashicons dashicons-plus"></span> Add to List
                        </button>
                    </div>

                    <!-- Added Terms List -->
                    <div class="ght-review-terms-list-card">
                        <h3>Custom Terms to Add (<span id="ght-review-term-count">0</span>)</h3>
                        <div id="ght-review-terms-list" class="ght-terms-list">
                            <!-- Terms will be added here -->
                            <div class="ght-no-terms-msg">No terms added yet.</div>
                        </div>
                    </div>

                    <!-- Existing Terms List -->
                    <div class="ght-review-terms-list-card" style="margin-top: 20px;">
                        <h3>Existing Glossary Terms (<span id="ght-review-existing-count">0</span>)</h3>
                        <div id="ght-review-existing-list" class="ght-terms-list">
                            <div class="ght-no-terms-msg">No existing terms found in content.</div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <!-- *** แปลเนื้อหา: เลือกภาษาเป้าหมายแล้วกดแปล *** -->
                    <div id="ght-translate-action-section" class="ght-review-actions">
                        <input type="hidden" id="ght-review-post-id" value="">
                        
                        <!-- เลือกภาษาเป้าหมาย -->
                        <div class="ght-form-group" style="margin-bottom: 15px;">
                            <label for="ght-review-target-lang"><strong>🌐 ภาษาเป้าหมาย:</strong></label>
                            <select id="ght-review-target-lang" class="widefat">
                                <option value="en">🇺🇸 English (อังกฤษ)</option>
                                <option value="zh">🇨🇳 Chinese (จีน)</option>
                                <option value="ja">🇯🇵 Japanese (ญี่ปุ่น)</option>
                                <option value="ko">🇰🇷 Korean (เกาหลี)</option>
                                <option value="de">🇩🇪 German (เยอรมัน)</option>
                                <option value="fr">🇫🇷 French (ฝรั่งเศส)</option>
                            </select>
                        </div>
                        
                        <button type="button" id="ght-review-translate-btn" class="button button-primary button-large button-block">
                            <span class="dashicons dashicons-translation"></span> Translate Content
                        </button>
                        
                        <button type="button" id="ght-review-delete-btn" class="button button-link-delete button-large button-block" style="margin-top: 10px;">
                            <span class="dashicons dashicons-trash"></span> Delete Translation
                        </button>
                        
                        <p class="description" style="margin-top: 10px; font-size: 11px; color: #666;">
                            * คำศัพท์เฉพาะที่เพิ่มไว้จะถูกบันทึกลง Glossary โดยอัตโนมัติ
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS สำหรับ Expand Modal -->
<style>
/* ปุ่ม Expand/Collapse */
.ght-modal-header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.ght-modal-expand {
    background: none;
    border: none;
    cursor: pointer;
    padding: 5px;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.ght-modal-expand:hover {
    background-color: rgba(0, 0, 0, 0.1);
}

.ght-modal-expand .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
}

/* Modal ขยายเต็มหน้าจอ */
.ght-modal-content.ght-modal-expanded {
    width: 98vw !important;
    max-width: 98vw !important;
    height: 95vh !important;
    max-height: 95vh !important;
    margin: 1vh auto !important;
}

.ght-modal-content.ght-modal-expanded .ght-modal-body {
    height: calc(95vh - 60px) !important;
    max-height: calc(95vh - 60px) !important;
}

/* Animation */
.ght-modal-content {
    transition: width 0.3s ease, max-width 0.3s ease, height 0.3s ease, max-height 0.3s ease;
}

/* View Tabs */
.ght-content-view-tabs .button.active {
    background: #2271b1;
    border-color: #2271b1;
    color: #fff;
}

.ght-content-view-tabs .button:not(.active) {
    background: #f0f0f1;
    border-color: #8c8f94;
    color: #1d2327;
}

.ght-content-view-tabs .button:not(.active):hover {
    background: #e0e0e0;
    border-color: #2271b1;
}
</style>

<!-- JavaScript สำหรับ Expand/Collapse -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const expandBtn = document.getElementById('ght-modal-expand-btn');
    const modalContainer = document.getElementById('ght-modal-container');
    const icon = expandBtn ? expandBtn.querySelector('.dashicons') : null;
    
    if (expandBtn && modalContainer && icon) {
        expandBtn.addEventListener('click', function() {
            // Toggle expanded class
            modalContainer.classList.toggle('ght-modal-expanded');
            
            // Toggle icon
            if (modalContainer.classList.contains('ght-modal-expanded')) {
                icon.classList.remove('dashicons-editor-expand');
                icon.classList.add('dashicons-editor-contract');
                expandBtn.title = 'Collapse';
            } else {
                icon.classList.remove('dashicons-editor-contract');
                icon.classList.add('dashicons-editor-expand');
                expandBtn.title = 'Expand';
            }
        });
    }
});
</script>
