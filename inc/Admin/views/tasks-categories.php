<!-- Tab: Categories -->
<?php
use GovHybridTranslator\Core\TermTranslationMeta;
?>
<div id="tab-tasks-content-categories" class="tab-tasks-content hidden">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="px-6 py-3 font-medium">Category Name</th>
                    <th class="px-6 py-3 font-medium">Slug</th>
                    <th class="px-6 py-3 font-medium">Count</th>
                    <th class="px-6 py-3 font-medium">Status</th>
                    <th class="px-6 py-3 font-medium">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                <?php if (!empty($untranslated_categories) && !is_wp_error($untranslated_categories)) : ?>
                    <?php foreach ($untranslated_categories as $category) : 
                        // ใช้ TermTranslationMeta แทน get_term_meta
                        $translated_name = TermTranslationMeta::get_name($category->term_id, 'en');
                        
                        // ข้าม categories ที่แปลแล้ว (แสดงเฉพาะที่ยังไม่แปล)
                        if (!empty($translated_name)) continue;
                    ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-800"><?php echo esc_html($category->name); ?></td>
                            <td class="px-6 py-4 text-gray-500"><?php echo esc_html($category->slug); ?></td>
                            <td class="px-6 py-4 text-gray-500"><?php echo esc_html($category->count); ?></td>
                            <td class="px-6 py-4">
                                <input type="text" 
                                    id="term-trans-<?php echo $category->term_id; ?>" 
                                    value="" 
                                    placeholder="Enter English Name"
                                    class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500 focus:border-transparent text-sm">
                            </td>
                            <td class="px-6 py-4">
                                <button onclick="saveTermTranslation(<?php echo $category->term_id; ?>)" 
                                    class="text-gov-600 hover:text-gov-700 font-medium bg-gov-50 hover:bg-gov-100 px-3 py-1 rounded-md transition-colors">
                                    Save
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No categories found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <script>
    function saveTermTranslation(termId) {
        const input = document.getElementById('term-trans-' + termId);
        const translation = input.value;
        const btn = event.target;
        const originalText = btn.innerText;
        const row = btn.closest('tr');

        if (!translation.trim()) {
            showNotification('Please enter translated name', 'error');
            return;
        }

        btn.innerText = 'Saving...';
        btn.disabled = true;

        const formData = new FormData();
        formData.append('action', 'ght_save_term_translation');
        formData.append('nonce', '<?php echo wp_create_nonce('ght_save_translation'); ?>');
        formData.append('term_id', termId);
        formData.append('lang', 'en'); // Hardcoded to EN for now as per UI
        formData.append('translation', translation);

        fetch(ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                btn.innerText = '✓ Saved';
                btn.classList.remove('bg-gov-50', 'text-gov-600');
                btn.classList.add('bg-green-50', 'text-green-600');
                showNotification('Category translation saved!', 'success');
                
                // Fade out และลบแถว
                if (row) {
                    row.style.transition = 'opacity 0.3s, transform 0.3s';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(20px)';
                    
                    setTimeout(() => {
                        row.remove();
                        
                        // อัพเดท counter ใน tab button
                        const tabBtn = document.getElementById('tab-tasks-btn-categories');
                        if (tabBtn) {
                            const match = tabBtn.innerText.match(/\((\d+)\)/);
                            if (match) {
                                const newCount = Math.max(0, parseInt(match[1]) - 1);
                                tabBtn.innerText = tabBtn.innerText.replace(/\(\d+\)/, '(' + newCount + ')');
                            }
                        }
                        
                        // ตรวจสอบว่าตารางว่างหรือยัง
                        const tableBody = document.querySelector('#tab-tasks-content-categories tbody');
                        if (tableBody && tableBody.querySelectorAll('tr').length === 0) {
                            const emptyRow = document.createElement('tr');
                            emptyRow.innerHTML = '<td colspan="5" class="px-6 py-4 text-center text-gray-500">All categories have been translated!</td>';
                            tableBody.appendChild(emptyRow);
                        }
                    }, 300);
                }
            } else {
                btn.innerText = originalText;
                btn.disabled = false;
                showNotification(data.data.message || 'Error saving', 'error');
            }
        })
        .catch(err => {
            btn.innerText = originalText;
            btn.disabled = false;
            showNotification('Error saving', 'error');
        });
    }
    </script>
</div>
