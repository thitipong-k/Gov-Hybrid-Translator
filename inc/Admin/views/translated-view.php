<!-- VIEW: TRANSLATED -->
<?php
use GovHybridTranslator\Core\TranslationMeta;
?>
<div id="view-translated" class="view-section hidden space-y-6">
    <h2 class="text-2xl font-bold text-gray-800">Translated</h2>
    
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8 overflow-x-auto">
            <!-- แท็บเปรียบเทียบ TH vs EN - แสดงเป็นแท็บแรก -->
            <button onclick="switchTranslatedTab('comparison')" id="tab-translated-btn-comparison" class="tab-inactive whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-1">
                🔄 TH ↔ EN
            </button>
            <button onclick="switchTranslatedTab('pages')" id="tab-translated-btn-pages" class="tab-active whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Pages (<?php echo count($translated_pages); ?>)
            </button>
            <button onclick="switchTranslatedTab('page-contents')" id="tab-translated-btn-page-contents" class="tab-inactive whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Page Contents (<?php echo count($translated_pages); ?>)
            </button>
            <button onclick="switchTranslatedTab('posts')" id="tab-translated-btn-posts" class="tab-inactive whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Posts (<?php echo count($translated_posts); ?>)
            </button>
            <button onclick="switchTranslatedTab('post-contents')" id="tab-translated-btn-post-contents" class="tab-inactive whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Post Contents (<?php echo count($translated_posts); ?>)
            </button>
            <button onclick="switchTranslatedTab('categories')" id="tab-translated-btn-categories" class="tab-inactive whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Categories (<?php echo isset($translated_categories) && !is_wp_error($translated_categories) ? count($translated_categories) : 0; ?>)
            </button>
            <button onclick="switchTranslatedTab('menus')" id="tab-translated-btn-menus" class="tab-inactive whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Menus (<?php echo isset($translated_menus) && !is_wp_error($translated_menus) ? count($translated_menus) : 0; ?>)
            </button>
            <button onclick="switchTranslatedTab('design-tabs')" id="tab-translated-btn-design-tabs" class="tab-inactive whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                <?php 
                $dt_translated_count = 0;
                if (class_exists('\GovHybridTranslator\Integrations\DesignTabsIntegration')) {
                    $dt_integration_count = new \GovHybridTranslator\Integrations\DesignTabsIntegration();
                    $dt_translated_count = count($dt_integration_count->get_translated_groups());
                }
                ?>
                📐 Design Tabs (<?php echo $dt_translated_count; ?>)
            </button>
        </nav>
    </div>

    <!-- ========== Tab: Comparison (TH vs EN) ========== -->
    <!-- แท็บเปรียบเทียบ: แสดง Posts และ Pages ทั้งหมดในรูปแบบ TH ↔ EN side by side -->
    <div id="tab-translated-content-comparison" class="tab-translated-content hidden">
        <div class="space-y-6">
            <!-- Filter: เลือกดู Posts หรือ Pages -->
            <div class="flex items-center gap-4 bg-gray-50 rounded-lg p-4">
                <span class="text-sm font-medium text-gray-600">แสดง:</span>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="comparison-show-pages" checked class="rounded text-gov-600">
                    <span class="text-sm">Pages (<?php echo count($translated_pages); ?>)</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="comparison-show-posts" checked class="rounded text-gov-600">
                    <span class="text-sm">Posts (<?php echo count($translated_posts); ?>)</span>
                </label>
            </div>

            <!-- Pages Comparison -->
            <div id="comparison-pages-section" class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                    📄 Pages Comparison
                </h3>
                <?php if (!empty($translated_pages)) : ?>
                    <?php foreach ($translated_pages as $page) : 
                        $en_title = TranslationMeta::get_title($page->ID, 'en');
                        $en_content = TranslationMeta::get_content($page->ID, 'en');
                        $en_excerpt = TranslationMeta::get_excerpt($page->ID, 'en');
                    ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-gov-50 to-blue-50 px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="text-lg">📄</span>
                                <span class="font-medium text-gray-800">Page #<?php echo $page->ID; ?></span>
                                <span class="text-xs text-gray-500"><?php echo get_the_modified_date('Y-m-d H:i', $page); ?></span>
                            </div>
                            <button 
                                onclick="showTranslationModal(<?php echo $page->ID; ?>)" 
                                class="text-blue-600 hover:text-blue-700 text-sm flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                View Full
                            </button>
                            <!-- Hidden data for modal -->
                            <div id="post-data-<?php echo $page->ID; ?>" class="hidden"
                                data-th-title="<?php echo esc_attr($page->post_title); ?>"
                                data-en-title="<?php echo esc_attr($en_title); ?>"
                                data-en-content="<?php echo esc_attr($en_content); ?>"
                                data-en-excerpt="<?php echo esc_attr($en_excerpt); ?>"
                                data-th-content="<?php echo esc_attr($page->post_content); ?>"></div>
                        </div>
                        <!-- Content: Side by Side -->
                        <div class="grid grid-cols-2 divide-x divide-gray-200">
                            <!-- Thai (Left) -->
                            <div class="p-4 bg-orange-50/30">
                                <div class="text-xs text-orange-600 font-medium mb-2">🇹🇭 THAI</div>
                                <div class="font-semibold text-gray-800 mb-2"><?php echo esc_html($page->post_title); ?></div>
                                <div class="text-sm text-gray-600 line-clamp-3"><?php echo esc_html(wp_trim_words(strip_tags($page->post_content), 40)); ?></div>
                            </div>
                            <!-- English (Right) -->
                            <div class="p-4 bg-blue-50/30">
                                <div class="text-xs text-blue-600 font-medium mb-2">🇺🇸 ENGLISH</div>
                                <?php if (!empty($en_title)) : ?>
                                    <div class="font-semibold text-gray-800 mb-2"><?php echo esc_html($en_title); ?></div>
                                    <div class="text-sm text-gray-600 line-clamp-3"><?php echo esc_html(wp_trim_words(strip_tags($en_content), 40)); ?></div>
                                <?php else : ?>
                                    <div class="text-gray-400 italic">ยังไม่ได้แปล</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="text-center text-gray-500 py-8">No translated pages yet.</div>
                <?php endif; ?>
            </div>

            <!-- Posts Comparison -->
            <div id="comparison-posts-section" class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                    📝 Posts Comparison
                </h3>
                <?php if (!empty($translated_posts)) : ?>
                    <?php foreach ($translated_posts as $post) : 
                        $en_title = TranslationMeta::get_title($post->ID, 'en');
                        $en_content = TranslationMeta::get_content($post->ID, 'en');
                        $en_excerpt = TranslationMeta::get_excerpt($post->ID, 'en');
                    ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="text-lg">📝</span>
                                <span class="font-medium text-gray-800">Post #<?php echo $post->ID; ?></span>
                                <span class="text-xs text-gray-500"><?php echo get_the_modified_date('Y-m-d H:i', $post); ?></span>
                            </div>
                            <button 
                                onclick="showTranslationModal(<?php echo $post->ID; ?>)" 
                                class="text-blue-600 hover:text-blue-700 text-sm flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                View Full
                            </button>
                            <!-- Hidden data for modal -->
                            <div id="post-data-<?php echo $post->ID; ?>" class="hidden"
                                data-th-title="<?php echo esc_attr($post->post_title); ?>"
                                data-en-title="<?php echo esc_attr($en_title); ?>"
                                data-en-content="<?php echo esc_attr($en_content); ?>"
                                data-en-excerpt="<?php echo esc_attr($en_excerpt); ?>"
                                data-th-content="<?php echo esc_attr($post->post_content); ?>"></div>
                        </div>
                        <!-- Content: Side by Side -->
                        <div class="grid grid-cols-2 divide-x divide-gray-200">
                            <!-- Thai (Left) -->
                            <div class="p-4 bg-orange-50/30">
                                <div class="text-xs text-orange-600 font-medium mb-2">🇹🇭 THAI</div>
                                <div class="font-semibold text-gray-800 mb-2"><?php echo esc_html($post->post_title); ?></div>
                                <div class="text-sm text-gray-600 line-clamp-3"><?php echo esc_html(wp_trim_words(strip_tags($post->post_content), 40)); ?></div>
                            </div>
                            <!-- English (Right) -->
                            <div class="p-4 bg-blue-50/30">
                                <div class="text-xs text-blue-600 font-medium mb-2">🇺🇸 ENGLISH</div>
                                <?php if (!empty($en_title)) : ?>
                                    <div class="font-semibold text-gray-800 mb-2"><?php echo esc_html($en_title); ?></div>
                                    <div class="text-sm text-gray-600 line-clamp-3"><?php echo esc_html(wp_trim_words(strip_tags($en_content), 40)); ?></div>
                                <?php else : ?>
                                    <div class="text-gray-400 italic">ยังไม่ได้แปล</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="text-center text-gray-500 py-8">No translated posts yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tab: Pages -->
    <!-- แท็บ Pages: แสดงรายการ Pages ที่แปลแล้วพร้อมปุ่มดูข้อความแปล -->
    <div id="tab-translated-content-pages" class="tab-translated-content">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="px-6 py-3 font-medium">Page Title (TH)</th>
                        <th class="px-6 py-3 font-medium">English Title</th>
                        <th class="px-6 py-3 font-medium">Last Updated</th>
                        <th class="px-6 py-3 font-medium">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    <?php if (!empty($translated_pages)) : ?>
                        <?php foreach ($translated_pages as $page) : 
                            // ใช้ TranslationMeta ดึงข้อมูลแปลทั้งหมด
                            $en_title = TranslationMeta::get_title($page->ID, 'en');
                            $en_content = TranslationMeta::get_content($page->ID, 'en');
                            $en_excerpt = TranslationMeta::get_excerpt($page->ID, 'en');
                            $data = TranslationMeta::get($page->ID, 'en');
                            $status = $data['status'] ?? 'published';
                        ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-800">
                                    <?php echo esc_html($page->post_title); ?>
                                    <?php if ($status === 'draft') : ?>
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">Draft</span>
                                    <?php else : ?>
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Published</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <!-- English Title - คลิกเพื่อดูข้อความแปลทั้งหมด -->
                                    <div class="flex items-center gap-2">
                                        <input type="text" 
                                            id="page-edit-<?php echo $page->ID; ?>" 
                                            value="<?php echo esc_attr($en_title); ?>"
                                            class="flex-1 p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500 focus:border-transparent text-sm">
                                        <!-- ปุ่มดูข้อความแปลทั้งหมด -->
                                        <button 
                                            onclick="showTranslationModal(<?php echo $page->ID; ?>)" 
                                            class="text-blue-600 hover:text-blue-700 p-2 rounded-lg hover:bg-blue-50 transition-colors"
                                            title="ดูข้อความแปลทั้งหมด">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <!-- ซ่อนข้อมูลแปลสำหรับ Modal -->
                                    <div id="post-data-<?php echo $page->ID; ?>" class="hidden"
                                        data-th-title="<?php echo esc_attr($page->post_title); ?>"
                                        data-en-title="<?php echo esc_attr($en_title); ?>"
                                        data-en-content="<?php echo esc_attr($en_content); ?>"
                                        data-en-excerpt="<?php echo esc_attr($en_excerpt); ?>"
                                        data-en-status="<?php echo esc_attr($status); ?>"
                                        data-th-content="<?php echo esc_attr($page->post_content); ?>"></div>
                                </td>
                                <td class="px-6 py-4 text-gray-500"><?php echo get_the_modified_date('Y-m-d', $page); ?></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <button onclick="openEditModal(<?php echo $page->ID; ?>)" 
                                            class="text-blue-600 hover:text-blue-700 font-medium bg-blue-50 hover:bg-blue-100 px-3 py-1 rounded-md transition-colors flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit
                                        </button>
                                        <button onclick="deleteTranslation(<?php echo $page->ID; ?>, 'en')" 
                                            class="text-red-600 hover:text-red-700 font-medium bg-red-50 hover:bg-red-100 px-3 py-1 rounded-md transition-colors flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">No translated pages yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab: Page Contents -->
    <!-- แท็บ Page Contents: แสดงเนื้อหา Pages ที่แปลแล้วพร้อมปุ่มดูผลแปล -->
    <div id="tab-translated-content-page-contents" class="tab-translated-content hidden">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="px-6 py-3 font-medium">Page Title (TH)</th>
                        <th class="px-6 py-3 font-medium">🇺🇸 English Excerpt</th>
                        <th class="px-6 py-3 font-medium">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    <?php if (!empty($translated_pages)) : ?>
                        <?php foreach ($translated_pages as $page) : 
                            // ดึงข้อมูลที่แปลแล้ว
                            $en_title = TranslationMeta::get_title($page->ID, 'en');
                            $en_content = TranslationMeta::get_content($page->ID, 'en');
                            $en_excerpt = TranslationMeta::get_excerpt($page->ID, 'en');
                        ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-800"><?php echo esc_html($page->post_title); ?></td>
                                <td class="px-6 py-4">
                                    <!-- แสดง English Excerpt หรือตัด Content -->
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-600">
                                            <?php 
                                            if (!empty($en_excerpt)) {
                                                echo esc_html(wp_trim_words($en_excerpt, 12));
                                            } elseif (!empty($en_content)) {
                                                echo esc_html(wp_trim_words(strip_tags($en_content), 12));
                                            } else {
                                                echo '<span class="text-gray-400 italic">ยังไม่ได้แปล</span>';
                                            }
                                            ?>
                                        </span>
                                        <!-- ปุ่มดูข้อความแปลทั้งหมด -->
                                        <button 
                                            onclick="showTranslationModal(<?php echo $page->ID; ?>)" 
                                            class="text-blue-600 hover:text-blue-700 p-2 rounded-lg hover:bg-blue-50 transition-colors"
                                            title="ดูข้อความแปลทั้งหมด">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <!-- ซ่อนข้อมูลแปลสำหรับ Modal -->
                                    <div id="post-data-<?php echo $page->ID; ?>" class="hidden"
                                        data-th-title="<?php echo esc_attr($page->post_title); ?>"
                                        data-en-title="<?php echo esc_attr($en_title); ?>"
                                        data-en-content="<?php echo esc_attr($en_content); ?>"
                                        data-en-excerpt="<?php echo esc_attr($en_excerpt); ?>"
                                        data-en-status="<?php echo esc_attr($status); ?>"
                                        data-th-content="<?php echo esc_attr($page->post_content); ?>"></div>
                                </td>
                                <td class="px-6 py-4">
                                    <button 
                                        data-post-id="<?php echo $page->ID; ?>"
                                        class="ght-review-content-btn text-gov-600 hover:text-gov-700 font-medium bg-gov-50 hover:bg-gov-100 px-3 py-1 rounded-md transition-colors">
                                        Review Content
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="3" class="px-6 py-4 text-center text-gray-500">No translated pages yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab: Posts -->
    <!-- แท็บ Posts: แสดงรายการ Posts ที่แปลแล้วพร้อมปุ่มดูข้อความแปล -->
    <div id="tab-translated-content-posts" class="tab-translated-content hidden">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="px-6 py-3 font-medium">Post Title (TH)</th>
                        <th class="px-6 py-3 font-medium">English Title</th>
                        <th class="px-6 py-3 font-medium">Category</th>
                        <th class="px-6 py-3 font-medium">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    <?php if (!empty($translated_posts)) : ?>
                        <?php foreach ($translated_posts as $post) : 
                            $categories = get_the_category($post->ID);
                            // ใช้ TranslationMeta ดึงข้อมูลแปลทั้งหมด
                            $en_title = TranslationMeta::get_title($post->ID, 'en');
                            $en_content = TranslationMeta::get_content($post->ID, 'en');
                            $en_excerpt = TranslationMeta::get_excerpt($post->ID, 'en');
                            $data = TranslationMeta::get($post->ID, 'en');
                            $status = $data['status'] ?? 'published';
                        ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-800">
                                    <?php echo esc_html($post->post_title); ?>
                                    <?php if ($status === 'draft') : ?>
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">Draft</span>
                                    <?php else : ?>
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Published</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <!-- English Title - คลิกเพื่อดูข้อความแปลทั้งหมด -->
                                    <div class="flex items-center gap-2">
                                        <input type="text" 
                                            id="post-edit-<?php echo $post->ID; ?>" 
                                            value="<?php echo esc_attr($en_title); ?>"
                                            class="flex-1 p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500 focus:border-transparent text-sm">
                                        <!-- ปุ่มดูข้อความแปลทั้งหมด -->
                                        <button 
                                            onclick="showTranslationModal(<?php echo $post->ID; ?>)" 
                                            class="text-blue-600 hover:text-blue-700 p-2 rounded-lg hover:bg-blue-50 transition-colors"
                                            title="ดูข้อความแปลทั้งหมด">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <!-- ซ่อนข้อมูลแปลสำหรับ Modal -->
                                    <div id="post-data-<?php echo $post->ID; ?>" class="hidden"
                                        data-th-title="<?php echo esc_attr($post->post_title); ?>"
                                        data-en-title="<?php echo esc_attr($en_title); ?>"
                                        data-en-content="<?php echo esc_attr($en_content); ?>"
                                        data-en-excerpt="<?php echo esc_attr($en_excerpt); ?>"
                                        data-en-status="<?php echo esc_attr($status); ?>"
                                        data-th-content="<?php echo esc_attr($post->post_content); ?>"></div>
                                </td>
                                <td class="px-6 py-4 text-gray-500"><?php echo !empty($categories) ? esc_html($categories[0]->name) : 'Uncategorized'; ?></td>
                                <td class="px-6 py-4">
                                    <button onclick="updatePostTranslation(<?php echo $post->ID; ?>)" 
                                        class="text-gov-600 hover:text-gov-700 font-medium bg-gov-50 hover:bg-gov-100 px-3 py-1 rounded-md transition-colors">
                                        Update
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">No translated posts yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab: Post Contents -->
    <!-- แท็บ Post Contents: แสดงเนื้อหาที่แปลแล้วพร้อมปุ่มดูผลแปล -->
    <div id="tab-translated-content-post-contents" class="tab-translated-content hidden">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="px-6 py-3 font-medium">Post Title (TH)</th>
                        <th class="px-6 py-3 font-medium">🇺🇸 English Excerpt</th>
                        <th class="px-6 py-3 font-medium">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    <?php if (!empty($translated_posts)) : ?>
                        <?php foreach ($translated_posts as $post) : 
                            // ดึงข้อมูลที่แปลแล้ว
                            $en_title = TranslationMeta::get_title($post->ID, 'en');
                            $en_content = TranslationMeta::get_content($post->ID, 'en');
                            $en_excerpt = TranslationMeta::get_excerpt($post->ID, 'en');
                        ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-800"><?php echo esc_html($post->post_title); ?></td>
                                <td class="px-6 py-4">
                                    <!-- แสดง English Excerpt หรือตัด Content -->
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-600">
                                            <?php 
                                            if (!empty($en_excerpt)) {
                                                echo esc_html(wp_trim_words($en_excerpt, 12));
                                            } elseif (!empty($en_content)) {
                                                echo esc_html(wp_trim_words(strip_tags($en_content), 12));
                                            } else {
                                                echo '<span class="text-gray-400 italic">ยังไม่ได้แปล</span>';
                                            }
                                            ?>
                                        </span>
                                        <!-- ปุ่มดูข้อความแปลทั้งหมด -->
                                        <button 
                                            onclick="showTranslationModal(<?php echo $post->ID; ?>)" 
                                            class="text-blue-600 hover:text-blue-700 p-2 rounded-lg hover:bg-blue-50 transition-colors"
                                            title="ดูข้อความแปลทั้งหมด">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <!-- ซ่อนข้อมูลแปลสำหรับ Modal -->
                                    <div id="post-data-<?php echo $post->ID; ?>" class="hidden"
                                        data-th-title="<?php echo esc_attr($post->post_title); ?>"
                                        data-en-title="<?php echo esc_attr($en_title); ?>"
                                        data-en-content="<?php echo esc_attr($en_content); ?>"
                                        data-en-excerpt="<?php echo esc_attr($en_excerpt); ?>"
                                        data-en-status="<?php echo esc_attr($status); ?>"
                                        data-th-content="<?php echo esc_attr($post->post_content); ?>"></div>
                                </td>
                                <td class="px-6 py-4">
                                    <button 
                                        data-post-id="<?php echo $post->ID; ?>"
                                        class="ght-review-content-btn text-gov-600 hover:text-gov-700 font-medium bg-gov-50 hover:bg-gov-100 px-3 py-1 rounded-md transition-colors">
                                        Review Content
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="3" class="px-6 py-4 text-center text-gray-500">No translated posts yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php require GOV_HYBRID_TRANSLATOR_PATH . 'inc/Admin/views/translated-categories.php'; ?>
    <?php require GOV_HYBRID_TRANSLATOR_PATH . 'inc/Admin/views/translated-menus.php'; ?>
    <?php require GOV_HYBRID_TRANSLATOR_PATH . 'inc/Admin/views/translated-design-tabs.php'; ?>

<!-- Script moved to assets/js/admin-dashboard.js -->

    <!-- CSS สำหรับ line-clamp และ Comparison View -->
    <style>
    /* line-clamp utilities สำหรับ Tailwind-like behavior */
    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    /* Smooth transitions for filter */
    #comparison-pages-section,
    #comparison-posts-section {
        transition: opacity 0.3s ease;
    }
    
    /* Card hover effect */
    #tab-translated-content-comparison .bg-white:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    </style>
</div>
