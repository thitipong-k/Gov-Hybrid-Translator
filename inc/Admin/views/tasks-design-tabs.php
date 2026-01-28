<?php
/**
 * Tasks Design Tabs View
 * 
 * แสดงรายการ Tab Groups ที่ยังแปลไม่ครบ
 * ให้ผู้ใช้แปล Tab titles แต่ละอัน
 * 
 * @package GovHybridTranslator
 * @since 2.1.1
 */

use GovHybridTranslator\Core\Languages;

// ดึง DesignTabs integration
$dt_integration = new \GovHybridTranslator\Integrations\DesignTabsIntegration();
$all_tab_groups = $dt_integration->get_all_tab_groups();
$untranslated_groups = $dt_integration->get_untranslated_groups();

// ดึง target languages สำหรับ dropdown
$settings_obj = isset($settings_obj) ? $settings_obj : new \GovHybridTranslator\Modules\Settings();
$target_languages = $settings_obj->get_setting('target_languages', ['en']);

// สร้าง array ข้อมูลภาษาสำหรับ dropdown
$language_options = [];
foreach ($target_languages as $lang_code) {
    $lang_info = Languages::get_language($lang_code);
    if ($lang_info) {
        $language_options[$lang_code] = [
            'code' => $lang_code,
            'name' => $lang_info['name'],
            'native' => $lang_info['native'] ?? $lang_info['name'],
            'flag' => $lang_info['flag'] ?? ''
        ];
    }
}
?>

<!-- Tab: Design Tabs -->
<div id="tab-tasks-content-design-tabs" class="tab-tasks-content hidden">
    
    <?php if (empty($all_tab_groups)) : ?>
        <!-- ไม่พบ Tab Groups -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
            <div class="text-gray-400 mb-4">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">ไม่พบ Tab Groups</h3>
            <p class="text-gray-500">ยังไม่มี Design Tabs Tab Groups บนเว็บไซต์</p>
        </div>
    <?php else : ?>
    
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">📐 Design Tabs Translation</h3>
                <p class="text-sm text-gray-500">แปล Tab titles สำหรับ Design Tabs plugin</p>
            </div>
            <div class="text-sm text-gray-500">
                <span class="font-semibold"><?php echo count($all_tab_groups); ?></span> Tab Groups
                | <span class="text-orange-600 font-semibold"><?php echo count($untranslated_groups); ?></span> ยังแปลไม่ครบ
            </div>
        </div>
        
        <!-- Tab Groups List -->
        <div class="space-y-4">
            <?php foreach ($all_tab_groups as $item) : 
                $group = $item['group'];
                $tabs = $item['tabs'];
                $translated_langs = $item['translated_langs'];
                $translations = $item['translations'];
                $missing_langs = $item['missing_langs'];
            ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" 
                     id="dt-group-card-<?php echo $group->ID; ?>">
                    
                    <!-- Group Header -->
                    <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-purple-50 cursor-pointer"
                         onclick="toggleDesignTabGroup(<?php echo $group->ID; ?>)">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">📐</span>
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
                                <!-- Translation Status -->
                                <div class="flex items-center gap-1">
                                    <?php foreach ($target_languages as $lang) : ?>
                                        <?php if (in_array($lang, $translated_langs)) : ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                <?php echo esc_html($language_options[$lang]['flag'] ?? ''); ?> 
                                                <?php echo strtoupper(esc_html($lang)); ?> ✓
                                            </span>
                                        <?php else : ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800">
                                                <?php echo esc_html($language_options[$lang]['flag'] ?? ''); ?> 
                                                <?php echo strtoupper(esc_html($lang)); ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                                <span class="text-gray-400 transition-transform dt-group-toggle-<?php echo $group->ID; ?>">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Group Content (Hidden by default) -->
                    <div class="hidden" id="dt-group-content-<?php echo $group->ID; ?>">
                        <div class="p-4">
                            
                            <!-- Language Selector -->
                            <div class="flex items-center gap-4 mb-4 pb-4 border-b border-gray-100">
                                <label class="text-sm font-medium text-gray-700">เลือกภาษา:</label>
                                <select id="dt-lang-select-<?php echo $group->ID; ?>" 
                                        class="p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500 focus:border-transparent"
                                        onchange="loadDesignTabTranslations(<?php echo $group->ID; ?>)">
                                    <?php foreach ($target_languages as $lang) : ?>
                                        <option value="<?php echo esc_attr($lang); ?>"
                                            <?php echo in_array($lang, $missing_langs) ? 'class="text-orange-600"' : ''; ?>>
                                            <?php echo esc_html(($language_options[$lang]['flag'] ?? '') . ' ' . ($language_options[$lang]['name'] ?? strtoupper($lang))); ?>
                                            <?php echo in_array($lang, $translated_langs) ? '✓' : '← ยังไม่แปล'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            

                            <!-- Tabs Translation Form -->
                            <div class="space-y-3" id="dt-tabs-form-<?php echo $group->ID; ?>">
                                <?php foreach ($tabs as $index => $tab) : ?>
                                    <div class="flex items-start gap-4 p-3 bg-gray-50 rounded-lg">
                                        <!-- Tab Index -->
                                        <div class="flex-shrink-0 w-8 h-8 bg-gov-100 text-gov-600 rounded-full flex items-center justify-center font-semibold text-sm">
                                            <?php echo $index + 1; ?>
                                        </div>
                                        
                                        <!-- Tab Inputs Container -->
                                        <div class="flex-1 space-y-3">
                                            <!-- Title Section -->
                                            <div class="flex gap-4">
                                                <!-- Original Title -->
                                                <div class="flex-1">
                                                    <label class="block text-xs text-gray-500 mb-1">หัวข้อ (ต้นฉบับ)</label>
                                                    <div class="text-sm font-medium text-gray-800 p-2 bg-white rounded border border-gray-200">
                                                        <?php if (!empty($tab['icon'])) : ?>
                                                            <span class="dashicons dashicons-<?php echo esc_attr($tab['icon']); ?>" style="font-size: 14px; width: 14px; height: 14px; margin-right: 5px;"></span>
                                                        <?php endif; ?>
                                                        <?php echo esc_html($tab['title'] ?: 'Untitled Tab'); ?>
                                                    </div>
                                                </div>
                                                
                                                <!-- Translated Title Input -->
                                                <div class="flex-1">
                                                    <label class="block text-xs text-gray-500 mb-1">แปลหัวข้อ</label>
                                                    <input type="text" 
                                                        class="dt-tab-title-input w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500 focus:border-transparent text-sm"
                                                        data-group-id="<?php echo $group->ID; ?>"
                                                        data-tab-index="<?php echo $index; ?>"
                                                        value="<?php 
                                                            $first_lang = $target_languages[0] ?? 'en';
                                                            $trans = $translations[$first_lang][$index] ?? '';
                                                            echo esc_attr(is_array($trans) ? ($trans['title'] ?? '') : $trans); 
                                                        ?>"
                                                        placeholder="แปลหัวข้อ: <?php echo esc_attr($tab['title']); ?>">
                                                </div>
                                            </div>

                                            <!-- Content Section (If exists) -->
                                            <?php if (isset($tab['content'])) : ?>
                                                <div class="flex gap-4">
                                                    <!-- Original Content -->
                                                    <div class="flex-1">
                                                        <label class="block text-xs text-gray-500 mb-1">เนื้อหา (ต้นฉบับ)</label>
                                                        <div class="text-xs text-gray-600 p-2 bg-white rounded border border-gray-200 font-mono max-h-32 overflow-y-auto whitespace-pre-wrap">
                                                            <?php echo esc_html($tab['content']); ?>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Translated Content Area -->
                                                    <div class="flex-1">
                                                        <label class="block text-xs text-gray-500 mb-1">แปลเนื้อหา (HTML/Shortcodes allowed)</label>
                                                        <textarea class="dt-tab-content-input w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500 focus:border-transparent text-sm font-mono"
                                                                rows="4"
                                                                data-group-id="<?php echo $group->ID; ?>"
                                                                data-tab-index="<?php echo $index; ?>"
                                                                placeholder="แปลเนื้อหา..."><?php 
                                                                    $first_lang = $target_languages[0] ?? 'en';
                                                                    $trans = $translations[$first_lang][$index] ?? '';
                                                                    echo esc_textarea(is_array($trans) ? ($trans['content'] ?? '') : ''); 
                                                                ?></textarea>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100">
                                <button type="button"
                                        onclick="autoTranslateDesignTabs(<?php echo $group->ID; ?>)"
                                        class="text-gov-600 hover:text-gov-700 text-sm font-medium flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                    Auto-Translate (AI)
                                </button>
                                
                                <button type="button"
                                        onclick="saveDesignTabTranslations(<?php echo $group->ID; ?>)"
                                        class="bg-gov-600 hover:bg-gov-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    บันทึกคำแปล
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
