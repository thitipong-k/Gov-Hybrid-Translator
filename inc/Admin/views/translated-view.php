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
                $dt_integration_count = new \GovHybridTranslator\Integrations\DesignTabsIntegration();
                $dt_translated_count = count($dt_integration_count->get_translated_groups());
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

    <script>
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
        formData.append('nonce', '<?php echo wp_create_nonce('ght_save_translation'); ?>');
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
        formData.append('nonce', '<?php echo wp_create_nonce('ght_save_translation'); ?>');
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
        formData.append('nonce', '<?php echo wp_create_nonce('ght_save_translation'); ?>');
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
        formData.append('nonce', '<?php echo wp_create_nonce('ght_save_translation'); ?>');
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

    /**
     * แสดง Modal ดูข้อความแปลทั้งหมด
     * 
     * ฟังก์ชันนี้จะดึงข้อมูลจาก hidden div และแสดงใน modal
     * รองรับ Title, Content, Excerpt ทั้งภาษาไทยและอังกฤษ
     * 
     * @param {number} postId - Post ID
     */
    function showTranslationModal(postId) {
        // ดึงข้อมูลจาก hidden div
        const dataDiv = document.getElementById('post-data-' + postId);
        if (!dataDiv) {
            showNotification('ไม่พบข้อมูลการแปล', 'error');
            return;
        }

        const thTitle = dataDiv.dataset.thTitle || '';
        const enTitle = dataDiv.dataset.enTitle || '';
        const enContent = dataDiv.dataset.enContent || '';
        const enExcerpt = dataDiv.dataset.enExcerpt || '';
        const thContent = dataDiv.dataset.thContent || '';

        // สร้าง Modal HTML
        const modalHtml = `
            <div id="translation-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-hidden flex flex-col">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gov-50 to-blue-50">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">🌐 ดูข้อความแปลทั้งหมด</h3>
                            <p class="text-sm text-gray-500">Post ID: ${postId}</p>
                        </div>
                        <button onclick="closeTranslationModal()" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Body - Scrollable -->
                    <div class="flex-1 overflow-y-auto p-6 space-y-6">
                        <!-- Title Section -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                                <h4 class="font-medium text-orange-800 mb-2">🇹🇭 Thai Title</h4>
                                <p class="text-gray-800">${escapeHtml(thTitle)}</p>
                            </div>
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <h4 class="font-medium text-blue-800 mb-2">🇺🇸 English Title</h4>
                                <p class="text-gray-800">${escapeHtml(enTitle) || '<span class="text-gray-400 italic">ยังไม่ได้แปล</span>'}</p>
                            </div>
                        </div>

                        <!-- Excerpt Section -->
                        ${enExcerpt ? `
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <h4 class="font-medium text-green-800 mb-2">📝 English Excerpt</h4>
                            <p class="text-gray-700">${escapeHtml(enExcerpt)}</p>
                        </div>
                        ` : ''}

                        <!-- Content Section -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <h4 class="font-medium text-orange-800 mb-2 flex items-center gap-2">
                                    🇹🇭 Thai Content
                                    <span class="text-xs bg-orange-100 px-2 py-1 rounded">${thContent.length} chars</span>
                                </h4>
                                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 max-h-80 overflow-y-auto">
                                    <div class="text-gray-800 text-sm whitespace-pre-wrap">${formatContent(thContent)}</div>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-medium text-blue-800 mb-2 flex items-center gap-2">
                                    🇺🇸 English Content
                                    <span class="text-xs bg-blue-100 px-2 py-1 rounded">${enContent.length} chars</span>
                                </h4>
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 max-h-80 overflow-y-auto">
                                    <div class="text-gray-800 text-sm whitespace-pre-wrap">${enContent ? formatContent(enContent) : '<span class="text-gray-400 italic">ยังไม่ได้แปล</span>'}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-3">
                        <button onclick="copyTranslation('${postId}')" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            Copy English Content
                        </button>
                        <button onclick="closeTranslationModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                            ปิด
                        </button>
                    </div>
                </div>
            </div>
        `;

        // เพิ่ม Modal ลงใน DOM
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // กด ESC เพื่อปิด Modal
        document.addEventListener('keydown', handleModalEscape);
    }

    /**
     * ปิด Translation Modal
     */
    function closeTranslationModal() {
        const modal = document.getElementById('translation-modal');
        if (modal) {
            modal.remove();
        }
        document.removeEventListener('keydown', handleModalEscape);
    }

    /**
     * จัดการการกด ESC เพื่อปิด Modal
     */
    function handleModalEscape(e) {
        if (e.key === 'Escape') {
            closeTranslationModal();
        }
    }

    /**
     * Escape HTML สำหรับป้องกัน XSS
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.innerText = text;
        return div.innerHTML;
    }

    /**
     * Format content สำหรับแสดงผล
     * แปลง HTML entities และตัด HTML tags บางส่วน
     */
    function formatContent(content) {
        if (!content) return '';
        // ลบ HTML tags แต่เก็บ line breaks
        let text = content.replace(/<br\s*\/?>/gi, '\n');
        text = text.replace(/<\/p>/gi, '\n\n');
        text = text.replace(/<[^>]+>/g, '');
        // Decode HTML entities
        const textarea = document.createElement('textarea');
        textarea.innerHTML = text;
        return textarea.value.substring(0, 2000) + (text.length > 2000 ? '...' : '');
    }

    /**
     * คัดลอก English Content ไปยัง Clipboard
     */
    function copyTranslation(postId) {
        const dataDiv = document.getElementById('post-data-' + postId);
        if (!dataDiv) return;

        const enContent = dataDiv.dataset.enContent || '';
        const enTitle = dataDiv.dataset.enTitle || '';
        
        const fullText = `Title: ${enTitle}\n\nContent:\n${enContent}`;
        navigator.clipboard.writeText(fullText).then(() => {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = 'Copied!';
            setTimeout(() => btn.innerHTML = originalText, 2000);
        });
    }

    /**
     * ลบคำแปล (Delete Translation)
     * เรียก AJAX ght_delete_translation
     */
    function deleteTranslation(postId, lang) {
        if (!confirm('Are you sure you want to delete this translation? This cannot be undone.')) {
            return;
        }

        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        btn.innerHTML = 'Deleting...';
        btn.disabled = true;

        const formData = new FormData();
        formData.append('action', 'ght_delete_translation');
        formData.append('nonce', '<?php echo wp_create_nonce('ght_save_translation'); ?>');
        formData.append('post_id', postId);
        formData.append('lang', lang);

        fetch(ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Translation deleted successfully', 'success');
                // Reload page to refresh list
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification(data.data.message || 'Error deleting', 'error');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        })
        .catch(err => {
            showNotification('Error deleting translation', 'error');
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    }

    /**
     * เปิด Modal สำหรับแก้ไขคำแปล (Manual Edit)
     */
    function openEditModal(postId) {
        const dataDiv = document.getElementById('post-data-' + postId);
        if (!dataDiv) return;

        const enTitle = dataDiv.dataset.enTitle || '';
        const enContent = dataDiv.dataset.enContent || '';
        const enExcerpt = dataDiv.dataset.enExcerpt || '';
        const status = dataDiv.dataset.enStatus || 'published';

        const modalHtml = `
            <div id="edit-translation-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-800">✏️ Edit Translation</h3>
                        <button onclick="document.getElementById('edit-translation-modal').remove()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Title (EN)</label>
                            <input type="text" id="edit-title" value="${escapeHtml(enTitle)}" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Excerpt (EN)</label>
                            <textarea id="edit-excerpt" rows="3" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">${escapeHtml(enExcerpt)}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Content (EN) - HTML Supported</label>
                            <textarea id="edit-content" rows="15" class="w-full font-mono text-sm border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">${escapeHtml(enContent)}</textarea>
                            <p class="text-xs text-gray-500 mt-1">You can use HTML tags like &lt;b&gt;, &lt;p&gt;, &lt;br&gt;, &lt;ul&gt;.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="edit-status" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="published" ${status === 'published' ? 'selected' : ''}>Published (Visible)</option>
                                <option value="draft" ${status === 'draft' ? 'selected' : ''}>Draft (Hidden)</option>
                            </select>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-3">
                        <button onclick="document.getElementById('edit-translation-modal').remove()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button onclick="saveFullTranslation(${postId})" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save Changes</button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }

    /**
     * บันทึกคำแปลทั้งหมด (Save Full Translation)
     */
    function saveFullTranslation(postId) {
        const title = document.getElementById('edit-title').value;
        const excerpt = document.getElementById('edit-excerpt').value;
        const content = document.getElementById('edit-content').value;
        const status = document.getElementById('edit-status').value;
        const btn = event.target;
        
        btn.innerText = 'Saving...';
        btn.disabled = true;

        const formData = new FormData();
        formData.append('action', 'ght_save_full_translation');
        formData.append('nonce', '<?php echo wp_create_nonce('ght_save_translation'); ?>');
        formData.append('post_id', postId);
        formData.append('lang', 'en'); // Default to EN for now, can support dynamic later
        formData.append('title', title);
        formData.append('excerpt', excerpt);
        formData.append('content', content);
        formData.append('status', status);

        fetch(ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Translation saved successfully', 'success');
                // Close modal and reload
                document.getElementById('edit-translation-modal').remove();
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification(data.data.message || 'Error saving', 'error');
                btn.innerText = 'Save Changes';
                btn.disabled = false;
            }
        })
        .catch(err => {
            showNotification('Error saving translation', 'error');
            btn.innerText = 'Save Changes';
            btn.disabled = false;
        });
    }
        
        });
    }

    // ========== Comparison Filter Checkboxes ==========
    // ตัวกรองสำหรับแท็บ Comparison - ซ่อน/แสดง Pages หรือ Posts
    document.addEventListener('DOMContentLoaded', function() {
        const showPagesCheckbox = document.getElementById('comparison-show-pages');
        const showPostsCheckbox = document.getElementById('comparison-show-posts');
        const pagesSection = document.getElementById('comparison-pages-section');
        const postsSection = document.getElementById('comparison-posts-section');

        if (showPagesCheckbox && pagesSection) {
            showPagesCheckbox.addEventListener('change', function() {
                pagesSection.style.display = this.checked ? 'block' : 'none';
            });
        }

        if (showPostsCheckbox && postsSection) {
            showPostsCheckbox.addEventListener('change', function() {
                postsSection.style.display = this.checked ? 'block' : 'none';
            });
        }
    });
    </script>

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
