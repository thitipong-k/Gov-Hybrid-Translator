/**
 * Glossary Management JavaScript
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

        bindEvents() {
            // Add term button
            $('#ght-add-term-btn').on('click', () => this.openAddModal());

            // Edit term buttons
            $(document).on('click', '.ght-edit-term', (e) => {
                const termId = $(e.currentTarget).data('term-id');
                this.openEditModal(termId);
            });

            // Delete term buttons
            $(document).on('click', '.ght-delete-term', (e) => {
                const termId = $(e.currentTarget).data('term-id');
                this.openDeleteModal(termId);
            });

            // Save term button
            $('#ght-save-term-btn').on('click', () => this.saveTerm());

            // Confirm delete button
            $('#ght-confirm-delete-btn').on('click', () => this.deleteTerm());

            // Modal close buttons
            $('.ght-modal-close').on('click', () => this.closeModals());

            // Close modal on outside click
            $('.ght-modal').on('click', (e) => {
                if ($(e.target).hasClass('ght-modal')) {
                    this.closeModals();
                }
            });

            // Search
            $('#ght-search-btn').on('click', () => this.performSearch());
            $('#ght-glossary-search').on('keypress', (e) => {
                if (e.which === 13) this.performSearch();
            });

            // Clear search
            $('#ght-clear-search-btn').on('click', () => this.clearSearch());

            // Category filter
            $('#ght-category-filter').on('change', (e) => {
                this.currentCategory = $(e.target).val();
                this.currentPage = 1;
                this.loadTerms();
            });

            // Pagination
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
            $('#ght-term-modal').fadeIn(200);
        },

        openEditModal(termId) {
            // Get term data from table row
            const $row = $(`tr[data-term-id="${termId}"]`);
            const thaiTerm = $row.find('.col-thai').text();
            const englishTerm = $row.find('.col-english').text();
            const categorySlug = $row.find('.ght-category-badge').attr('class').match(/ght-cat-(\w+)/)[1];

            $('#ght-modal-title').text('แก้ไขคำศัพท์');
            $('#ght-term-id').val(termId);
            $('#ght-thai-term').val(thaiTerm);
            $('#ght-english-term').val(englishTerm);
            $('#ght-term-category').val(categorySlug);
            $('#ght-term-modal').fadeIn(200);
        },

        openDeleteModal(termId) {
            const $row = $(`tr[data-term-id="${termId}"]`);
            const thaiTerm = $row.find('.col-thai').text();
            const englishTerm = $row.find('.col-english').text();

            $('#ght-delete-term-id').val(termId);
            $('#ght-delete-term-name').text(`${thaiTerm} (${englishTerm})`);
            $('#ght-delete-modal').fadeIn(200);
        },

        closeModals() {
            $('.ght-modal').fadeOut(200);
        },

        saveTerm() {
            const termId = $('#ght-term-id').val();
            const thaiTerm = $('#ght-thai-term').val().trim();
            const englishTerm = $('#ght-english-term').val().trim();
            const category = $('#ght-term-category').val();

            // Validation
            if (!thaiTerm || !englishTerm) {
                alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                return;
            }

            const action = termId ? 'ght_update_glossary_term' : 'ght_create_glossary_term';
            const data = {
                action: action,
                nonce: ghtData.nonce,
                thai_term: thaiTerm,
                english_term: englishTerm,
                category: category
            };

            if (termId) {
                data.term_id = termId;
            }

            $('#ght-save-term-btn').prop('disabled', true).text('กำลังบันทึก...');

            $.post(ghtData.ajaxUrl, data, (response) => {
                if (response.success) {
                    this.showNotification(response.data.message, 'success');
                    this.closeModals();
                    this.loadTerms();
                } else {
                    this.showNotification(response.data.message || 'เกิดข้อผิดพลาด', 'error');
                }
            }).fail(() => {
                this.showNotification('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            }).always(() => {
                $('#ght-save-term-btn').prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> บันทึก');
            });
        },

        deleteTerm() {
            const termId = $('#ght-delete-term-id').val();

            $('#ght-confirm-delete-btn').prop('disabled', true).text('กำลังลบ...');

            $.post(ghtData.ajaxUrl, {
                action: 'ght_delete_glossary_term',
                nonce: ghtData.nonce,
                term_id: termId
            }, (response) => {
                if (response.success) {
                    this.showNotification(response.data.message, 'success');
                    this.closeModals();
                    this.loadTerms();
                } else {
                    this.showNotification(response.data.message || 'เกิดข้อผิดพลาด', 'error');
                }
            }).fail(() => {
                this.showNotification('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            }).always(() => {
                $('#ght-confirm-delete-btn').prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> ลบ');
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

            $.post(ghtData.ajaxUrl, {
                action: 'ght_search_glossary',
                nonce: ghtData.nonce,
                query: query,
                page: this.currentPage
            }, (response) => {
                if (response.success) {
                    this.renderTerms(response.data);
                } else {
                    this.showNotification(response.data.message || 'เกิดข้อผิดพลาด', 'error');
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

            $.post(ghtData.ajaxUrl, {
                action: 'ght_get_glossary_terms',
                nonce: ghtData.nonce,
                page: this.currentPage,
                category: this.currentCategory
            }, (response) => {
                if (response.success) {
                    this.renderTerms(response.data);
                } else {
                    this.showNotification(response.data.message || 'เกิดข้อผิดพลาด', 'error');
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

            if (data.terms.length === 0) {
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

            if (data.pages > 1) {
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
                <div class="notice ${className} is-dismissible" style="margin: 10px 0;">
                    <p>${message}</p>
                </div>
            `);

            $('.ght-glossary-container').prepend($notice);

            setTimeout(() => {
                $notice.fadeOut(300, function () {
                    $(this).remove();
                });
            }, 3000);
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // Initialize on document ready
    $(document).ready(() => {
        GlossaryManager.init();
    });

})(jQuery);
