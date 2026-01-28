<?php
/**
 * Translated Design Tabs View
 * 
 * แสดงรายการ Design Tab Groups ที่แปลครบแล้ว
 * 
 * @package GovHybridTranslator
 * @since 2.1.1
 */

// ดึง DesignTabs integration และ translated groups
$dt_integration = new \GovHybridTranslator\Integrations\DesignTabsIntegration();
$translated_dt_groups = $dt_integration->get_translated_groups();

// ดึง target languages
$settings_obj = isset($settings_obj) ? $settings_obj : new \GovHybridTranslator\Modules\Settings();
$target_languages = $settings_obj->get_setting('target_languages', ['en']);
?>

<!-- Tab: Design Tabs (Translated) -->
<div id="tab-translated-content-design-tabs" class="tab-translated-content hidden">
    <?php if (empty($translated_dt_groups)) : ?>
        <!-- ไม่พบ Tab Groups ที่แปลครบ -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
            <div class="text-gray-400 mb-4">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">ยังไม่มี Tab Groups ที่แปลครบ</h3>
            <p class="text-gray-500">เริ่มแปล Tab titles ได้ที่เมนู "Tasks → Design Tabs"</p>
        </div>
    <?php else : ?>
        
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">📐 Translated Design Tabs</h3>
                <p class="text-sm text-gray-500">Tab Groups ที่แปลครบแล้ว</p>
            </div>
            <div class="text-sm text-gray-500">
                <span class="font-semibold text-green-600"><?php echo count($translated_dt_groups); ?></span> groups แปลครบ
            </div>
        </div>
        
        <!-- Translated Tab Groups List -->
        <div class="space-y-4">
            <?php foreach ($translated_dt_groups as $item) : 
                $group = $item['group'];
                $tabs = $item['tabs'];
                $translations = $item['translations'];
                $translated_langs = $item['translated_langs'];
            ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    
                    <!-- Group Header -->
                    <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-green-50 to-emerald-50 cursor-pointer"
                         onclick="toggleTranslatedDTGroup(<?php echo $group->ID; ?>)">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">✅</span>
                                <div>
                                    <h4 class="font-semibold text-gray-800">
                                        <?php echo esc_html($group->post_title); ?>
                                    </h4>
                                    <p class="text-sm text-gray-500">
                                        <?php echo count($tabs); ?> tabs
                                        | Shortcode: <code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">[design_tabs id="<?php echo $group->ID; ?>"]</code>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <!-- Language Badges -->
                                <div class="flex items-center gap-1">
                                    <?php foreach ($translated_langs as $lang) : ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                            <?php echo strtoupper(esc_html($lang)); ?> ✓
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                                <span class="text-gray-400 transition-transform translated-dt-toggle-<?php echo $group->ID; ?>">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Group Content (Hidden by default) -->
                    <div class="hidden" id="translated-dt-content-<?php echo $group->ID; ?>">
                        <div class="p-4">
                            
                            <!-- Tabs Translation Table -->
                            <div class="overflow-hidden rounded-lg border border-gray-200">
                                <table class="w-full text-left text-sm">
                                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                                        <tr>
                                            <th class="px-4 py-2 font-medium">#</th>
                                            <th class="px-4 py-2 font-medium">🇹🇭 Original (Thai)</th>
                                            <?php foreach ($target_languages as $lang) : ?>
                                                <th class="px-4 py-2 font-medium"><?php echo strtoupper(esc_html($lang)); ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <?php foreach ($tabs as $index => $tab) : ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-2 text-gray-500"><?php echo $index + 1; ?></td>
                                                <td class="px-4 py-2 font-medium text-gray-800">
                                                    <?php echo esc_html($tab['title'] ?: 'Untitled'); ?>
                                                </td>
                                                <?php foreach ($target_languages as $lang) : ?>
                                                    <td class="px-4 py-2 text-gray-600">
                                                        <?php 
                                                        if (isset($translations[$lang][$index]) && !empty($translations[$lang][$index])) {
                                                            echo esc_html($translations[$lang][$index]);
                                                        } else {
                                                            echo '<span class="text-gray-400 italic">-</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="flex items-center justify-end mt-4 pt-4 border-t border-gray-100">
                                <button onclick="goToEditDesignTabs(<?php echo $group->ID; ?>)" 
                                   class="text-gov-600 hover:text-gov-700 text-sm font-medium flex items-center gap-1 bg-transparent border-0 cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    แก้ไขคำแปล
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
    <?php endif; ?>
</div>

<!-- Script moved to assets/js/admin-dashboard.js -->
