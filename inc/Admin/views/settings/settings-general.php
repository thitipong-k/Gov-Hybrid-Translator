<!-- GENERAL SETTINGS TAB -->
<div id="settings-content-general" class="settings-tab-content">
    <div class="max-w-3xl space-y-8">
        <!-- Default Source Language -->
        <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Default Source Language</h3>
            <div class="space-y-3">
                <label class="block">
                    <span class="text-sm font-medium text-gray-700 mb-2 block">Source Language</span>
                    <select name="source_language" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500 focus:border-transparent">
                        <option value="th" <?php selected($settings['source_language'] ?? 'th', 'th'); ?>>Thai (ไทย)</option>
                        <option value="en" <?php selected($settings['source_language'] ?? 'th', 'en'); ?>>English</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">The primary language of your website content</p>
                </label>
            </div>
        </div>

        <!-- ========== Site Identity Translation ========== -->
        <!-- แปลชื่อเว็บและ Tagline สำหรับแต่ละภาษา -->
        <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl p-6 border border-blue-100">
            <h3 class="text-lg font-semibold text-gray-800 mb-2 flex items-center gap-2">
                🌐 Site Identity Translation
            </h3>
            <p class="text-sm text-gray-600 mb-4">แปลชื่อเว็บไซต์และ Tagline ที่แสดงใน Header สำหรับแต่ละภาษา</p>
            
            <?php 
            // ดึง current site title และ tagline
            $current_blogname = get_option('blogname', '');
            $current_tagline = get_option('blogdescription', '');
            
            // ภาษาเป้าหมายหลัก
            $target_langs = [
                'en' => ['flag' => '🇺🇸', 'name' => 'English'],
                'zh' => ['flag' => '🇨🇳', 'name' => 'Chinese'],
                'ja' => ['flag' => '🇯🇵', 'name' => 'Japanese'],
            ];
            ?>
            
            <!-- Current Site Identity (Thai) -->
            <div class="mb-6 p-4 bg-white rounded-lg border border-gray-200">
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-xl">🇹🇭</span>
                    <span class="font-medium text-gray-800">Thai (ต้นฉบับ)</span>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">Site Title:</span>
                        <span class="font-medium text-gray-800 ml-2"><?php echo esc_html($current_blogname); ?></span>
                    </div>
                    <div>
                        <span class="text-gray-500">Tagline:</span>
                        <span class="text-gray-700 ml-2"><?php echo esc_html($current_tagline); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Translated Site Identity -->
            <div class="space-y-4">
                <?php foreach ($target_langs as $lang_code => $lang_info) : 
                    $saved_title = get_option('ght_blogname_' . $lang_code, '');
                    $saved_tagline = get_option('ght_blogdescription_' . $lang_code, '');
                ?>
                <div class="p-4 bg-white rounded-lg border border-gray-200">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-xl"><?php echo $lang_info['flag']; ?></span>
                        <span class="font-medium text-gray-800"><?php echo esc_html($lang_info['name']); ?></span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Site Title</label>
                            <input type="text" 
                                name="ght_blogname_<?php echo esc_attr($lang_code); ?>" 
                                value="<?php echo esc_attr($saved_title); ?>"
                                placeholder="<?php echo esc_attr($current_blogname); ?>"
                                class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500 focus:border-transparent text-sm">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Tagline</label>
                            <input type="text" 
                                name="ght_blogdescription_<?php echo esc_attr($lang_code); ?>" 
                                value="<?php echo esc_attr($saved_tagline); ?>"
                                placeholder="<?php echo esc_attr($current_tagline); ?>"
                                class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500 focus:border-transparent text-sm">
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <p class="text-xs text-gray-500 mt-4">
                💡 ทิ้งว่างไว้ถ้าต้องการใช้ชื่อต้นฉบับ (ภาษาไทย)
            </p>
        </div>


        <!-- Target Languages -->
        <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Target Languages</h3>
            
            <!-- Popular Languages -->
            <div class="mb-4">
                <p class="text-sm font-medium text-gray-700 mb-2">Popular Languages</p>
                <div class="space-y-2">
                    <?php 
                    $popular_languages = [];
                    $selected_languages = isset($settings['target_languages']) ? $settings['target_languages'] : ['en'];
                    if (class_exists('\GovHybridTranslator\Core\Languages')) {
                        $popular_languages = \GovHybridTranslator\Core\Languages::get_popular_languages();
                    }
                    foreach ($popular_languages as $code => $lang) : 
                        $checked = in_array($code, $selected_languages) ? 'checked' : '';
                    ?>
                        <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" name="target_languages[]" value="<?php echo esc_attr($code); ?>" <?php echo $checked; ?> class="w-4 h-4 text-gov-600 rounded focus:ring-gov-500">
                            <span class="text-xl"><?php echo $lang['flag']; ?></span>
                            <span class="flex-1 text-sm font-medium text-gray-700"><?php echo esc_html($lang['name']); ?> (<?php echo esc_html($lang['native_name']); ?>)</span>
                            <?php if ($code === 'en') : ?>
                                <span class="text-xs text-gray-500">Primary target</span>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- All Languages -->
            <div>
                <p class="text-sm font-medium text-gray-700 mb-2">Other Languages</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <?php 
                    $all_languages = [];
                    if (class_exists('\GovHybridTranslator\Core\Languages')) {
                        $all_languages = \GovHybridTranslator\Core\Languages::get_enabled_languages();
                    }
                    foreach ($all_languages as $code => $lang) : 
                        if ($lang['popular']) continue; // Skip popular languages
                        $checked = in_array($code, $selected_languages) ? 'checked' : '';
                    ?>
                        <label class="flex items-center gap-2 p-2 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" name="target_languages[]" value="<?php echo esc_attr($code); ?>" <?php echo $checked; ?> class="w-4 h-4 text-gov-600 rounded focus:ring-gov-500">
                            <span class="text-lg"><?php echo $lang['flag']; ?></span>
                            <span class="flex-1 text-sm text-gray-700"><?php echo esc_html($lang['name']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Translation Mode -->
        <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Translation Mode</h3>
            <div class="space-y-2">
                <label class="flex items-start gap-3 p-4 border-2 <?php echo ($settings['translation_mode'] ?? 'hybrid') === 'hybrid' ? 'border-gov-500 bg-gov-50' : 'border-gray-200'; ?> rounded-lg cursor-pointer">
                    <input type="radio" name="translation_mode" value="hybrid" <?php checked($settings['translation_mode'] ?? 'hybrid', 'hybrid'); ?> class="mt-1 w-4 h-4 text-gov-600 focus:ring-gov-500">
                    <div class="flex-1">
                        <div class="font-medium text-gray-900">Hybrid (AI + Manual)</div>
                        <p class="text-sm text-gray-600 mt-1">Use AI for initial translation, then allow manual editing. Recommended for best quality.</p>
                    </div>
                </label>
                <label class="flex items-start gap-3 p-4 border <?php echo ($settings['translation_mode'] ?? 'hybrid') === 'manual' ? 'border-gov-500 bg-gov-50' : 'border-gray-200'; ?> rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="radio" name="translation_mode" value="manual" <?php checked($settings['translation_mode'] ?? 'hybrid', 'manual'); ?> class="mt-1 w-4 h-4 text-gov-600 focus:ring-gov-500">
                    <div class="flex-1">
                        <div class="font-medium text-gray-900">Manual Only</div>
                        <p class="text-sm text-gray-600 mt-1">All translations must be entered manually. No AI assistance.</p>
                    </div>
                </label>
                <label class="flex items-start gap-3 p-4 border <?php echo ($settings['translation_mode'] ?? 'hybrid') === 'ai' ? 'border-gov-500 bg-gov-50' : 'border-gray-200'; ?> rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="radio" name="translation_mode" value="ai" <?php checked($settings['translation_mode'] ?? 'hybrid', 'ai'); ?> class="mt-1 w-4 h-4 text-gov-600 focus:ring-gov-500">
                    <div class="flex-1">
                        <div class="font-medium text-gray-900">AI Only</div>
                        <p class="text-sm text-gray-600 mt-1">Fully automated AI translation. Faster but may require review.</p>
                    </div>
                </label>
            </div>
        </div>
    </div>
</div>
