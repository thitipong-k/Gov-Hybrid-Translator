<!-- Tab: Menus -->
<?php
use GovHybridTranslator\Core\TranslationMeta;
?>
<div id="tab-tasks-content-menus" class="tab-tasks-content hidden">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="px-6 py-3 font-medium">Menu Name</th>
                    <th class="px-6 py-3 font-medium">Locations</th>
                    <th class="px-6 py-3 font-medium">Items</th>
                    <th class="px-6 py-3 font-medium">Status</th>
                    <th class="px-6 py-3 font-medium">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                <?php if (!empty($untranslated_menus) && !is_wp_error($untranslated_menus)) : ?>
                    <?php foreach ($untranslated_menus as $menu) : 
                        $menu_items = wp_get_nav_menu_items($menu->term_id);
                    ?>
                        <tr class="bg-gray-50">
                            <td colspan="5" class="px-6 py-3 font-bold text-gray-700">
                                <?php echo esc_html($menu->name); ?>
                            </td>
                        </tr>
                        <?php if ($menu_items) : foreach ($menu_items as $item) : 
                            // ใช้ TranslationMeta แทน get_post_meta
                            $translated_title = TranslationMeta::get_title($item->ID, 'en');
                            
                            // ข้าม items ที่แปลแล้ว (แสดงเฉพาะ items ที่ยังไม่แปล)
                            if (!empty($translated_title)) continue;
                        ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 pl-10 font-medium text-gray-800">
                                    <?php echo str_repeat('- ', $item->menu_item_parent ? 1 : 0) . esc_html($item->title); ?>
                                </td>
                                <td class="px-6 py-4 text-gray-500 text-xs"><?php echo esc_html($item->type_label); ?></td>
                                <td class="px-6 py-4 text-gray-500">-</td>
                                <td class="px-6 py-4">
                                    <input type="text" 
                                        id="menu-trans-<?php echo $item->ID; ?>" 
                                        value="" 
                                        placeholder="Enter English Label"
                                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500 focus:border-transparent text-sm">
                                </td>
                                <td class="px-6 py-4">
                                    <button onclick="saveMenuTranslation(<?php echo $item->ID; ?>)" 
                                        class="text-gov-600 hover:text-gov-700 font-medium bg-gov-50 hover:bg-gov-100 px-3 py-1 rounded-md transition-colors">
                                        Save
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No menus found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <script>
    function saveMenuTranslation(itemId) {
        const input = document.getElementById('menu-trans-' + itemId);
        const translation = input.value;
        const btn = event.target;
        const originalText = btn.innerText;
        const row = btn.closest('tr');

        if (!translation.trim()) {
            showNotification('Please enter translated label', 'error');
            return;
        }

        btn.innerText = 'Saving...';
        btn.disabled = true;

        const formData = new FormData();
        formData.append('action', 'ght_save_menu_translation');
        formData.append('nonce', '<?php echo wp_create_nonce('ght_save_translation'); ?>');
        formData.append('menu_item_id', itemId);
        formData.append('lang', 'en'); // Hardcoded to EN for now
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
                showNotification('Menu item saved!', 'success');
                
                // Fade out และลบแถว
                if (row) {
                    row.style.transition = 'opacity 0.3s, transform 0.3s';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(20px)';
                    
                    setTimeout(() => {
                        row.remove();
                        
                        // อัพเดท counter ใน tab button
                        const tabBtn = document.getElementById('tab-tasks-btn-menus');
                        if (tabBtn) {
                            const match = tabBtn.innerText.match(/\((\d+)\)/);
                            if (match) {
                                const newCount = Math.max(0, parseInt(match[1]) - 1);
                                tabBtn.innerText = tabBtn.innerText.replace(/\(\d+\)/, '(' + newCount + ')');
                            }
                        }
                        
                        // ตรวจสอบว่าตารางว่างหรือยัง
                        const tableBody = document.querySelector('#tab-tasks-content-menus tbody');
                        if (tableBody && tableBody.querySelectorAll('tr:not(.bg-gray-50)').length === 0) {
                            const emptyRow = document.createElement('tr');
                            emptyRow.innerHTML = '<td colspan="5" class="px-6 py-4 text-center text-gray-500">All menus have been translated!</td>';
                            tableBody.innerHTML = '';
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
