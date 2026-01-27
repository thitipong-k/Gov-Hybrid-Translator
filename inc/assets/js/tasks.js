/**
 * Translation Tasks JavaScript
 * Handles translation actions in the Tasks view
 */

// Save Page Translation
function savePageTranslation(pageId) {
    const input = document.getElementById('post-trans-' + pageId);
    const translation = input.value;
    const btn = event.target;
    const originalText = btn.innerText;

    if (!translation.trim()) {
        showNotification('Please enter English title', 'error');
        return;
    }

    btn.innerText = 'Saving...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('action', 'ght_save_page_translation');
    formData.append('nonce', ghtData.nonce);
    formData.append('page_id', pageId);
    formData.append('translation', translation);

    fetch(ajaxurl, {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            btn.innerText = originalText;
            btn.disabled = false;
            if (data.success) {
                showNotification('Page translation saved!', 'success');
                window.location.hash = 'view=tasks&tab=pages';
                setTimeout(() => location.reload(), 500);
            } else {
                showNotification(data.data.message || 'Error saving', 'error');
            }
        })
        .catch(err => {
            btn.innerText = originalText;
            btn.disabled = false;
            showNotification('Error saving', 'error');
        });
}

// Translate Post (with AI)
function translatePost(postId) {
    const input = document.getElementById('post-trans-' + postId);
    const customTitle = input ? input.value : '';
    const btn = event.target;
    const originalText = btn.innerText;

    btn.innerText = 'Translating...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('action', 'ght_translate_post');
    formData.append('nonce', ghtData.nonce);
    formData.append('post_id', postId);
    formData.append('custom_title', customTitle);

    fetch(ajaxurl, {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                btn.innerText = 'Saved!';
                btn.disabled = false;
                showNotification('Translation created successfully!', 'success');
                window.location.hash = 'view=tasks&tab=posts';
                setTimeout(() => location.reload(), 1000);
            } else {
                btn.innerText = originalText;
                btn.disabled = false;
                showNotification(data.data.message || 'Error translating', 'error');
            }
        })
        .catch(err => {
            btn.innerText = originalText;
            btn.disabled = false;
            showNotification('Error translating', 'error');
        });
}

// Save Term (Category) Translation
function saveTermTranslation(termId) {
    const input = document.getElementById('term-trans-' + termId);
    const translation = input.value;
    const btn = event.target;
    const originalText = btn.innerText;

    btn.innerText = 'Saving...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('action', 'ght_save_term_translation');
    formData.append('nonce', ghtData.nonce);
    formData.append('term_id', termId);
    formData.append('lang', 'en');
    formData.append('translation', translation);

    fetch(ajaxurl, {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            btn.innerText = originalText;
            btn.disabled = false;
            if (data.success) {
                showNotification('Category translation saved!', 'success');
                window.location.hash = 'view=tasks&tab=categories';
                setTimeout(() => location.reload(), 500);
            } else {
                showNotification(data.data.message || 'Error saving', 'error');
            }
        })
        .catch(err => {
            btn.innerText = originalText;
            btn.disabled = false;
            showNotification('Error saving', 'error');
        });
}

// Save Menu Translation
function saveMenuTranslation(itemId) {
    const input = document.getElementById('menu-trans-' + itemId);
    const translation = input.value;
    const btn = event.target;
    const originalText = btn.innerText;

    btn.innerText = 'Saving...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('action', 'ght_save_menu_translation');
    formData.append('nonce', ghtData.nonce);
    formData.append('menu_item_id', itemId);
    formData.append('lang', 'en');
    formData.append('translation', translation);

    fetch(ajaxurl, {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            btn.innerText = originalText;
            btn.disabled = false;
            if (data.success) {
                showNotification('Menu item saved!', 'success');
                window.location.hash = 'view=tasks&tab=menus';
                setTimeout(() => location.reload(), 500);
            } else {
                showNotification(data.data.message || 'Error saving', 'error');
            }
        })
        .catch(err => {
            btn.innerText = originalText;
            btn.disabled = false;
            showNotification('Error saving', 'error');
        });
}
