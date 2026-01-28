/**
 * Frontend Visual Editor Script
 * สคริปต์จัดการหน้าจอแก้ไขคำแปล (Frontend Editor/Sidebar)
 */
jQuery(document).ready(function ($) {

    // Variables (ตัวแปร)
    const editorId = 'ght-frontend-editor';
    const overlayId = 'ght-editor-overlay';
    let isOriginalVisible = false;

    // --- 1. Init UI (เริ่มวาดหน้าจอ) ---
    function initEditor() {
        // Create Sidebar HTML (สร้างโครงสร้าง HTML)
        const editorHTML = `
            <div id="${overlayId}" class="ght-overlay"></div>
            <div id="${editorId}">
                <div class="ght-editor-header">
                    <h2>Edit Translation (${ghtEditorData.lang.toUpperCase()})</h2>
                    <button class="ght-close-btn">&times;</button>
                </div>
                <div class="ght-editor-body">
                    
                    <!-- Status -->
                    <div class="ght-form-group">
                        <label>Status</label>
                        <select id="ght-status" class="ght-form-control">
                            ${getStatusOptions()}
                        </select>
                    </div>

                    <!-- Title -->
                    <div class="ght-form-group">
                        <label>Title</label>
                        <input type="text" id="ght-title" class="ght-form-control" value="">
                    </div>

                    <!-- Content -->
                    <div class="ght-form-group">
                        <label>Content (HTML supported)</label>
                        <textarea id="ght-content" class="ght-form-control"></textarea>
                    </div>

                    <!-- Original Reference -->
                    <div class="ght-original-content">
                        <button class="ght-original-toggle">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            Show Original Content
                        </button>
                        <div class="ght-original-preview">
                            <h4>Original Title</h4>
                            <div id="ght-orig-title"></div>
                            <hr style="border: 0; border-top: 1px solid #ddd; margin: 10px 0;">
                            <h4>Original Content</h4>
                            <div id="ght-orig-content"></div>
                        </div>
                    </div>

                </div>
                <div class="ght-editor-footer">
                    <button class="ght-btn ght-btn-secondary" id="ght-cancel-btn">${ghtEditorData.i18n.cancel}</button>
                    <button class="ght-btn ght-btn-primary" id="ght-save-btn">${ghtEditorData.i18n.save}</button>
                </div>
            </div>
        `;

        $('body').append(editorHTML);

        // Fill initial data (ใส่ข้อมูลเริ่มต้นจาก Database)
        $('#ght-status').val(ghtEditorData.currentData.status);
        $('#ght-title').val(ghtEditorData.currentData.title);
        $('#ght-content').val(ghtEditorData.currentData.content);

        // Fill Original data (ใส่ข้อมูลต้นฉบับสำหรับเปรียบเทียบ)
        $('#ght-orig-title').text(ghtEditorData.originalData.title);
        $('#ght-orig-content').html(ghtEditorData.originalData.content);
    }

    // Initialize on load
    initEditor();

    // --- 2. Event Handlers (จัดการเหตุการณ์ต่างๆ) ---

    // Open Editor (เปิด Sidebar)
    $('#wp-admin-bar-ght-edit-translation a').on('click', function (e) {
        e.preventDefault();
        openEditor();
    });

    // Close Editor
    $('.ght-close-btn, #ght-cancel-btn, #ght-editor-overlay').on('click', function (e) {
        e.preventDefault();
        closeEditor();
    });

    // Toggle Original Content
    $('.ght-original-toggle').on('click', function (e) {
        e.preventDefault();
        isOriginalVisible = !isOriginalVisible;

        $(this).toggleClass('open', isOriginalVisible);
        $('.ght-original-preview').slideToggle(200);
    });

    // Save Changes (บันทึกข้อมูล)
    $('#ght-save-btn').on('click', function (e) {
        e.preventDefault();
        saveTranslation();
    });

    // --- 3. Functions (ฟังก์ชันการทำงาน) ---

    function openEditor() {
        $('#' + editorId).addClass('active');
        $('#' + overlayId).addClass('active');
        $('body').css('overflow', 'hidden'); // Prevent scrolling
    }

    function closeEditor() {
        // Confirmation if changed? (Simulate simple check for now, can be improved)
        /*
        if (!confirm(ghtEditorData.i18n.confirm_close)) {
            return;
        }
        */

        $('#' + editorId).removeClass('active');
        $('#' + overlayId).removeClass('active');
        $('body').css('overflow', ''); // Restore scrolling
    }

    function saveTranslation() {
        const btn = $('#ght-save-btn');
        const originalText = btn.text();

        btn.prop('disabled', true).text(ghtEditorData.i18n.saving);

        const data = {
            action: 'ght_save_full_translation',
            nonce: ghtEditorData.nonce,
            post_id: ghtEditorData.postId,
            lang: ghtEditorData.lang,
            status: $('#ght-status').val(),
            title: $('#ght-title').val(),
            content: $('#ght-content').val(),
            excerpt: '' // Optional for now
        };

        $.post(ghtEditorData.ajaxUrl, data)
            .done(function (response) {
                if (response.success) {
                    btn.text(ghtEditorData.i18n.success);
                    setTimeout(function () {
                        location.reload(); // Reload to see changes
                    }, 1000);
                } else {
                    alert(ghtEditorData.i18n.error + '\n' + (response.data.message || 'Unknown error'));
                    btn.prop('disabled', false).text(originalText);
                }
            })
            .fail(function () {
                alert('Connection error');
                btn.prop('disabled', false).text(originalText);
            });
    }

    // Helper: สร้างตัวเลือกสถานะตามสิทธิ์ (Capabilities)
    // - ถ้าเป็น Admin/Editor: เห็นทุกสถานะ (Draft, Reviewing, Approved, Published)
    // - ถ้าเป็น Author: เห็นแค่ Draft, Reviewing
    function getStatusOptions() {
        let options = '';
        const current = ghtEditorData.currentData.status;

        // Draft is always available
        options += `<option value="draft" ${current === 'draft' ? 'selected' : ''}>Draft (Hidden)</option>`;

        // Submit for Review (Reviewing)
        options += `<option value="reviewing" ${current === 'reviewing' ? 'selected' : ''}>Submit for Review</option>`;

        // Approved/Published only if capable
        if (ghtEditorData.canApprove) {
            options += `<option value="approved" ${current === 'approved' ? 'selected' : ''}>Approved</option>`;
            options += `<option value="published" ${current === 'published' ? 'selected' : ''}>Published</option>`;
        }

        return options;
    }

});
