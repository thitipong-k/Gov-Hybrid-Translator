/**
 * Translated View JavaScript
 * Handles updating translations in the Translated view
 */

// Update Page Translation
function updatePageTranslation(pageId) {
    const input = document.getElementById('page-edit-' + pageId);
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
                showNotification('Page translation updated!', 'success');
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

// Update Post Translation
function updatePostTranslation(postId) {
    const input = document.getElementById('post-edit-' + postId);
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
    formData.append('page_id', postId);
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
                showNotification('Post translation updated!', 'success');
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

// Update Category Translation
function updateCategoryTranslation(termId) {
    const input = document.getElementById('cat-edit-' + termId);
    const translation = input.value;
    const btn = event.target;
    const originalText = btn.innerText;

    if (!translation.trim()) {
        showNotification('Please enter English name', 'error');
        return;
    }

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
                showNotification('Category translation updated!', 'success');
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

// Update Menu Translation
function updateMenuTranslation(menuItemId) {
    const input = document.getElementById('menu-edit-' + menuItemId);
    const translation = input.value;
    const btn = event.target;
    const originalText = btn.innerText;

    if (!translation.trim()) {
        showNotification('Please enter English label', 'error');
        return;
    }

    btn.innerText = 'Saving...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('action', 'ght_save_menu_translation');
    formData.append('nonce', ghtData.nonce);
    formData.append('menu_item_id', menuItemId);
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
                showNotification('Menu translation updated!', 'success');
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
