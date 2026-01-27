/**
 * Admin Dashboard JavaScript
 * Handles view switching and main navigation
 */

// Switch between main views (Overview, Tasks, Translated, Settings, About)
// doNotResetTabs: ถ้าเป็น true จะไม่ reset tabs (ใช้เมื่อมี tab parameter จาก URL hash)
function switchView(viewId, doNotResetTabs = false) {
    // Hide all views
    document.querySelectorAll('.view-section').forEach(el => el.classList.add('hidden'));
    const view = document.getElementById('view-' + viewId);
    if (view) view.classList.remove('hidden');

    // Update sidebar active state
    document.querySelectorAll('.sidebar-item').forEach(el => {
        el.classList.remove('active');
        el.classList.add('text-gray-600');
    });

    // Reset tabs (ยกเว้นเมื่อ doNotResetTabs = true)
    if (!doNotResetTabs) {
        if (viewId === 'tasks') switchTasksTab('pages');
        if (viewId === 'translated') switchTranslatedTab('pages');
    }
}

// Switch between Tasks tabs
function switchTasksTab(tabId) {
    document.querySelectorAll('.tab-tasks-content').forEach(el => el.classList.add('hidden'));
    const content = document.getElementById('tab-tasks-content-' + tabId);
    if (content) content.classList.remove('hidden');

    document.querySelectorAll('[id^="tab-tasks-btn-"]').forEach(el => {
        el.classList.remove('tab-active');
        el.classList.add('tab-inactive');
    });
    const activeTab = document.getElementById('tab-tasks-btn-' + tabId);
    if (activeTab) {
        activeTab.classList.remove('tab-inactive');
        activeTab.classList.add('tab-active');
    }
}

// Switch between Translated tabs
function switchTranslatedTab(tabId) {
    document.querySelectorAll('.tab-translated-content').forEach(el => el.classList.add('hidden'));
    const content = document.getElementById('tab-translated-content-' + tabId);
    if (content) content.classList.remove('hidden');

    document.querySelectorAll('[id^="tab-translated-btn-"]').forEach(el => {
        el.classList.remove('tab-active');
        el.classList.add('tab-inactive');
    });
    const activeTab = document.getElementById('tab-translated-btn-' + tabId);
    if (activeTab) {
        activeTab.classList.remove('tab-inactive');
        activeTab.classList.add('tab-active');
    }
}

// Switch between Settings tabs
function switchSettingsTab(tabId) {
    // Hide all tab contents
    document.querySelectorAll('.settings-tab-content').forEach(el => el.classList.add('hidden'));
    const content = document.getElementById('settings-content-' + tabId);
    if (content) content.classList.remove('hidden');

    // Update tab buttons
    document.querySelectorAll('[id^="settings-tab-"]').forEach(el => {
        el.classList.remove('settings-tab-active');
        el.classList.add('settings-tab-inactive');
    });
    const activeTab = document.getElementById('settings-tab-' + tabId);
    if (activeTab) {
        activeTab.classList.remove('settings-tab-inactive');
        activeTab.classList.add('settings-tab-active');
    }
}

// Show notification message
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white z-50 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'
        }`;
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.3s';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Restore view and tab from URL hash on page load
window.addEventListener('DOMContentLoaded', function () {
    const hash = window.location.hash.substring(1); // Remove #
    if (hash) {
        const params = new URLSearchParams(hash);
        const view = params.get('view');
        const tab = params.get('tab');

        if (view) {
            // ถ้ามี tab parameter ให้ส่ง doNotResetTabs = true เพื่อไม่ให้ reset tab เป็น default
            switchView(view, !!tab);

            if (tab && view === 'tasks') {
                switchTasksTab(tab);
            } else if (tab && view === 'translated') {
                switchTranslatedTab(tab);
            }
        }
    }
});
