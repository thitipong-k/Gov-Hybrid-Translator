<!-- Tab: Categories -->
<?php
use GovHybridTranslator\Core\TermTranslationMeta;
?>
<div id="tab-translated-content-categories" class="tab-translated-content hidden">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="px-6 py-3 font-medium">Category Name (TH)</th>
                    <th class="px-6 py-3 font-medium">Translated Name (EN)</th>
                    <th class="px-6 py-3 font-medium">Slug</th>
                    <th class="px-6 py-3 font-medium">Count</th>
                    <th class="px-6 py-3 font-medium">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                <?php 
                $has_translated_categories = false;
                if (!empty($translated_categories) && !is_wp_error($translated_categories)) : 
                    foreach ($translated_categories as $category) : 
                        // ใช้ TermTranslationMeta แทน get_term_meta
                        $translated_name = TermTranslationMeta::get_name($category->term_id, 'en');
                        if (empty($translated_name)) continue;
                        $has_translated_categories = true;
                    ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-800"><?php echo esc_html($category->name); ?></td>
                            <td class="px-6 py-4">
                                <input type="text" 
                                    id="cat-edit-<?php echo $category->term_id; ?>" 
                                    value="<?php echo esc_attr($translated_name); ?>"
                                    class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500 focus:border-transparent text-sm">
                            </td>
                            <td class="px-6 py-4 text-gray-500"><?php echo esc_html($category->slug); ?></td>
                            <td class="px-6 py-4 text-gray-500"><?php echo esc_html($category->count); ?></td>
                            <td class="px-6 py-4">
                                <button onclick="updateCategoryTranslation(<?php echo $category->term_id; ?>)" 
                                    class="text-gov-600 hover:text-gov-700 font-medium bg-gov-50 hover:bg-gov-100 px-3 py-1 rounded-md transition-colors">
                                    Update
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if (!$has_translated_categories) : ?>
                    <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No translated categories found. Go to Tasks to translate.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
