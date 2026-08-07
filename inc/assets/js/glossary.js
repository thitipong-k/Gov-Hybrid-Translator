/**
 * สคริปต์จัดการระบบคำศัพท์เฉพาะ (Glossary)
 */

(function ($) {
    'use strict';

    const GlossaryManager = {
        currentPage: 1,
        currentCategory: '',
        searchQuery: '',
        isSearching: false,

        init() {
            this.bindEvents();
        },

        getNonce() {
            if (typeof ghtData !== 'undefined' && ghtData.nonce) return ghtData.nonce;
            if (typeof ghtAdminData !== 'undefined' && ghtAdminData.nonce_save) return ghtAdminData.nonce_save;
            return '';
        },

        getAjaxUrl() {
            if (typeof ghtData !== 'undefined' && ghtData.ajaxUrl) return ghtData.ajaxUrl;
            if (typeof ghtAdminData !== 'undefined' && ghtAdminData.ajaxUrl) return ghtAdminData.ajaxUrl;
            if (typeof ajaxurl !== 'undefined') return ajaxurl;
            return '/wp-admin/admin-ajax.php';
        },

        bindEvents() {
            // ป้องกันการส่งฟอร์มเมื่อกด Enter
            $('#ght-term-form').on('submit', (e) => {
                e.preventDefault();
                this.saveTerm();
            });

            // ปุ่มเพิ่มคำศัพท์
            $('#ght-add-term-btn').on('click', () => this.openAddModal());

            // ปุ่มแก้ไขคำศัพท์
            $(document).on('click', '.ght-edit-term', (e) => {
                const termId = $(e.currentTarget).data('term-id');
                this.openEditModal(termId);
            });

            // ปุ่มลบคำศัพท์
            $(document).on('click', '.ght-delete-term', (e) => {
                const termId = $(e.currentTarget).data('term-id');
                this.openDeleteModal(termId);
            });

            // ปุ่มบันทึกคำศัพท์
            $('#ght-save-term-btn').on('click', () => this.saveTerm());

            // ปุ่มยืนยันการลบ
            $('#ght-confirm-delete-btn').on('click', () => this.deleteTerm());

            // ปุ่มปิดกล่องข้อความ (Modal)
            $('.ght-modal-close').on('click', () => this.closeModals());

            // ปิดกล่องข้อความเมื่อคลิกพื้นที่ด้านนอก
            $('.ght-modal').on('click', (e) => {
                if ($(e.target).hasClass('ght-modal')) {
                    this.closeModals();
                }
            });

            // การค้นหา
            $('#ght-search-btn').on('click', () => this.performSearch());
            $('#ght-glossary-search').on('keypress', (e) => {
                if (e.which === 13) this.performSearch();
            });

            // ล้างการค้นหา
            $('#ght-clear-search-btn').on('click', () => this.clearSearch());

            // ตัวกรองหมวดหมู่
            $('#ght-category-filter').on('change', (e) => {
                this.currentCategory = $(e.target).val();
                this.currentPage = 1;
                this.loadTerms();
            });

            // แบ่งหน้า (Pagination)
            $(document).on('click', '.ght-page-btn', (e) => {
                const page = parseInt($(e.currentTarget).data('page'));
                this.currentPage = page;
                if (this.isSearching) {
                    this.performSearch();
                } else {
                    this.loadTerms();
                }
            });
        },

        openAddModal() {
            $('#ght-modal-title').text('เพิ่มคำศัพท์ใหม่');
            $('#ght-term-id').val('');
            $('#ght-thai-term').val('');
            $('#ght-english-term').val('');
            $('#ght-term-category').val('other');
            $('#ght-term-modal').addClass('active').hide().fadeIn(200);
        },

        openEditModal(termId) {
            const $row = $(`tr[data-term-id="${termId}"]`);
            const thaiTerm = $row.find('.col-thai').text().trim();
            const englishTerm = $row.find('.col-english').text().trim();
            
            let categorySlug = 'other';
            const badge = $row.find('.ght-category-badge');
            if (badge.length && badge.attr('class')) {
                const match = badge.attr('class').match(/ght-cat-([a-zA-Z0-9_-]+)/);
                if (match) categorySlug = match[1];
            }

            $('#ght-modal-title').text('แก้ไขคำศัพท์');
            $('#ght-term-id').val(termId);
            $('#ght-thai-term').val(thaiTerm);
            $('#ght-english-term').val(englishTerm);
            $('#ght-term-category').val(categorySlug);
            $('#ght-term-modal').addClass('active').hide().fadeIn(200);
        },

        openDeleteModal(termId) {
            const $row = $(`tr[data-term-id="${termId}"]`);
            const thaiTerm = $row.find('.col-thai').text().trim();
            const englishTerm = $row.find('.col-english').text().trim();

            $('#ght-delete-term-id').val(termId);
            $('#ght-delete-term-name').text(`${thaiTerm} (${englishTerm})`);
            $('#ght-delete-modal').addClass('active').hide().fadeIn(200);
        },

        closeModals() {
            $('.ght-modal').fadeOut(150, function() {
                $(this).removeClass('active');
            });
            $('#ght-term-id').val('');
            $('#ght-thai-term').val('');
            $('#ght-english-term').val('');
        },

        saveTerm() {
            const termId = $('#ght-term-id').val();
            const thaiTerm = $('#ght-thai-term').val().trim();
            const englishTerm = $('#ght-english-term').val().trim();
            const category = $('#ght-term-category').val();

            if (!thaiTerm || !englishTerm) {
                alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                return;
            }

            const action = termId ? 'ght_update_glossary_term' : 'ght_create_glossary_term';
            const data = {
                action: action,
                nonce: this.getNonce(),
                thai_term: thaiTerm,
                english_term: englishTerm,
                category: category
            };

            if (termId) {
                data.term_id = termId;
            }

            const $btn = $('#ght-save-term-btn');
            $btn.prop('disabled', true).text('กำลังบันทึก...');

            $.post(this.getAjaxUrl(), data, (response) => {
                if (response && response.success) {
                    const message = (response.data && response.data.message) ? response.data.message : 'บันทึกคำศัพท์เรียบร้อยแล้ว';
                    this.showNotification(message, 'success');
                    this.closeModals();
                    this.loadTerms();
                } else {
                    const message = (response && response.data && response.data.message) ? response.data.message : 'เกิดข้อผิดพลาดในการบันทึกข้อมูล';
                    this.showNotification(message, 'error');
                }
            }).fail((xhr, status, error) => {
                this.showNotification('เกิดข้อผิดพลาดในการเชื่อมต่อ (' + (error || status) + ')', 'error');
            }).always(() => {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> บันทึก');
            });
        },

        deleteTerm() {
            const termId = $('#ght-delete-term-id').val();
            const $btn = $('#ght-confirm-delete-btn');

            $btn.prop('disabled', true).text('กำลังลบ...');

            $.post(this.getAjaxUrl(), {
                action: 'ght_delete_glossary_term',
                nonce: this.getNonce(),
                term_id: termId
            }, (response) => {
                if (response && response.success) {
                    const message = (response.data && response.data.message) ? response.data.message : 'ลบคำศัพท์เรียบร้อยแล้ว';
                    this.showNotification(message, 'success');
                    this.closeModals();
                    this.loadTerms();
                } else {
                    const message = (response && response.data && response.data.message) ? response.data.message : 'เกิดข้อผิดพลาดในการลบ';
                    this.showNotification(message, 'error');
                }
            }).fail((xhr, status, error) => {
                this.showNotification('เกิดข้อผิดพลาดในการเชื่อมต่อ (' + (error || status) + ')', 'error');
            }).always(() => {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> ลบ');
            });
        },

        performSearch() {
            const query = $('#ght-glossary-search').val().trim();

            if (!query) {
                alert('กรุณากรอกคำค้นหา');
                return;
            }

            this.searchQuery = query;
            this.isSearching = true;
            this.currentPage = 1;
            $('#ght-clear-search-btn').show();

            $('.ght-glossary-table-wrapper').addClass('ght-loading');

            $.post(this.getAjaxUrl(), {
                action: 'ght_search_glossary',
                nonce: this.getNonce(),
                query: query,
                page: this.currentPage
            }, (response) => {
                if (response && response.success) {
                    this.renderTerms(response.data);
                } else {
                    const message = (response && response.data && response.data.message) ? response.data.message : 'เกิดข้อผิดพลาดในการค้นหา';
                    this.showNotification(message, 'error');
                }
            }).fail(() => {
                this.showNotification('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            }).always(() => {
                $('.ght-glossary-table-wrapper').removeClass('ght-loading');
            });
        },

        clearSearch() {
            $('#ght-glossary-search').val('');
            $('#ght-clear-search-btn').hide();
            this.searchQuery = '';
            this.isSearching = false;
            this.currentPage = 1;
            this.loadTerms();
        },

        loadTerms() {
            $('.ght-glossary-table-wrapper').addClass('ght-loading');

            $.post(this.getAjaxUrl(), {
                action: 'ght_get_glossary_terms',
                nonce: this.getNonce(),
                page: this.currentPage,
                category: this.currentCategory
            }, (response) => {
                if (response && response.success) {
                    this.renderTerms(response.data);
                } else {
                    const message = (response && response.data && response.data.message) ? response.data.message : 'เกิดข้อผิดพลาดในการโหลดข้อมูล';
                    this.showNotification(message, 'error');
                }
            }).fail(() => {
                this.showNotification('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            }).always(() => {
                $('.ght-glossary-table-wrapper').removeClass('ght-loading');
            });
        },

        renderTerms(data) {
            const $tbody = $('#ght-glossary-tbody');
            $tbody.empty();

            if (!data || !data.terms || data.terms.length === 0) {
                $tbody.append(`
                    <tr class="ght-no-terms">
                        <td colspan="4" style="text-align:center; padding: 40px;">
                            ไม่พบคำศัพท์
                        </td>
                    </tr>
                `);
            } else {
                data.terms.forEach(term => {
                    $tbody.append(`
                        <tr data-term-id="${term.id}">
                            <td class="col-thai">${this.escapeHtml(term.thai_term)}</td>
                            <td class="col-english">${this.escapeHtml(term.english_term)}</td>
                            <td class="col-category">
                                <span class="ght-category-badge ght-cat-${term.category_slug}">
                                    ${this.escapeHtml(term.category)}
                                </span>
                            </td>
                            <td class="col-actions">
                                <button type="button" class="button button-small ght-edit-term" data-term-id="${term.id}">
                                    <span class="dashicons dashicons-edit"></span> แก้ไข
                                </button>
                                <button type="button" class="button button-small ght-delete-term" data-term-id="${term.id}">
                                    <span class="dashicons dashicons-trash"></span> ลบ
                                </button>
                            </td>
                        </tr>
                    `);
                });
            }

            this.renderPagination(data);
        },

        renderPagination(data) {
            const $pagination = $('#ght-glossary-pagination');
            $pagination.empty();

            if (data && data.pages > 1) {
                $pagination.append(`
                    <button type="button" class="button ght-page-btn" data-page="1" ${data.current_page == 1 ? 'disabled' : ''}>
                        « แรก
                    </button>
                    <button type="button" class="button ght-page-btn" data-page="${Math.max(1, data.current_page - 1)}" ${data.current_page == 1 ? 'disabled' : ''}>
                        ‹ ก่อนหน้า
                    </button>
                    <span class="ght-page-info">
                        หน้า ${data.current_page} จาก ${data.pages}
                        (ทั้งหมด ${data.total} คำ)
                    </span>
                    <button type="button" class="button ght-page-btn" data-page="${Math.min(data.pages, data.current_page + 1)}" ${data.current_page == data.pages ? 'disabled' : ''}>
                        ถัดไป ›
                    </button>
                    <button type="button" class="button ght-page-btn" data-page="${data.pages}" ${data.current_page == data.pages ? 'disabled' : ''}>
                        สุดท้าย »
                    </button>
                `);
            }
        },

        showNotification(message, type = 'success') {
            const className = type === 'success' ? 'notice-success' : 'notice-error';
            const $notice = $(`
                <div class="notice ${className} is-dismissible" style="margin: 10px 0; z-index: 99999; position: relative;">
                    <p>${message}</p>
                </div>
            `);

            $('.ght-glossary-container').prepend($notice);

            setTimeout(() => {
                $notice.fadeOut(300, function () {
                    $(this).remove();
                });
            }, 3500);
        },

        escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    $(document).ready(() => {
        GlossaryManager.init();
    });

})(jQuery);
