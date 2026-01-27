/**
 * Settings JavaScript
 * Handles settings save and AI connection testing
 * 
 * @package GovHybridTranslator
 * @since 1.0.0
 * @updated 1.5.0 - แก้ไขให้ส่ง settings เป็น JSON
 * @updated 2.0.0 - เพิ่ม permissions data collection
 */

// Save Settings via AJAX
function saveSettings() {
    const btn = event.target;
    const originalText = btn.innerText;
    btn.innerText = 'Saving...';
    btn.disabled = true;

    // รวบรวม settings เป็น object
    const settings = {};
    const form = document.querySelector('#settings-form');

    if (form) {
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            const name = input.name;
            if (!name) return;

            // ข้าม permissions checkboxes (จัดการแยก)
            if (input.classList.contains('ght-permission-checkbox')) return;

            if (input.type === 'checkbox') {
                // Handle checkbox arrays (e.g., placement[])
                if (name.endsWith('[]')) {
                    const arrayName = name.slice(0, -2);
                    if (!settings[arrayName]) {
                        settings[arrayName] = [];
                    }
                    if (input.checked) {
                        settings[arrayName].push(input.value);
                    }
                } else {
                    settings[name] = input.checked ? '1' : '0';
                }
            } else if (input.type === 'radio') {
                // Only add if checked
                if (input.checked) {
                    settings[name] = input.value;
                }
            } else {
                settings[name] = input.value;
            }
        });
    }

    // === รวบรวม Permissions Data (ถ้ามี) ===
    // เรียก getPermissionsData() ที่กำหนดใน settings-permissions.php
    if (typeof getPermissionsData === 'function') {
        settings.permissions = getPermissionsData();
    }

    // สร้าง FormData และใส่ settings เป็น JSON
    const formData = new FormData();
    formData.append('action', 'ght_save_settings');
    formData.append('nonce', ghtData.settingsNonce);
    formData.append('settings', JSON.stringify(settings));

    fetch(ajaxurl, {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            btn.innerText = originalText;
            btn.disabled = false;
            if (data.success) {
                showNotification('Settings saved successfully!', 'success');
            } else {
                showNotification(data.data?.message || 'Error saving settings', 'error');
            }
        })
        .catch(err => {
            btn.innerText = originalText;
            btn.disabled = false;
            console.error('Save error:', err);
            showNotification('Error saving settings', 'error');
        });
}

// Test AI Connection
function testAIConnection() {
    const btn = event.target;
    const originalText = btn.innerText;
    const apiKey = document.querySelector('[name="ai_api_key"]')?.value;
    const provider = document.querySelector('[name="ai_provider"]')?.value;

    if (!apiKey && provider !== 'simulator') {
        showNotification('Please enter API Key first', 'error');
        return;
    }

    btn.innerText = 'Testing...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('action', 'ght_test_ai_connection');
    formData.append('nonce', ghtData.settingsNonce);
    formData.append('api_key', apiKey);
    formData.append('provider', provider);

    fetch(ajaxurl, {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            btn.innerText = originalText;
            btn.disabled = false;
            if (data.success) {
                showNotification(data.data.message || 'Connection successful!', 'success');
            } else {
                showNotification(data.data.message || 'Connection failed', 'error');
            }
        })
        .catch(err => {
            btn.innerText = originalText;
            btn.disabled = false;
            showNotification('Error testing connection', 'error');
        });
}
