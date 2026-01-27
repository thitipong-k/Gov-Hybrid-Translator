<?php
/**
 * Settings Advanced Tab View
 * 
 * แสดง settings ขั้นสูง:
 * - Performance (caching)
 * - Security (access restriction, API encryption, audit logging)
 * - Debug Mode (View/Clear logs)
 * - Danger Zone (Reset settings, Delete translations)
 * 
 * @package GovHybridTranslator
 * @since 1.5.0
 * @modified 2.1.0 - เพิ่ม View Logs modal และ AJAX handlers
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;
?>
<!-- ADVANCED SETTINGS TAB -->
<div id="settings-content-advanced" class="settings-tab-content hidden">
    <div class="max-w-3xl space-y-8">
        <!-- Performance -->
        <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Performance</h3>
            <div class="space-y-4">
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="enable_cache" value="1" <?php checked($settings['enable_cache'] ?? true, true); ?> class="w-4 h-4 text-gov-600 rounded">
                    <span class="text-sm text-gray-700">Enable translation caching</span>
                </label>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cache duration (hours)</label>
                    <input type="number" name="cache_duration" value="<?php echo esc_attr($settings['cache_duration'] ?? 24); ?>" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500">
                </div>
            </div>
        </div>

        <!-- Security -->
        <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Security</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Restrict access to</label>
                    <select name="restrict_access" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500">
                        <option value="admin" <?php selected($settings['restrict_access'] ?? 'editor', 'admin'); ?>>Administrators only</option>
                        <option value="editor" <?php selected($settings['restrict_access'] ?? 'editor', 'editor'); ?>>Editors and above</option>
                        <option value="author" <?php selected($settings['restrict_access'] ?? 'editor', 'author'); ?>>Authors and above</option>
                    </select>
                </div>
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="api_encryption" value="1" <?php checked($settings['api_encryption'] ?? true, true); ?> class="w-4 h-4 text-gov-600 rounded">
                    <span class="text-sm text-gray-700">Enable API key encryption</span>
                </label>
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="audit_log" value="1" <?php checked($settings['audit_log'] ?? true, true); ?> class="w-4 h-4 text-gov-600 rounded">
                    <span class="text-sm text-gray-700">Enable audit logging</span>
                </label>
            </div>
        </div>

        <!-- Debug Mode -->
        <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Debug Mode</h3>
            <div class="space-y-4">
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="debug_mode" value="1" <?php checked($settings['debug_mode'] ?? false, true); ?> class="w-4 h-4 text-gov-600 rounded">
                    <span class="text-sm text-gray-700">Enable debug logging</span>
                </label>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Log level</label>
                    <select name="log_level" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500">
                        <option value="errors" <?php selected($settings['log_level'] ?? 'warnings', 'errors'); ?>>Errors only</option>
                        <option value="warnings" <?php selected($settings['log_level'] ?? 'warnings', 'warnings'); ?>>Warnings and errors</option>
                        <option value="all" <?php selected($settings['log_level'] ?? 'warnings', 'all'); ?>>All events</option>
                    </select>
                </div>
                <div class="flex gap-3">
                    <button type="button" id="btn-view-logs" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium">
                        📋 View Logs
                    </button>
                    <button type="button" id="btn-clear-logs" class="bg-red-100 hover:bg-red-200 text-red-700 px-4 py-2 rounded-lg text-sm font-medium">
                        🗑️ Clear Logs
                    </button>
                </div>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="border-t border-red-200 pt-8">
            <h3 class="text-lg font-semibold text-red-600 mb-4">Danger Zone</h3>
            <div class="bg-red-50 border border-red-200 rounded-lg p-6 space-y-4">
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Reset All Settings</h4>
                    <p class="text-sm text-gray-600 mb-3">This will reset all plugin settings to default values.</p>
                    <button type="button" id="btn-reset-settings" class="bg-white hover:bg-red-50 text-red-600 border border-red-300 px-4 py-2 rounded-lg text-sm font-medium">
                        Reset Settings
                    </button>
                </div>
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Delete All Translations</h4>
                    <p class="text-sm text-gray-600 mb-3">Permanently delete all translated content. This cannot be undone.</p>
                    <button type="button" id="btn-delete-translations" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                        Delete All Translations
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Logs Modal -->
<div id="logs-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[80vh] flex flex-col m-4">
        <div class="flex items-center justify-between p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">📋 Debug Logs</h3>
            <button onclick="closeLogsModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="flex-1 overflow-auto p-4">
            <pre id="logs-content" class="bg-gray-900 text-green-400 p-4 rounded-lg text-sm font-mono whitespace-pre-wrap overflow-x-auto min-h-[300px]">Loading...</pre>
        </div>
        <div class="flex justify-between items-center p-4 border-t border-gray-200">
            <span id="logs-info" class="text-sm text-gray-500">-</span>
            <div class="flex gap-2">
                <button onclick="refreshLogs()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium">
                    🔄 Refresh
                </button>
                <button onclick="closeLogsModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// === Global Functions ===
function closeLogsModal() {
    document.getElementById('logs-modal').classList.add('hidden');
}

function fetchLogs() {
    const logsContent = document.getElementById('logs-content');
    const logsInfo = document.getElementById('logs-info');
    
    logsContent.textContent = 'Loading...';
    logsInfo.textContent = 'Fetching logs...';
    
    const formData = new FormData();
    formData.append('action', 'ght_get_debug_logs');
    formData.append('nonce', '<?php echo wp_create_nonce('ght_save_settings'); ?>');
    
    fetch(ajaxurl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            logsContent.textContent = data.data.logs || 'No logs found.';
            logsInfo.textContent = `Lines: ${data.data.line_count || 0} | Size: ${data.data.size || '0 KB'}`;
        } else {
            logsContent.textContent = data.data?.message || 'Error loading logs';
            logsInfo.textContent = 'Error';
        }
    })
    .catch(err => {
        logsContent.textContent = 'Error: ' + err.message;
        logsInfo.textContent = 'Connection error';
    });
}

function refreshLogs() {
    fetchLogs();
}

function clearDebugLogs(btn) {
    if (!confirm('Are you sure you want to clear all debug logs?')) {
        return;
    }
    
    const originalText = btn.innerText;
    btn.innerText = 'Clearing...';
    btn.disabled = true;
    
    const formData = new FormData();
    formData.append('action', 'ght_clear_debug_logs');
    formData.append('nonce', '<?php echo wp_create_nonce('ght_save_settings'); ?>');
    
    fetch(ajaxurl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        btn.innerText = originalText;
        btn.disabled = false;
        
        if (data.success) {
            alert('✅ ' + (data.data?.message || 'Logs cleared successfully'));
        } else {
            alert('❌ ' + (data.data?.message || 'Error clearing logs'));
        }
    })
    .catch(err => {
        btn.innerText = originalText;
        btn.disabled = false;
        alert('❌ Error: ' + err.message);
    });
}

// === Event Listeners (wait for DOM) ===
document.addEventListener('DOMContentLoaded', function() {
    // View Logs button
    const viewLogsBtn = document.getElementById('btn-view-logs');
    if (viewLogsBtn) {
        viewLogsBtn.addEventListener('click', function() {
            document.getElementById('logs-modal').classList.remove('hidden');
            fetchLogs();
        });
    }

    // Clear Logs button
    const clearLogsBtn = document.getElementById('btn-clear-logs');
    if (clearLogsBtn) {
        clearLogsBtn.addEventListener('click', function() {
            clearDebugLogs(this);
        });
    }

    // Close modal on backdrop click
    const logsModal = document.getElementById('logs-modal');
    if (logsModal) {
        logsModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeLogsModal();
            }
        });
    }
});

// Close modal on ESC key (global)
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('logs-modal');
        if (modal && !modal.classList.contains('hidden')) {
            closeLogsModal();
        }
    }
});
</script>
