/**
 * Gov Hybrid Translator - Admin Dashboard Script
 * 
 * Handles interactions for the dashboard, tasks, and settings views.
 */
jQuery(document).ready(function ($) {

    // Global: Switch View
    // Expose to window so onclick handlers work (or refactor to event listeners)
    window.switchView = function (viewName) {
        // Hide all views
        $('.view-section').addClass('hidden');

        // Show selected view
        $('#view-' + viewName).removeClass('hidden');

        // Update sidebar
        $('.sidebar-item').removeClass('active bg-gov-50 text-gov-600 font-medium').addClass('text-gray-600');

        // Find link with matching onclick (simplistic check)
        // Better: Use data-view attribute on sidebar links
        $(`.sidebar-item[onclick*="'${viewName}'"]`).addClass('active bg-gov-50 text-gov-600 font-medium').removeClass('text-gray-600');

        // Save state
        localStorage.setItem('ght_current_view', viewName);
    };

    // Global Notification
    window.showNotification = function (message, type = 'success') {
        // Remove existing notifications to prevent stacking too many
        $('.notification-toast').remove();

        const color = type === 'success' ? 'bg-green-500' : 'bg-red-500';
        const icon = type === 'success' ? '✓' : '✕';

        const html = `
            <div class="fixed bottom-4 right-4 ${color} text-white px-6 py-3 rounded-lg shadow-lg flex items-center gap-2 transform transition-all duration-300 translate-y-20 opacity-0 z-50 notification-toast" style="z-index: 9999;">
                <span class="font-bold">${icon}</span>
                <span>${message}</span>
            </div>
        `;

        const $notification = $(html);
        $('body').append($notification);

        // Animate in
        requestAnimationFrame(() => {
            $notification.removeClass('translate-y-20 opacity-0');
        });

        // Remove after 3s
        setTimeout(() => {
            $notification.addClass('translate-y-20 opacity-0');
            setTimeout(() => $notification.remove(), 300);
        }, 3000);
    };

    // Restore state
    const savedView = localStorage.getItem('ght_current_view') || 'overview';
    if ($('#view-' + savedView).length) {
        window.switchView(savedView);
    } else {
        window.switchView('overview');
    }

    // --- Tasks View Logic ---

    window.switchTasksTab = function (tabName) {
        // Update Buttons
        $('#view-tasks nav button').removeClass('tab-active border-gov-600 text-gov-600')
            .addClass('tab-inactive border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300');
        $('#tab-tasks-btn-' + tabName).removeClass('tab-inactive border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300')
            .addClass('tab-active border-gov-600 text-gov-600');

        // Update Content
        $('.tab-tasks-content').addClass('hidden');
        $('#tab-tasks-content-' + tabName).removeClass('hidden');
    };

    // Task Actions
    window.updateButtonText = function (postId) {
        const input = $('#post-trans-' + postId);
        const btn = $('#btn-' + postId);
        if (input.length && btn.length) {
            btn.text(input.val().trim() ? 'Save' : 'Translate');
        }
    };

    window.savePageTranslation = function (pageId) {
        const input = $('#post-trans-' + pageId);
        const langSelect = $('#lang-select-page-' + pageId);
        const translation = input.val();
        const targetLang = langSelect.length ? langSelect.val() : 'en';
        const btn = $('#btn-' + pageId);
        const originalText = btn.text();
        const row = btn.closest('tr');

        if (!translation.trim()) {
            alert('Please enter translated title');
            return;
        }

        btn.text('Saving...').prop('disabled', true);

        $.post(ajaxurl, {
            action: 'ght_save_page_translation',
            nonce: ghtAdminData.nonce_save,
            page_id: pageId,
            translation: translation,
            lang: targetLang
        })
            .done(function (response) {
                if (response.success) {
                    btn.text('✓ Saved').removeClass('bg-gov-50 text-gov-600').addClass('bg-green-50 text-green-600');
                    moveRowToTranslated(row, 'page');
                } else {
                    alert(response.data.message || 'Error saving');
                    btn.text(originalText).prop('disabled', false);
                }
            })
            .fail(function () {
                alert('Request failed');
                btn.text(originalText).prop('disabled', false);
            });
    };

    window.translatePost = function (postId) {
        const input = $('#post-trans-' + postId);
        const langSelect = $('#lang-select-post-' + postId);
        const customTitle = input ? input.val() : '';
        const targetLang = langSelect.length ? langSelect.val() : 'en';
        const btn = $('#btn-' + postId);
        const originalText = btn.text();
        const row = btn.closest('tr');

        btn.text('Translating...').prop('disabled', true);

        $.post(ajaxurl, {
            action: 'ght_translate_to_language',
            nonce: ghtAdminData.nonce_translate,
            post_id: postId,
            target_lang: targetLang,
            custom_title: customTitle
        })
            .done(function (response) {
                if (response.success) {
                    btn.text('✓ Translated').removeClass('bg-gov-50 text-gov-600').addClass('bg-green-50 text-green-600');
                    moveRowToTranslated(row, 'post');
                } else {
                    alert(response.data.message || 'Error translating');
                    btn.text(originalText).prop('disabled', false);
                }
            })
            .fail(function () {
                alert('Request failed');
                btn.text(originalText).prop('disabled', false);
            });
    };

    function moveRowToTranslated(row, type) {
        row.css({ transition: 'opacity 0.3s, transform 0.3s', opacity: '0', transform: 'translateX(20px)' });

        setTimeout(() => {
            row.remove();

            // Update counter
            const tabBtn = $('#tab-tasks-btn-' + type + 's');
            if (tabBtn.length) {
                const text = tabBtn.text();
                const match = text.match(/\((\d+)\)/);
                if (match) {
                    const newCount = Math.max(0, parseInt(match[1]) - 1);
                    tabBtn.text(text.replace(/\(\d+\)/, '(' + newCount + ')'));
                }
            }

            // Check empty table
            const tableBody = $('#tab-tasks-content-' + type + 's tbody');
            if (tableBody.children('tr').length === 0) {
                tableBody.append('<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">All ' + type + 's have been translated!</td></tr>');
            }
        }, 300);
    }

    // --- Settings Tabs ---
    window.switchSettingsTab = function (tabName) {
        // Buttons
        $('#view-settings nav button').removeClass('settings-tab-active border-b-2 font-medium')
            .addClass('settings-tab-inactive border-transparent');
        $('#settings-tab-' + tabName)
            .removeClass('settings-tab-inactive border-transparent')
            .addClass('settings-tab-active border-b-2 font-medium');

        // Content
        $('.settings-tab-content').addClass('hidden');
        $('#settings-content-' + tabName).removeClass('hidden');
    };

    window.saveSettings = function () {
        const btn = $('.settings-save-btn');
        const originalText = btn.text();
        btn.text('Saving...').prop('disabled', true);

        let data = $('#settings-form').serialize();
        data += '&action=ght_save_settings&nonce=' + ghtAdminData.nonce_settings;

        $.post(ajaxurl, data)
            .done(function (response) {
                if (response.success) {
                    alert('Settings saved successfully!');
                } else {
                    alert('Error: ' + (response.data.message || 'Unknown error'));
                }
            })
            .fail(function () {
                alert('Connection failed');
            })
            .always(function () {
                btn.text(originalText).prop('disabled', false);
            });
    };

    // --- Permissions Logic ---
    window.resetPermissionsToDefaults = function () {
        if (!confirm('Reset permissions to defaults?')) return;

        const defaults = {
            'administrator': { 'ght_view_dashboard': true, 'ght_translate': true, 'ght_manage_glossary': true, 'ght_manage_settings': true, 'ght_approve_translation': true },
            'editor': { 'ght_view_dashboard': true, 'ght_translate': true, 'ght_manage_glossary': false, 'ght_manage_settings': false, 'ght_approve_translation': true }
        };

        $('.ght-permission-checkbox').each(function () {
            const role = $(this).data('role');
            const cap = $(this).data('cap');
            if (defaults[role] && defaults[role][cap] !== undefined) {
                $(this).prop('checked', defaults[role][cap]);
            } else {
                $(this).prop('checked', false);
            }
        });

        alert('Permissions reset. Please click Save Changes.');
    };

    // --- Helper: Show Notification ---
    window.showNotification = function (message, type) {
        alert(message);
    };

    // --- Content Review Modal Logic ---
    // Expand/Collapse
    const expandBtn = document.getElementById('ght-modal-expand-btn');
    const modalContainer = document.getElementById('ght-modal-container');
    const icon = expandBtn ? expandBtn.querySelector('.dashicons') : null;

    if (expandBtn && modalContainer && icon) {
        expandBtn.addEventListener('click', function () {
            modalContainer.classList.toggle('ght-modal-expanded');
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

    // --- Settings AI Translation Logic ---

    window.editApiKey = function (provider) {
        $('#ai_provider').val(provider).trigger('change');
        // Scroll
        document.getElementById('ai_provider').scrollIntoView({ behavior: 'smooth', block: 'center' });

        setTimeout(() => {
            const input = $('#field-' + provider + ' input[type="password"]');
            if (input.length) {
                input.focus().attr('placeholder', 'Enter new API Key to update...');
            }
        }, 300);
    };

    window.deleteApiKey = function (provider, name) {
        if (!confirm('Delete API Key for ' + name + '?')) return;

        $.post(ajaxurl, {
            action: 'ght_delete_api_key',
            nonce: ghtAdminData.nonce_settings,
            provider: provider
        })
            .done(function (response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (response.data?.message || 'Unknown error'));
                }
            });
    };

    window.updateProviderFields = function () {
        const provider = $('#ai_provider').val();
        $('.provider-field').addClass('hidden');
        $('.provider-field[data-provider="' + provider + '"]').removeClass('hidden');
    };

    // Listeners
    $(document).on('change', '#ai_provider', window.updateProviderFields);
    // Init on load
    if ($('#ai_provider').length) {
        window.updateProviderFields();
    }

    $(document).on('click', '#btn-test-connection', function () {
        const btn = $(this);
        const originalText = btn.text();
        const provider = $('#ai_provider').val();

        let apiKey = '';
        let extraData = {};

        switch (provider) {
            case 'google':
                apiKey = $('input[name="google_api_key"]').val();
                break;
            case 'openai':
                apiKey = $('input[name="openai_api_key"]').val();
                extraData.openai_model = $('select[name="openai_model"]').val() || 'gpt-3.5-turbo';
                break;
            case 'deepl':
                apiKey = $('input[name="deepl_api_key"]').val();
                extraData.deepl_plan = $('select[name="deepl_plan"]').val() || 'free';
                break;
            case 'azure':
                apiKey = $('input[name="azure_api_key"]').val();
                extraData.azure_region = $('select[name="azure_region"]').val() || 'southeastasia';
                break;
            case 'claude':
                apiKey = $('input[name="claude_api_key"]').val();
                extraData.claude_model = $('select[name="claude_model"]').val() || 'claude-3-sonnet-20240229';
                break;
            case 'simulator':
                alert('✅ Simulator mode - No API Key required');
                return;
        }

        if (!apiKey) {
            alert('⚠️ Please enter API Key first');
            return;
        }

        btn.text('🔄 Testing...').prop('disabled', true);

        const data = {
            action: 'ght_test_ai_connection',
            nonce: ghtAdminData.nonce_settings,
            provider: provider
        };
        data[provider + '_api_key'] = apiKey;
        Object.assign(data, extraData);

        $.post(ajaxurl, data)
            .done(function (response) {
                if (response.success) {
                    alert('✅ ' + response.data.message);
                } else {
                    alert('❌ ' + (response.data?.message || 'Connection failed'));
                }
            })
            .fail(function () {
                alert('❌ Connection error');
            })
            .always(function () {
                btn.text(originalText).prop('disabled', false);
            });
    });

    // --- Settings Advanced Logic ---

    // Logs Modal
    window.fetchLogs = function () {
        const logsContent = document.getElementById('logs-content');
        const logsInfo = document.getElementById('logs-info');

        if (!logsContent) return;

        logsContent.textContent = 'Loading...';
        logsInfo.textContent = 'Fetching logs...';

        $.post(ajaxurl, {
            action: 'ght_get_debug_logs',
            nonce: ghtAdminData.nonce_settings
        })
            .done(function (response) {
                if (response.success) {
                    logsContent.textContent = response.data.logs || 'No logs found.';
                    logsInfo.textContent = `Lines: ${response.data.line_count || 0} | Size: ${response.data.size || '0 KB'}`;
                } else {
                    logsContent.textContent = response.data?.message || 'Error loading logs';
                    logsInfo.textContent = 'Error';
                }
            })
            .fail(function () {
                logsContent.textContent = 'Connection error';
                logsInfo.textContent = 'Connection error';
            });
    };

    window.refreshLogs = function () {
        window.fetchLogs();
    };

    window.closeLogsModal = function () {
        $('#logs-modal').addClass('hidden');
    };

    window.clearDebugLogs = function (btn) {
        if (!confirm('Are you sure you want to clear all debug logs?')) return;

        const $btn = $(btn);
        const originalText = $btn.text();
        $btn.text('Clearing...').prop('disabled', true);

        $.post(ajaxurl, {
            action: 'ght_clear_debug_logs',
            nonce: ghtAdminData.nonce_settings
        })
            .done(function (response) {
                alert(response.success ? ('✅ ' + (response.data?.message || 'Logs cleared')) : ('❌ ' + (response.data?.message || 'Error')));
                if (response.success && !$('#logs-modal').hasClass('hidden')) {
                    window.fetchLogs();
                }
            })
            .always(function () {
                $btn.text(originalText).prop('disabled', false);
            });
    };

    // Event Listeners for logs
    $(document).on('click', '#btn-view-logs', function () {
        $('#logs-modal').removeClass('hidden');
        window.fetchLogs();
    });

    $(document).on('click', '#btn-clear-logs', function () {
        window.clearDebugLogs(this);
    });

    $(document).on('click', '#logs-modal', function (e) {
        if (e.target === this) window.closeLogsModal();
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && !$('#logs-modal').hasClass('hidden')) {
            window.closeLogsModal();
        }
    });

    // --- Settings Language Switcher Logic ---

    window.initRadioHandlers = function () {
        // Switcher Type
        $('input[name="switcher_type"]').on('change', function () {
            $('input[name="switcher_type"]').each(function () {
                const label = $(this).closest('label');
                if (label.length) {
                    label.removeClass('border-gov-500 bg-gov-50').addClass('border-gray-200');
                }
            });

            const selectedLabel = $(this).closest('label');
            if (selectedLabel.length) {
                selectedLabel.removeClass('border-gray-200').addClass('border-gov-500 bg-gov-50');
            }
        });

        // Floating Position
        $('input[name="floating_position"]').on('change', function () {
            $('input[name="floating_position"]').each(function () {
                const label = $(this).closest('label');
                if (label.length) {
                    label.removeClass('border-gov-500 bg-gov-50').addClass('border-gray-200');
                }
            });

            const selectedLabel = $(this).closest('label');
            if (selectedLabel.length) {
                selectedLabel.removeClass('border-gray-200').addClass('border-gov-500 bg-gov-50');
            }
            window.updateFloatingPreview();
        });
    };

    window.updateFloatingPreview = function () {
        const preview = $('#floating-preview-button');
        if (!preview.length) return;

        const position = $('input[name="floating_position"]:checked').val() || 'bottom-right';
        const marginX = $('input[name="floating_margin_x"]').val() || 20;
        const marginY = $('input[name="floating_margin_y"]').val() || 20;

        preview.css({ top: '', bottom: '', left: '', right: '' });

        if (position.includes('top')) {
            preview.css('top', marginY + 'px');
        } else {
            preview.css('bottom', marginY + 'px');
        }

        if (position.includes('left')) {
            preview.css('left', marginX + 'px');
        } else {
            preview.css('right', marginX + 'px');
        }
    };

    // Init Language Switcher logic
    if ($('#placement_floating').length) {
        window.initRadioHandlers();

        $('#placement_floating').on('change', function () {
            $('#floating-button-settings').toggle(this.checked);
        }).trigger('change');

        $('input[name="floating_margin_x"], input[name="floating_margin_y"]').on('input', window.updateFloatingPreview);
        window.updateFloatingPreview();
    }

    // --- Tasks By Category Logic ---

    window.switchCategoryTab = function (slug) {
        $('.cat-tab-content').addClass('hidden');
        $('#category-tabs-nav button').removeClass('cat-tab-active').addClass('cat-tab-inactive');
        $('#category-tabs-nav span.rounded-full').removeClass('bg-gov-600 text-white').addClass('bg-gray-200 text-gray-600');

        $('#cat-tab-content-' + slug).removeClass('hidden');
        const btn = $('#cat-tab-btn-' + slug);
        btn.removeClass('cat-tab-inactive').addClass('cat-tab-active');
        btn.find('span.rounded-full').removeClass('bg-gray-200 text-gray-600').addClass('bg-gov-600 text-white');
    };

    window.translateFromCategory = function (postId, categorySlug, btnElement) {
        const langSelect = $('#cat-lang-select-' + postId);
        const targetLang = langSelect.val() || 'en';

        // Handle button element (passed or found via event)
        let btn = btnElement ? $(btnElement) : null;
        if (!btn && window.event && window.event.target) {
            btn = $(window.event.target).closest('button');
        }
        if (!btn || !btn.length) return; // Should not happen

        const originalHtml = btn.html();
        const card = $('#post-card-' + postId);

        btn.html('<span class="dashicons dashicons-update animate-spin"></span> Translating...').prop('disabled', true);

        $.post(ajaxurl, {
            action: 'ght_translate_to_language',
            nonce: ghtAdminData.nonce_translate,
            post_id: postId,
            target_lang: targetLang
        })
            .done(function (response) {
                if (response.success) {
                    btn.html('✓ Translated').removeClass('bg-gov-600 hover:bg-gov-700').addClass('bg-green-500 hover:bg-green-600');

                    // Remove option
                    langSelect.find('option[value="' + targetLang + '"]').remove();

                    // If no options left
                    if (langSelect.children('option').length === 0) {
                        card.css({ transition: 'opacity 0.3s, transform 0.3s', opacity: '0', transform: 'translateX(20px)' });
                        setTimeout(function () {
                            card.remove();
                            // Update counters
                            const tabBtn = $('#cat-tab-btn-' + categorySlug);
                            const badge = tabBtn.find('span.rounded-full');
                            if (badge.length) {
                                badge.text(Math.max(0, parseInt(badge.text()) - 1));
                            }

                            const mainTabBtn = $('#tab-tasks-btn-by-category'); // Adjust selector as needed
                            if (mainTabBtn.length) {
                                const text = mainTabBtn.text();
                                const match = text.match(/\((\d+)\)/);
                                if (match) {
                                    mainTabBtn.text(text.replace(/\(\d+\)/, '(' + Math.max(0, parseInt(match[1]) - 1) + ')'));
                                }
                            }
                        }, 300);
                    } else {
                        setTimeout(function () {
                            btn.html(originalHtml).removeClass('bg-green-500 hover:bg-green-600').addClass('bg-gov-600 hover:bg-gov-700').prop('disabled', false);
                        }, 1500);
                    }
                } else {
                    alert(response.data?.message || 'Error translating');
                    btn.html(originalHtml).prop('disabled', false);
                }
            })
            .fail(function () {
                alert('Request failed');
                btn.html(originalHtml).prop('disabled', false);
            });
    };

    // --- Translated View Logic ---

    window.switchTranslatedTab = function (tabName) {
        // Update Buttons
        $('#view-translated nav button').removeClass('tab-active border-b-2 font-medium').addClass('tab-inactive');
        $('#tab-translated-btn-' + tabName).removeClass('tab-inactive').addClass('tab-active border-b-2 font-medium');

        // Update Content
        $('.tab-translated-content').addClass('hidden');
        $('#tab-translated-content-' + tabName).removeClass('hidden');
    };

    window.updatePageTranslation = function (pageId) {
        const input = $('#page-edit-' + pageId);
        const translation = input.val();
        // Fallback for event target if called from inline onclick
        const btn = $(window.event ? window.event.target : null).closest('button');
        if (!btn.length) return;
        const originalText = btn.text();

        if (!translation.trim()) {
            window.showNotification('Please enter English title', 'error');
            return;
        }

        btn.text('Saving...').prop('disabled', true);

        $.post(ajaxurl, {
            action: 'ght_save_page_translation',
            nonce: ghtAdminData.nonce_save,
            page_id: pageId,
            translation: translation
        })
            .done(function (response) {
                btn.text(originalText).prop('disabled', false);
                if (response.success) {
                    window.showNotification('Page translation updated!', 'success');
                } else {
                    window.showNotification(response.data?.message || 'Error saving', 'error');
                }
            })
            .fail(function () {
                btn.text(originalText).prop('disabled', false);
                window.showNotification('Error saving', 'error');
            });
    };

    window.updatePostTranslation = function (postId) {
        const input = $('#post-edit-' + postId);
        const translation = input.val();
        const btn = $(window.event ? window.event.target : null).closest('button');
        if (!btn.length) return;
        const originalText = btn.text();

        if (!translation.trim()) {
            window.showNotification('Please enter English title', 'error');
            return;
        }

        btn.text('Saving...').prop('disabled', true);

        $.post(ajaxurl, {
            action: 'ght_save_page_translation',
            nonce: ghtAdminData.nonce_save,
            page_id: postId,
            translation: translation
        })
            .done(function (response) {
                btn.text(originalText).prop('disabled', false);
                if (response.success) {
                    window.showNotification('Post translation updated!', 'success');
                } else {
                    window.showNotification(response.data?.message || 'Error saving', 'error');
                }
            })
            .fail(function () {
                btn.text(originalText).prop('disabled', false);
                window.showNotification('Error saving', 'error');
            });
    };

    window.updateCategoryTranslation = function (termId) {
        const input = $('#cat-edit-' + termId);
        const translation = input.val();
        const btn = $(window.event ? window.event.target : null).closest('button');
        if (!btn.length) return;
        const originalText = btn.text();

        if (!translation.trim()) {
            window.showNotification('Please enter English name', 'error');
            return;
        }

        btn.text('Saving...').prop('disabled', true);

        $.post(ajaxurl, {
            action: 'ght_save_term_translation',
            nonce: ghtAdminData.nonce_save,
            term_id: termId,
            lang: 'en',
            translation: translation
        })
            .done(function (response) {
                btn.text(originalText).prop('disabled', false);
                if (response.success) {
                    window.showNotification('Category translation updated!', 'success');
                } else {
                    window.showNotification(response.data?.message || 'Error saving', 'error');
                }
            })
            .fail(function () {
                btn.text(originalText).prop('disabled', false);
                window.showNotification('Error saving', 'error');
            });
    };

    window.updateMenuTranslation = function (menuItemId) {
        const input = $('#menu-edit-' + menuItemId);
        const translation = input.val();
        const btn = $(window.event ? window.event.target : null).closest('button');
        if (!btn.length) return;
        const originalText = btn.text();

        if (!translation.trim()) {
            window.showNotification('Please enter English label', 'error');
            return;
        }

        btn.text('Saving...').prop('disabled', true);

        $.post(ajaxurl, {
            action: 'ght_save_menu_translation',
            nonce: ghtAdminData.nonce_save,
            menu_item_id: menuItemId,
            lang: 'en',
            translation: translation
        })
            .done(function (response) {
                btn.text(originalText).prop('disabled', false);
                if (response.success) {
                    window.showNotification('Menu translation updated!', 'success');
                } else {
                    window.showNotification(response.data?.message || 'Error saving', 'error');
                }
            })
            .fail(function () {
                btn.text(originalText).prop('disabled', false);
                window.showNotification('Error saving', 'error');
            });
    };

    // --- Modal Logic ---

    window.escapeHtml = function (text) {
        if (!text) return '';
        return $('<div>').text(text).html();
    };

    window.formatContent = function (content) {
        if (!content) return '';
        // Strip tags but keep breaks
        let text = content.replace(/<br\s*\/?>/gi, '\n');
        text = text.replace(/<\/p>/gi, '\n\n');
        // Simple strip tags
        text = text.replace(/(<([^>]+)>)/gi, '');
        // Decode entities
        return $('<div>').html(text).text().substring(0, 2000) + (text.length > 2000 ? '...' : '');
    };

    window.showTranslationModal = function (postId) {
        const dataDiv = $('#post-data-' + postId);
        if (!dataDiv.length) {
            window.showNotification('ไม่พบข้อมูลการแปล', 'error');
            return;
        }

        const thTitle = dataDiv.data('th-title') || '';
        const enTitle = dataDiv.data('en-title') || '';
        const enContent = dataDiv.data('en-content') || '';
        const enExcerpt = dataDiv.data('en-excerpt') || '';
        const thContent = dataDiv.data('th-content') || '';

        // Remove existing modal if any
        $('#translation-modal').remove();

        const modalHtml = `
            <div id="translation-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-hidden flex flex-col">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gov-50 to-blue-50">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">🌐 ดูข้อความแปลทั้งหมด</h3>
                            <p class="text-sm text-gray-500">Post ID: ${postId}</p>
                        </div>
                        <button onclick="closeTranslationModal()" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                                <h4 class="font-medium text-orange-800 mb-2">🇹🇭 Thai Title</h4>
                                <p class="text-gray-800">${window.escapeHtml(thTitle)}</p>
                            </div>
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <h4 class="font-medium text-blue-800 mb-2">🇺🇸 English Title</h4>
                                <p class="text-gray-800">${window.escapeHtml(enTitle) || '<span class="text-gray-400 italic">ยังไม่ได้แปล</span>'}</p>
                            </div>
                        </div>

                        ${enExcerpt ? `
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <h4 class="font-medium text-green-800 mb-2">📝 English Excerpt</h4>
                            <p class="text-gray-700">${window.escapeHtml(enExcerpt)}</p>
                        </div>
                        ` : ''}

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <h4 class="font-medium text-orange-800 mb-2 flex items-center gap-2">
                                    🇹🇭 Thai Content
                                    <span class="text-xs bg-orange-100 px-2 py-1 rounded">${thContent.length} chars</span>
                                </h4>
                                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 max-h-80 overflow-y-auto">
                                    <div class="text-gray-800 text-sm whitespace-pre-wrap">${window.formatContent(thContent)}</div>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-medium text-blue-800 mb-2 flex items-center gap-2">
                                    🇺🇸 English Content
                                    <span class="text-xs bg-blue-100 px-2 py-1 rounded">${enContent.length} chars</span>
                                </h4>
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 max-h-80 overflow-y-auto">
                                    <div class="text-gray-800 text-sm whitespace-pre-wrap">${enContent ? window.formatContent(enContent) : '<span class="text-gray-400 italic">ยังไม่ได้แปล</span>'}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-3">
                        <button onclick="copyTranslation('${postId}')" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2">
                            Copy English Content
                        </button>
                        <button onclick="closeTranslationModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                            ปิด
                        </button>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);
        $(document).on('keydown.translationModal', window.handleModalEscape);
    };

    window.closeTranslationModal = function () {
        $('#translation-modal').remove();
        $(document).off('keydown.translationModal');
    };

    window.handleModalEscape = function (e) {
        if (e.key === 'Escape') {
            window.closeTranslationModal();
        }
    };

    window.copyTranslation = function (postId) {
        const dataDiv = $('#post-data-' + postId);
        if (!dataDiv.length) return;

        const enContent = dataDiv.data('en-content') || '';
        const enTitle = dataDiv.data('en-title') || '';
        const fullText = `Title: ${enTitle}\n\nContent:\n${enContent}`;

        navigator.clipboard.writeText(fullText).then(() => {
            const btn = $(window.event ? window.event.target : null).closest('button');
            const originalText = btn.html();
            btn.text('Copied!');
            setTimeout(() => btn.html(originalText), 2000);
        });
    };

    window.deleteTranslation = function (postId, lang) {
        if (!confirm('Are you sure you want to delete this translation? This cannot be undone.')) return;

        const btn = $(window.event ? window.event.target : null).closest('button');
        const originalText = btn.html();
        btn.text('Deleting...').prop('disabled', true);

        $.post(ajaxurl, {
            action: 'ght_delete_translation',
            nonce: ghtAdminData.nonce_save,
            post_id: postId,
            lang: lang
        })
            .done(function (response) {
                if (response.success) {
                    window.showNotification('Translation deleted successfully', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    window.showNotification(response.data?.message || 'Error deleting', 'error');
                    btn.html(originalText).prop('disabled', false);
                }
            })
            .fail(function () {
                window.showNotification('Error deleting translation', 'error');
                btn.html(originalText).prop('disabled', false);
            });
    };

    window.openEditModal = function (postId) {
        const dataDiv = $('#post-data-' + postId);
        if (!dataDiv.length) return;

        const enTitle = dataDiv.data('en-title') || '';
        const enContent = dataDiv.data('en-content') || '';
        const enExcerpt = dataDiv.data('en-excerpt') || '';
        const status = dataDiv.data('en-status') || 'published';

        // Remove existing
        $('#edit-translation-modal').remove();

        const modalHtml = `
            <div id="edit-translation-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-800">✏️ Edit Translation</h3>
                        <button onclick="document.getElementById('edit-translation-modal').remove()" class="text-gray-400 hover:text-gray-600">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Title (EN)</label>
                            <input type="text" id="edit-title" value="${window.escapeHtml(enTitle)}" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Excerpt (EN)</label>
                            <textarea id="edit-excerpt" rows="3" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">${window.escapeHtml(enExcerpt)}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Content (EN) - HTML Supported</label>
                            <textarea id="edit-content" rows="15" class="w-full font-mono text-sm border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">${window.escapeHtml(enContent)}</textarea>
                            <p class="text-xs text-gray-500 mt-1">You can use HTML tags like &lt;b&gt;, &lt;p&gt;, &lt;br&gt;, &lt;ul&gt;.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="edit-status" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="published" ${status === 'published' ? 'selected' : ''}>Published (Visible)</option>
                                <option value="draft" ${status === 'draft' ? 'selected' : ''}>Draft (Hidden)</option>
                            </select>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-3">
                        <button onclick="document.getElementById('edit-translation-modal').remove()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button onclick="saveFullTranslation(${postId})" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save Changes</button>
                    </div>
                </div>
            </div>
        `;
        $('body').append(modalHtml);
    };

    window.saveFullTranslation = function (postId) {
        const title = $('#edit-title').val();
        const excerpt = $('#edit-excerpt').val();
        const content = $('#edit-content').val();
        const status = $('#edit-status').val();
        const btn = $(window.event ? window.event.target : null).closest('button');
        const originalText = btn.text();

        btn.text('Saving...').prop('disabled', true);

        $.post(ajaxurl, {
            action: 'ght_save_full_translation',
            nonce: ghtAdminData.nonce_save,
            post_id: postId,
            lang: 'en',
            title: title,
            excerpt: excerpt,
            content: content,
            status: status
        })
            .done(function (response) {
                if (response.success) {
                    window.showNotification('Translation saved successfully', 'success');
                    $('#edit-translation-modal').remove();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    window.showNotification(response.data?.message || 'Error saving', 'error');
                    btn.text(originalText).prop('disabled', false);
                }
            })
            .fail(function () {
                window.showNotification('Error saving translation', 'error');
                btn.text(originalText).prop('disabled', false);
            });
    };

    // --- Comparison Filter Logic ---
    if ($('#comparison-show-pages').length) {
        $('#comparison-show-pages').on('change', function () {
            $('#comparison-pages-section').toggle(this.checked);
        });
    }
    if ($('#comparison-show-posts').length) {
        $('#comparison-show-posts').on('change', function () {
            $('#comparison-posts-section').toggle(this.checked);
        });
    }

    // --- Design Tabs Logic ---

    // Translated View
    window.toggleTranslatedDTGroup = function (groupId) {
        const content = $('#translated-dt-content-' + groupId);
        const toggle = $('.translated-dt-toggle-' + groupId);

        if (content.hasClass('hidden')) {
            content.removeClass('hidden');
            toggle.css('transform', 'rotate(180deg)');
        } else {
            content.addClass('hidden');
            toggle.css('transform', 'rotate(0)');
        }
    };

    window.goToEditDesignTabs = function (groupId) {
        if (typeof window.switchView === 'function') {
            window.switchView('tasks');
        }

        setTimeout(() => {
            if (typeof window.switchTasksTab === 'function') {
                window.switchTasksTab('design-tabs');
            }

            setTimeout(() => {
                const groupCard = $('#dt-group-card-' + groupId);
                if (groupCard.length) {
                    groupCard[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    if (typeof window.toggleDesignTabGroup === 'function') {
                        window.toggleDesignTabGroup(groupId);
                    }
                }
            }, 200);
        }, 100);
    };

    // Tasks View
    window.toggleDesignTabGroup = function (groupId) {
        const content = $('#dt-group-content-' + groupId);
        const toggle = $('.dt-group-toggle-' + groupId);

        if (content.hasClass('hidden')) {
            content.removeClass('hidden');
            toggle.css('transform', 'rotate(180deg)');
        } else {
            content.addClass('hidden');
            toggle.css('transform', 'rotate(0)');
        }
    };

    window.loadDesignTabTranslations = function (groupId) {
        const langSelect = $('#dt-lang-select-' + groupId);
        const targetLang = langSelect.val();

        $.post(ajaxurl, {
            action: 'ght_get_design_tab_translations',
            nonce: ghtAdminData.nonce_design_tabs,
            group_id: groupId,
            lang: targetLang
        }).done(function (response) {
            if (response.success && response.data.translations) {
                const translations = response.data.translations;

                const titleInputs = $(`.dt-tab-title-input[data-group-id="${groupId}"]`);
                titleInputs.each(function (index) {
                    const trans = translations[index] || '';
                    $(this).val((typeof trans === 'object') ? (trans.title || '') : trans);
                });

                const contentInputs = $(`.dt-tab-content-input[data-group-id="${groupId}"]`);
                contentInputs.each(function (index) {
                    const trans = translations[index] || '';
                    $(this).val((typeof trans === 'object') ? (trans.content || '') : '');
                });
            }
        });
    };

    window.saveDesignTabTranslations = function (groupId) {
        const langSelect = $('#dt-lang-select-' + groupId);
        const targetLang = langSelect.val();
        const saveBtn = $(window.event ? window.event.target : null).closest('button');
        const originalBtnHtml = saveBtn.html();

        saveBtn.prop('disabled', true);
        saveBtn.html('<span class="dashicons dashicons-update animate-spin"></span> กำลังบันทึก...');

        const translations = {};
        const titleInputs = $(`.dt-tab-title-input[data-group-id="${groupId}"]`);

        titleInputs.each(function (index) {
            const titleVal = $(this).val();
            const contentInput = $(`.dt-tab-content-input[data-group-id="${groupId}"][data-tab-index="${index}"]`);
            translations[index] = {
                title: titleVal,
                content: contentInput.length ? contentInput.val() : ''
            };
        });

        $.post(ajaxurl, {
            action: 'ght_save_design_tab_translations',
            nonce: ghtAdminData.nonce_design_tabs,
            group_id: groupId,
            lang: targetLang,
            translations: JSON.stringify(translations)
        }).done(function (response) {
            saveBtn.prop('disabled', false);
            if (response.success) {
                saveBtn.html('<span class="dashicons dashicons-saved"></span> บันทึกสำเร็จ!');
                saveBtn.removeClass('bg-gov-600 hover:bg-gov-700').addClass('bg-green-500 hover:bg-green-600');

                window.showNotification('บันทึกคำแปลสำเร็จ!', 'success');

                // Update badges logic
                const card = $('#dt-group-card-' + groupId);
                if (card.length) {
                    const badges = card.find('.bg-orange-100');
                    badges.each(function () {
                        const badge = $(this);
                        if (badge.text().includes(targetLang.toUpperCase()) && !badge.text().includes('✓')) {
                            badge.removeClass('bg-orange-100 text-orange-800').addClass('bg-green-100 text-green-800');
                            badge.text(badge.text() + ' ✓');
                        }
                    });

                    const langOption = langSelect.find(`option[value="${targetLang}"]`);
                    if (langOption.length && !langOption.text().includes('✓')) {
                        langOption.text(langOption.text().replace('← ยังไม่แปล', '✓'));
                    }
                }

                setTimeout(() => {
                    saveBtn.html(originalBtnHtml).removeClass('bg-green-500 hover:bg-green-600').addClass('bg-gov-600 hover:bg-gov-700');
                }, 2000);
            } else {
                saveBtn.html(originalBtnHtml);
                window.showNotification(response.data?.message || 'เกิดข้อผิดพลาด', 'error');
            }
        }).fail(function () {
            saveBtn.prop('disabled', false).html(originalBtnHtml);
            window.showNotification('เกิดข้อผิดพลาด', 'error');
        });
    };

    window.autoTranslateDesignTabs = function (groupId) {
        const langSelect = $('#dt-lang-select-' + groupId);
        const targetLang = langSelect.val();

        const titleInputs = $(`.dt-tab-title-input[data-group-id="${groupId}"]`);
        const contentInputs = $(`.dt-tab-content-input[data-group-id="${groupId}"]`);

        const items = [];

        titleInputs.each(function (index) {
            const input = $(this);
            const contentInput = $(`.dt-tab-content-input[data-group-id="${groupId}"][data-tab-index="${index}"]`);

            const originalTitle = input.attr('placeholder').replace('แปลหัวข้อ: ', '');
            let originalContent = '';

            if (contentInput.length) {
                const container = contentInput.closest('.flex.gap-4');
                const originalDiv = container.find('.font-mono');
                if (originalDiv.length) {
                    originalContent = originalDiv.text().trim();
                }
            }

            items.push({
                type: 'tab',
                title: originalTitle,
                content: originalContent
            });
        });

        titleInputs.prop('disabled', true).val('กำลังแปล...');
        contentInputs.prop('disabled', true).val('กำลังแปล...');

        $.post(ajaxurl, {
            action: 'ght_auto_translate_design_tabs',
            nonce: ghtAdminData.nonce_design_tabs,
            group_id: groupId,
            lang: targetLang,
            items: JSON.stringify(items)
        }).done(function (response) {
            titleInputs.prop('disabled', false);
            contentInputs.prop('disabled', false);

            if (response.success && response.data.translations) {
                let tabIndex = 0;
                response.data.translations.forEach(function (trans) {
                    if (trans.type !== 'group_title') {
                        if (titleInputs[tabIndex]) {
                            $(titleInputs[tabIndex]).val(typeof trans === 'object' ? (trans.title || '') : trans);
                        }
                        if (contentInputs[tabIndex]) {
                            $(contentInputs[tabIndex]).val(typeof trans === 'object' ? (trans.content || '') : '');
                        }
                        tabIndex++;
                    }
                });
                window.showNotification('แปลอัตโนมัติสำเร็จ! กดบันทึกเพื่อยืนยัน', 'success');
            } else {
                window.showNotification(response.data?.message || 'เกิดข้อผิดพลาด', 'error');
            }
        }).fail(function () {
            titleInputs.prop('disabled', false);
            contentInputs.prop('disabled', false);
            window.showNotification('เกิดข้อผิดพลาดในการแปล', 'error');
        });
    };

    // --- Settings Content SEO Logic ---
    window.toggleAutoTranslateOptions = function () {
        var checkbox = $('#auto_translate_enabled');
        var options = $('#auto_translate_options');
        if (checkbox.is(':checked')) {
            options.removeClass('hidden');
        } else {
            options.addClass('hidden');
        }
    };

    // --- Settings Language Switcher Logic ---
    window.updateFloatingPreview = function () {
        const preview = $('#floating-preview-button');
        if (!preview.length) return;

        const position = $('input[name="floating_position"]:checked').val() || 'bottom-right';
        const marginX = $('input[name="floating_margin_x"]').val() || 20;
        const marginY = $('input[name="floating_margin_y"]').val() || 20;

        preview.css({ top: '', bottom: '', left: '', right: '' });

        if (position.includes('top')) {
            preview.css('top', marginY + 'px');
        } else {
            preview.css('bottom', marginY + 'px');
        }

        if (position.includes('left')) {
            preview.css('left', marginX + 'px');
        } else {
            preview.css('right', marginX + 'px');
        }
    };

    window.initRadioHandlers = function () {
        // Switcher Type
        $('input[name="switcher_type"]').on('change', function () {
            $('input[name="switcher_type"]').each(function () {
                const label = $(this).closest('label');
                if (label.length) {
                    if ($(this).is(':checked')) {
                        label.removeClass('border-gray-200').addClass('border-gov-500 bg-gov-50');
                    } else {
                        label.removeClass('border-gov-500 bg-gov-50').addClass('border-gray-200');
                    }
                }
            });
        });

        // Floating Position
        $('input[name="floating_position"]').on('change', function () {
            $('input[name="floating_position"]').each(function () {
                const label = $(this).closest('label');
                if (label.length) {
                    if ($(this).is(':checked')) {
                        label.removeClass('border-gray-200').addClass('border-gov-500 bg-gov-50');
                    } else {
                        label.removeClass('border-gov-500 bg-gov-50').addClass('border-gray-200');
                    }
                }
            });
            window.updateFloatingPreview();
        });

        // Inputs
        $('input[name="floating_margin_x"], input[name="floating_margin_y"]').on('input', window.updateFloatingPreview);

        // Placement check
        $('input[name="placement[]"]').on('change', function () {
            if ($(this).val() === 'floating') {
                if ($(this).is(':checked')) {
                    $('#floating-button-settings').slideDown();
                } else {
                    $('#floating-button-settings').slideUp();
                }
            }
        });

        // Init state
        window.updateFloatingPreview();
        const placementFloating = $('input[name="placement[]"][value="floating"]');
        if (placementFloating.length && !placementFloating.is(':checked')) {
            $('#floating-button-settings').hide();
        }
    };

    // Call init
    window.initRadioHandlers();

});
