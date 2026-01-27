/**
 * Content Review JavaScript
 * Handles text selection, highlighting, and custom term management.
 */

(function ($) {
    'use strict';

    const ContentReview = {
        postId: 0,
        customTerms: [],
        sourceTab: '', // เก็บ tab ที่เปิด modal มา (post-contents หรือ page-contents)
        translatedContent: null, // เก็บเนื้อหาที่แปลแล้ว
        currentView: 'original', // 'original' หรือ 'translated'
        originalContent: null, // เก็บเนื้อหาต้นฉบับ

        init() {
            this.bindEvents();
        },

        bindEvents() {
            // Open review modal (delegated)
            $(document).on('click', '.ght-review-content-btn', (e) => {
                e.preventDefault();
                const $btn = $(e.currentTarget);
                const postId = $btn.data('post-id');

                // ตรวจจับว่าปุ่มอยู่ใน tab ไหน
                // 1. ตรวจสอบ Translated view sub-tabs ก่อน
                const $translatedTab = $btn.closest('.tab-translated-content');
                const $translatedView = $btn.closest('#view-translated');

                if ($translatedTab.length) {
                    const tabId = $translatedTab.attr('id');
                    if (tabId === 'tab-translated-content-page-contents') {
                        this.sourceTab = 'translated-page-contents';
                    } else if (tabId === 'tab-translated-content-post-contents') {
                        this.sourceTab = 'translated-post-contents';
                    } else if (tabId === 'tab-translated-content-pages') {
                        this.sourceTab = 'translated-pages';
                    } else if (tabId === 'tab-translated-content-posts') {
                        this.sourceTab = 'translated-posts';
                    } else {
                        this.sourceTab = 'translated-posts'; // default
                    }
                }
                // 2. Fallback: ถ้าอยู่ใน #view-translated แต่ไม่พบ .tab-translated-content
                else if ($translatedView.length) {
                    // ค้นหา active tab
                    const $activeTab = $('#view-translated .tab-translated-content:not(.hidden)');
                    if ($activeTab.length) {
                        const tabId = $activeTab.attr('id');
                        if (tabId && tabId.includes('page-contents')) {
                            this.sourceTab = 'translated-page-contents';
                        } else if (tabId && tabId.includes('post-contents')) {
                            this.sourceTab = 'translated-post-contents';
                        } else {
                            this.sourceTab = 'translated-posts';
                        }
                    } else {
                        this.sourceTab = 'translated-posts'; // default for translated view
                    }
                }
                // 3. ตรวจสอบ Tasks view tabs
                else {
                    const $parentTab = $btn.closest('.tab-tasks-content');
                    if ($parentTab.attr('id') === 'tab-tasks-content-post-contents') {
                        this.sourceTab = 'post-contents';
                    } else if ($parentTab.attr('id') === 'tab-tasks-content-page-contents') {
                        this.sourceTab = 'page-contents';
                    } else {
                        this.sourceTab = 'post-contents'; // default
                    }
                }

                this.openReviewModal(postId);
            });

            // Text selection
            $('#ght-review-content-display').on('mouseup', () => this.handleTextSelection());

            // Add term button
            $('#ght-review-add-term-btn').on('click', (e) => {
                e.preventDefault();
                this.addTerm();
            });

            // Delete term button (delegated)
            $(document).on('click', '.ght-term-delete', (e) => {
                const index = $(e.currentTarget).data('index');
                this.removeTerm(index);
            });

            // Translate button
            $('#ght-review-translate-btn').on('click', () => this.translateContent());

            // Delete Translation button
            $('#ght-review-delete-btn').on('click', () => this.deleteTranslation());

            // Input validation
            $('#ght-review-english-term').on('input', (e) => {
                const val = $(e.target).val().trim();
                const selected = $('#ght-review-selected-term').val().trim();
                $('#ght-review-add-term-btn').prop('disabled', !(val && selected));
            });

            // View Tab Buttons - สลับระหว่าง Original และ Translated view
            $(document).on('click', '#ght-view-original-btn', () => this.showOriginalView());
            $(document).on('click', '#ght-view-translated-btn', () => this.showTranslatedView());
        },

        openReviewModal(postId) {
            this.postId = postId;
            this.customTerms = []; // Reset terms
            this.translatedContent = null; // Reset translated content
            this.currentView = 'original'; // Reset view to original
            this.renderTermsList();
            this.renderExistingTerms([]); // Reset existing terms

            $('#ght-review-post-id').val(postId);
            $('#ght-review-title').text('Loading content...');
            $('#ght-content-review-modal').fadeIn(200);
            $('#ght-review-content-display').html('<div class="ght-loading-placeholder">Loading content...</div>');

            // Reset view tabs
            this.updateViewTabs();
            // แสดง term editor และ translate sections
            $('#ght-term-editor-section').show();
            $('#ght-translate-action-section').show();

            // Fetch content via AJAX
            $.post(ghtData.ajaxUrl, {
                action: 'ght_get_post_content',
                nonce: ghtData.nonce,
                post_id: postId
            }, (response) => {
                if (response.success) {
                    $('#ght-review-title').text('Review Content: ' + response.data.title);
                    $('#ght-review-content-display').html(response.data.content);

                    // เก็บ originalContent สำหรับใช้สลับ view
                    this.originalContent = response.data.content;

                    // Handle existing terms
                    if (response.data.found_terms && response.data.found_terms.length > 0) {
                        this.renderExistingTerms(response.data.found_terms);
                        response.data.found_terms.forEach(term => {
                            this.highlightTerm(term.thai_term, 'ght-highlight-existing');
                        });
                    } else {
                        this.renderExistingTerms([]);
                    }
                } else {
                    $('#ght-review-content-display').html('<div class="error">Failed to load content.</div>');
                }
            });
        },

        handleTextSelection() {
            const selection = window.getSelection();
            const selectedText = selection.toString().trim();

            if (selectedText.length > 0) {
                // Check if selection is within content display
                const container = document.getElementById('ght-review-content-display');
                if (container.contains(selection.anchorNode)) {
                    $('#ght-review-selected-term').val(selectedText);
                    $('#ght-review-english-term').focus();

                    // Enable add button if english term already has value
                    const englishVal = $('#ght-review-english-term').val().trim();
                    $('#ght-review-add-term-btn').prop('disabled', !englishVal);
                }
            }
        },

        addTerm() {
            const thai = $('#ght-review-selected-term').val().trim();
            const english = $('#ght-review-english-term').val().trim();
            const category = $('#ght-review-term-category').val();

            if (!thai || !english) return;

            // Add to array
            this.customTerms.push({
                thai: thai,
                english: english,
                category: category
            });

            // Highlight in content with animation
            this.highlightTerm(thai, 'ght-highlight-term ght-highlight-new');

            // Remove animation class after animation completes
            setTimeout(() => {
                $('.ght-highlight-new').removeClass('ght-highlight-new');
            }, 1000);

            // Reset form
            $('#ght-review-selected-term').val('');
            $('#ght-review-english-term').val('');
            $('#ght-review-add-term-btn').prop('disabled', true);

            this.renderTermsList();
        },

        removeTerm(index) {
            this.customTerms.splice(index, 1);
            this.renderTermsList();
            // Note: Removing highlight is complex, we'll skip for MVP
        },

        renderTermsList() {
            const $list = $('#ght-review-terms-list');
            const $count = $('#ght-review-term-count');

            $list.empty();
            $count.text(this.customTerms.length);

            if (this.customTerms.length === 0) {
                $list.html('<div class="ght-no-terms-msg">No terms added yet.</div>');
                return;
            }

            this.customTerms.forEach((term, index) => {
                $list.append(`
                    <div class="ght-term-item">
                        <div class="ght-term-info">
                            <span class="ght-term-thai">${this.escapeHtml(term.thai)}</span>
                            <span class="ght-term-eng">${this.escapeHtml(term.english)}</span>
                            <span class="ght-term-cat">${term.category}</span>
                        </div>
                        <div class="ght-term-delete" data-index="${index}" title="Remove">
                            <span class="dashicons dashicons-trash"></span>
                        </div>
                    </div>
                `);
            });
        },

        renderExistingTerms(terms) {
            const $list = $('#ght-review-existing-list');
            const $count = $('#ght-review-existing-count');

            $list.empty();
            $count.text(terms.length);

            if (terms.length === 0) {
                $list.html('<div class="ght-no-terms-msg">No existing terms found in content.</div>');
                return;
            }

            terms.forEach((term) => {
                $list.append(`
                    <div class="ght-term-item existing-term">
                        <div class="ght-term-info">
                            <span class="ght-term-thai">${this.escapeHtml(term.thai_term)}</span>
                            <span class="ght-term-eng">${this.escapeHtml(term.english_term)}</span>
                            <span class="ght-term-cat">${term.category}</span>
                        </div>
                    </div>
                `);
            });
        },

        highlightTerm(term, className = 'ght-highlight-term') {
            // Simple highlight implementation
            const $content = $('#ght-review-content-display');
            const html = $content.html();
            // Escape regex special chars
            const safeTerm = term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            // Create regex that matches term but NOT inside HTML tags
            const regex = new RegExp(`(${safeTerm})(?![^<]*>|[^<>]*<\/)(?![^<]*>)`, 'gi');

            $content.html(html.replace(regex, `<span class="${className}">$1</span>`));
        },

        /**
         * แสดง Original Thai content
         */
        showOriginalView() {
            if (this.originalContent) {
                $('#ght-review-content-display').html(this.originalContent);
            }
            this.currentView = 'original';
            this.updateViewTabs();
            // แสดง term editor สำหรับ original view
            $('#ght-term-editor-section').show();
            $('#ght-translate-action-section').show();
        },

        /**
         * แสดง Translated content
         * ดึงข้อมูลจาก AJAX ถ้ายังไม่มี
         */
        showTranslatedView() {
            const targetLang = $('#ght-review-target-lang').val() || 'en';
            this.loadTranslatedContent(targetLang);
        },

        /**
         * โหลดเนื้อหาที่แปลแล้วจาก AJAX
         * @param {string} lang - รหัสภาษาเป้าหมาย
         */
        loadTranslatedContent(lang = 'en') {
            const $content = $('#ght-review-content-display');
            $content.html('<div class="ght-loading-placeholder">กำลังโหลดเนื้อหาที่แปลแล้ว...</div>');

            $.post(ghtData.ajaxUrl, {
                action: 'ght_get_translated_content',
                nonce: ghtData.nonce,
                post_id: this.postId,
                lang: lang
            }, (response) => {
                if (response.success) {
                    this.translatedContent = response.data;
                    $content.html(response.data.content);
                    this.currentView = 'translated';
                    this.updateViewTabs();
                    // ซ่อน term editor เมื่อดู translated view
                    $('#ght-term-editor-section').hide();
                    $('#ght-translate-action-section').hide();
                } else {
                    $content.html(`
                        <div class="ght-no-translation-msg" style="padding: 20px; text-align: center; color: #666;">
                            <span class="dashicons dashicons-info" style="font-size: 32px; color: #999;"></span>
                            <p style="margin-top: 10px;">${response.data.message}</p>
                            <p style="font-size: 12px; color: #999;">กดปุ่ม "🇹🇭 Original (Thai)" เพื่อดูเนื้อหาต้นฉบับและแปลได้</p>
                        </div>
                    `);
                    this.currentView = 'translated';
                    this.updateViewTabs();
                }
            }).fail(() => {
                $content.html('<div class="ght-error-msg">เกิดข้อผิดพลาดในการโหลดเนื้อหา</div>');
            });
        },

        /**
         * อัพเดท UI ของ view tabs
         */
        updateViewTabs() {
            if (this.currentView === 'translated') {
                $('#ght-view-original-btn').removeClass('active');
                $('#ght-view-translated-btn').addClass('active');
            } else {
                $('#ght-view-original-btn').addClass('active');
                $('#ght-view-translated-btn').removeClass('active');
            }
        },

        /**
         * แปลเนื้อหาพร้อม custom terms
         * 
         * ขั้นตอน:
         * 1. รวบรวม custom terms ที่เพิ่มไว้
         * 2. เรียก AJAX translate_with_terms
         * 3. แสดงผลลัพธ์
         */
        translateContent() {
            const $btn = $('#ght-review-translate-btn');
            const targetLang = $('#ght-review-target-lang').val() || 'en';

            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> กำลังแปล...');

            $.post(ghtData.ajaxUrl, {
                action: 'ght_translate_with_terms',
                nonce: ghtData.nonce,
                post_id: this.postId,
                custom_terms: this.customTerms,
                target_lang: targetLang
            }, (response) => {
                if (response.success) {
                    // แสดง notification สำเร็จ
                    if (typeof showNotification === 'function') {
                        showNotification(response.data.message || 'แปลเนื้อหาสำเร็จ!', 'success');
                    } else {
                        alert(response.data.message || 'Translation complete!');
                    }

                    // ปิด Modal
                    $('.ght-modal').fadeOut();

                    // Redirect กลับไปยัง tab เดิมโดยใช้ URL hash
                    setTimeout(() => {
                        // กำหนด view และ tab ตาม sourceTab
                        let redirectHash = '';

                        // ตรวจสอบว่ามาจาก Translated view หรือไม่
                        if (this.sourceTab.startsWith('translated-')) {
                            // ดึงชื่อ sub-tab จาก sourceTab (เช่น 'translated-page-contents' -> 'page-contents')
                            const subTab = this.sourceTab.replace('translated-', '');
                            redirectHash = '#view=translated&tab=' + subTab;
                        } else {
                            // Tasks view
                            redirectHash = '#view=tasks&tab=' + this.sourceTab;
                        }

                        // Reload หน้าพร้อม hash เพื่อกลับไปที่ tab เดิม
                        window.location.href = window.location.pathname + window.location.search + redirectHash;
                        window.location.reload();
                    }, 1500);
                } else {
                    // แสดง error
                    if (typeof showNotification === 'function') {
                        showNotification(response.data.message || 'เกิดข้อผิดพลาด', 'error');
                    } else {
                        alert('Error: ' + (response.data.message || 'Unknown error'));
                    }
                }
            }).fail(() => {
                if (typeof showNotification === 'function') {
                    showNotification('Connection error - ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
                } else {
                    alert('Connection error');
                }
            }).always(() => {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-translation"></span> Translate Content');
            });
        },

        /**
         * ลบ Translation ที่มีอยู่
         */
        deleteTranslation() {
            if (!confirm('ต้องการลบ Translation ของเนื้อหานี้หรือไม่?')) {
                return;
            }

            const $btn = $('#ght-review-delete-btn');
            const targetLang = $('#ght-review-target-lang').val() || 'en';

            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> กำลังลบ...');

            $.post(ghtData.ajaxUrl, {
                action: 'ght_delete_translation',
                nonce: ghtData.nonce,
                post_id: this.postId,
                lang: targetLang
            }, (response) => {
                if (response.success) {
                    if (typeof showNotification === 'function') {
                        showNotification(response.data.message || 'ลบ Translation สำเร็จ!', 'success');
                    } else {
                        alert(response.data.message || 'Translation deleted!');
                    }

                    // Reset translated content
                    this.translatedContent = null;

                    // Switch back to original view
                    this.showOriginalView();
                } else {
                    if (typeof showNotification === 'function') {
                        showNotification(response.data.message || 'เกิดข้อผิดพลาด', 'error');
                    } else {
                        alert('Error: ' + (response.data.message || 'Unknown error'));
                    }
                }
            }).fail(() => {
                alert('Connection error');
            }).always(() => {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> Delete Translation');
            });
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    $(document).ready(() => {
        ContentReview.init();
    });

})(jQuery);
