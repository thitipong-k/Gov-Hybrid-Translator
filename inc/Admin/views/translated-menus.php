<!-- Tab: Menus -->
<?php
use GovHybridTranslator\Core\TranslationMeta;
?>
<div id="tab-translated-content-menus" class="tab-translated-content hidden">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="px-6 py-3 font-medium">Menu Item (TH)</th>
                    <th class="px-6 py-3 font-medium">Translated Label (EN)</th>
                    <th class="px-6 py-3 font-medium">Menu Name</th>
                    <th class="px-6 py-3 font-medium">Type</th>
                    <th class="px-6 py-3 font-medium">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                <?php 
                $has_translated_menus = false;
                if (!empty($translated_menus) && !is_wp_error($translated_menus)) : 
                    foreach ($translated_menus as $menu) : 
                        $menu_items = wp_get_nav_menu_items($menu->term_id);
                        if ($menu_items) : foreach ($menu_items as $item) : 
                            // ใช้ TranslationMeta แทน get_post_meta
                            $translated_title = TranslationMeta::get_title($item->ID, 'en');
                            if (empty($translated_title)) continue;
                            $has_translated_menus = true;
                        ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-800"><?php echo esc_html($item->title); ?></td>
                                <td class="px-6 py-4">
                                    <input type="text" 
                                        id="menu-edit-<?php echo $item->ID; ?>" 
                                        value="<?php echo esc_attr($translated_title); ?>"
                                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500 focus:border-transparent text-sm">
                                </td>
                                <td class="px-6 py-4 text-gray-500"><?php echo esc_html($menu->name); ?></td>
                                <td class="px-6 py-4 text-gray-500 text-xs"><?php echo esc_html($item->type_label); ?></td>
                                <td class="px-6 py-4">
                                    <button onclick="updateMenuTranslation(<?php echo $item->ID; ?>)" 
                                        class="text-gov-600 hover:text-gov-700 font-medium bg-gov-50 hover:bg-gov-100 px-3 py-1 rounded-md transition-colors">
                                        Update
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!$has_translated_menus) : ?>
                    <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No translated menu items found. Go to Tasks to translate.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
