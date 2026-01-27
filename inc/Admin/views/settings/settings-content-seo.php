<!-- CONTENT & SEO SETTINGS TAB -->
<div id="settings-content-content" class="settings-tab-content hidden">
    <div class="max-w-3xl space-y-8">
        <!-- Auto-translate Content Types -->
        <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Auto-translate Content Types</h3>
            <div class="space-y-2">
                <?php
                $auto_translate_types = $settings['auto_translate_types'] ?? ['pages', 'posts'];
                ?>
                <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                    <input type="checkbox" name="auto_translate_types[]" value="pages" <?php checked(in_array('pages', $auto_translate_types), true); ?> class="w-4 h-4 text-gov-600 rounded">
                    <span class="text-sm text-gray-700">Pages</span>
                </label>
                <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                    <input type="checkbox" name="auto_translate_types[]" value="posts" <?php checked(in_array('posts', $auto_translate_types), true); ?> class="w-4 h-4 text-gov-600 rounded">
                    <span class="text-sm text-gray-700">Posts</span>
                </label>
                <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                    <input type="checkbox" name="auto_translate_types[]" value="categories" <?php checked(in_array('categories', $auto_translate_types), true); ?> class="w-4 h-4 text-gov-600 rounded">
                    <span class="text-sm text-gray-700">Categories</span>
                </label>
                <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                    <input type="checkbox" name="auto_translate_types[]" value="tags" <?php checked(in_array('tags', $auto_translate_types), true); ?> class="w-4 h-4 text-gov-600 rounded">
                    <span class="text-sm text-gray-700">Tags</span>
                </label>
            </div>
        </div>

        <!-- URL Structure -->
        <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">URL Structure</h3>
            <div class="space-y-2">
                <label class="flex items-start gap-3 p-4 border <?php echo ($settings['url_structure'] ?? 'subdomain') === 'subdirectory' ? 'border-gov-500 bg-gov-50' : 'border-gray-200'; ?> rounded-lg hover:bg-gray-50">
                    <input type="radio" name="url_structure" value="subdirectory" <?php checked($settings['url_structure'] ?? 'subdomain', 'subdirectory'); ?> class="mt-1 w-4 h-4 text-gov-600">
                    <div class="flex-1">
                        <div class="font-medium text-gray-900">Subdirectory</div>
                        <p class="text-sm text-gray-600 mt-1">example.go.th/en/page-name</p>
                    </div>
                </label>
                <label class="flex items-start gap-3 p-4 border <?php echo ($settings['url_structure'] ?? 'subdomain') === 'subdomain' ? 'border-gov-500 bg-gov-50' : 'border-gray-200'; ?> rounded-lg">
                    <input type="radio" name="url_structure" value="subdomain" <?php checked($settings['url_structure'] ?? 'subdomain', 'subdomain'); ?> class="mt-1 w-4 h-4 text-gov-600">
                    <div class="flex-1">
                        <div class="font-medium text-gray-900">Subdomain</div>
                        <p class="text-sm text-gray-600 mt-1">en.example.go.th/page-name</p>
                    </div>
                </label>
                <label class="flex items-start gap-3 p-4 border <?php echo ($settings['url_structure'] ?? 'subdomain') === 'parameter' ? 'border-gov-500 bg-gov-50' : 'border-gray-200'; ?> rounded-lg hover:bg-gray-50">
                    <input type="radio" name="url_structure" value="parameter" <?php checked($settings['url_structure'] ?? 'subdomain', 'parameter'); ?> class="mt-1 w-4 h-4 text-gov-600">
                    <div class="flex-1">
                        <div class="font-medium text-gray-900">Parameter</div>
                        <p class="text-sm text-gray-600 mt-1">example.go.th/page-name?lang=en</p>
                    </div>
                </label>
            </div>
        </div>

        <!-- SEO Options -->
        <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">SEO Options</h3>
            <div class="space-y-3">
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="seo_hreflang" value="1" <?php checked($settings['seo_hreflang'] ?? true, true); ?> class="w-4 h-4 text-gov-600 rounded">
                    <span class="text-sm text-gray-700">Add hreflang tags</span>
                </label>
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="seo_canonical" value="1" <?php checked($settings['seo_canonical'] ?? true, true); ?> class="w-4 h-4 text-gov-600 rounded">
                    <span class="text-sm text-gray-700">Generate canonical URLs</span>
                </label>
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="seo_sitemap" value="1" <?php checked($settings['seo_sitemap'] ?? true, true); ?> class="w-4 h-4 text-gov-600 rounded">
                    <span class="text-sm text-gray-700">Include in sitemap</span>
                </label>
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="auto_translate_slugs" value="1" <?php checked($settings['auto_translate_slugs'] ?? true, true); ?> class="w-4 h-4 text-gov-600 rounded">
                    <span class="text-sm text-gray-700">Auto-translate slugs</span>
                </label>
            </div>
        </div>

        <!-- ===================================================================
             Auto-Translate on Publish Section
             ส่วนตั้งค่าการแปลอัตโนมัติเมื่อ Publish post/page
             =================================================================== -->
        <div class="mt-8 p-6 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-200">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                🚀 Auto-Translate on Publish
                <span class="text-xs font-normal text-blue-600 bg-blue-100 px-2 py-1 rounded">NEW</span>
            </h3>
            <p class="text-sm text-gray-600 mb-4">
                แปลอัตโนมัติเมื่อ Publish post/page ใหม่ ประหยัดเวลาไม่ต้องกดแปลด้วยตนเอง
            </p>

            <?php
            // โหลด Settings สำหรับ Auto-Translate
            $auto_settings = get_option('ght_auto_translate_settings', [
                'enabled' => false,
                'target_languages' => ['en'],
                'post_types' => ['post', 'page'],
                'first_publish_only' => true,
            ]);

            // ดึงรายการภาษาที่รองรับ
            $available_languages = [
                'en' => 'English (EN)',
                'zh' => '中文 (ZH)',
                'ja' => '日本語 (JA)',
                'ko' => '한국어 (KO)',
                'vi' => 'Tiếng Việt (VI)',
                'my' => 'မြန်မာ (MY)',
            ];

            // ดึงรายการ Post Types
            $available_post_types = get_post_types(['public' => true], 'objects');
            ?>

            <div class="space-y-4">
                <!-- เปิด/ปิด Feature -->
                <label class="flex items-center gap-3 p-3 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" 
                           name="auto_translate_enabled" 
                           id="auto_translate_enabled"
                           value="1" 
                           <?php checked($auto_settings['enabled'] ?? false, true); ?> 
                           class="w-5 h-5 text-blue-600 rounded"
                           onchange="toggleAutoTranslateOptions()">
                    <div>
                        <span class="font-medium text-gray-900">เปิดใช้งาน Auto-translate on Publish</span>
                        <p class="text-xs text-gray-500">แปลอัตโนมัติทันทีเมื่อกด Publish</p>
                    </div>
                </label>

                <!-- ตัวเลือกเพิ่มเติม (ซ่อน/แสดงตาม toggle) -->
                <div id="auto_translate_options" class="space-y-4 ml-4 <?php echo empty($auto_settings['enabled']) ? 'hidden' : ''; ?>">
                    
                    <!-- เลือกภาษาเป้าหมาย -->
                    <div class="p-4 bg-white border border-gray-200 rounded-lg">
                        <h4 class="font-medium text-gray-800 mb-3">🌐 ภาษาเป้าหมาย</h4>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                            <?php foreach ($available_languages as $code => $name): ?>
                            <label class="flex items-center gap-2 p-2 hover:bg-gray-50 rounded cursor-pointer">
                                <input type="checkbox" 
                                       name="auto_translate_languages[]" 
                                       value="<?php echo esc_attr($code); ?>"
                                       <?php checked(in_array($code, $auto_settings['target_languages'] ?? ['en']), true); ?>
                                       class="w-4 h-4 text-blue-600 rounded">
                                <span class="text-sm text-gray-700"><?php echo esc_html($name); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- เลือกประเภทเนื้อหา -->
                    <div class="p-4 bg-white border border-gray-200 rounded-lg">
                        <h4 class="font-medium text-gray-800 mb-3">📄 ประเภทเนื้อหา</h4>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                            <?php foreach ($available_post_types as $type): 
                                if ($type->name === 'attachment') continue;
                            ?>
                            <label class="flex items-center gap-2 p-2 hover:bg-gray-50 rounded cursor-pointer">
                                <input type="checkbox" 
                                       name="auto_translate_post_types[]" 
                                       value="<?php echo esc_attr($type->name); ?>"
                                       <?php checked(in_array($type->name, $auto_settings['post_types'] ?? ['post', 'page']), true); ?>
                                       class="w-4 h-4 text-blue-600 rounded">
                                <span class="text-sm text-gray-700"><?php echo esc_html($type->label); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- ตัวเลือกเพิ่มเติม -->
                    <div class="p-4 bg-white border border-gray-200 rounded-lg">
                        <h4 class="font-medium text-gray-800 mb-3">⚙️ ตัวเลือกเพิ่มเติม</h4>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" 
                                   name="auto_translate_first_only" 
                                   value="1"
                                   <?php checked($auto_settings['first_publish_only'] ?? true, true); ?>
                                   class="w-4 h-4 text-blue-600 rounded">
                            <div>
                                <span class="text-sm text-gray-700">แปลเฉพาะ Publish ครั้งแรก</span>
                                <p class="text-xs text-gray-500">ไม่แปลซ้ำเมื่อ Update post</p>
                            </div>
                        </label>
                    </div>

                    <!-- คำเตือน API Costs -->
                    <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg flex items-start gap-2">
                        <span class="text-yellow-500">⚠️</span>
                        <p class="text-xs text-yellow-700">
                            <strong>หมายเหตุ:</strong> การแปลอัตโนมัติจะใช้ API ทุกครั้งที่ Publish
                            อาจทำให้ค่าใช้จ่ายเพิ่มขึ้น
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- JavaScript สำหรับ Toggle Options -->
        <script>
        function toggleAutoTranslateOptions() {
            var checkbox = document.getElementById('auto_translate_enabled');
            var options = document.getElementById('auto_translate_options');
            if (checkbox.checked) {
                options.classList.remove('hidden');
            } else {
                options.classList.add('hidden');
            }
        }
        </script>
    </div>
</div>
