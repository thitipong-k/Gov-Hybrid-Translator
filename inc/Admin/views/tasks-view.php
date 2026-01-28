<?php
/**
 * Tasks View
 * 
 * แสดงรายการ Posts/Pages ที่ยังไม่ได้แปล
 * รองรับการเลือกภาษาเป้าหมายแบบ dynamic
 * 
 * @package GovHybridTranslator
 * @since 1.4.0
 */

use GovHybridTranslator\Core\Languages;
use GovHybridTranslator\Modules\Settings;

// ดึง target languages จาก settings
$settings_obj = isset($settings_obj) ? $settings_obj : new Settings();
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
<!-- VIEW: TASKS -->
<div id="view-tasks" class="view-section hidden space-y-6">
    <h2 class="text-2xl font-bold text-gray-800">Translation Tasks</h2>
    
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <button onclick="switchTasksTab('pages')" id="tab-tasks-btn-pages" class="tab-active whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Pages (<?php echo count($untranslated_pages); ?>)
            </button>
            <button onclick="switchTasksTab('page-contents')" id="tab-tasks-btn-page-contents" class="tab-inactive whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Page Contents (<?php echo count($untranslated_pages); ?>)
            </button>
            <button onclick="switchTasksTab('posts')" id="tab-tasks-btn-posts" class="tab-inactive whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Posts (<?php echo count($untranslated_posts); ?>)
            </button>
            <button onclick="switchTasksTab('post-contents')" id="tab-tasks-btn-post-contents" class="tab-inactive whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Post Contents (<?php echo count($untranslated_posts); ?>)
            </button>
            <button onclick="switchTasksTab('categories')" id="tab-tasks-btn-categories" class="tab-inactive whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Categories (<?php echo isset($untranslated_categories) && !is_wp_error($untranslated_categories) ? count($untranslated_categories) : 0; ?>)
            </button>
            <button onclick="switchTasksTab('menus')" id="tab-tasks-btn-menus" class="tab-inactive whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Menus (<?php echo isset($untranslated_menus) && !is_wp_error($untranslated_menus) ? count($untranslated_menus) : 0; ?>)
            </button>
            <button onclick="switchTasksTab('by-category')" id="tab-tasks-btn-by-category" class="tab-inactive whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                📁 By Category (<?php echo isset($incomplete_by_category) ? array_sum(array_column($incomplete_by_category, 'count')) : 0; ?>)
            </button>
            <button onclick="switchTasksTab('design-tabs')" id="tab-tasks-btn-design-tabs" class="tab-inactive whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                <?php 
                $dt_untranslated = 0;
                if (class_exists('\GovHybridTranslator\Integrations\DesignTabsIntegration')) {
                    $dt_integration = new \GovHybridTranslator\Integrations\DesignTabsIntegration();
                    $dt_untranslated = count($dt_integration->get_untranslated_groups());
                }
                ?>
                📐 Design Tabs (<?php echo $dt_untranslated; ?>)
            </button>
            <button onclick="switchTasksTab('quick')" id="tab-tasks-btn-quick" class="tab-inactive whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Quick Translate
            </button>
        </nav>
    </div>

    <!-- Tab: Pages -->
    <div id="tab-tasks-content-pages" class="tab-tasks-content">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="px-6 py-3 font-medium">Source Page</th>
                        <th class="px-6 py-3 font-medium">Target Language</th>
                        <th class="px-6 py-3 font-medium">Status</th>
                        <th class="px-6 py-3 font-medium">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    <?php if (!empty($untranslated_pages)) : ?>
                        <?php foreach ($untranslated_pages as $page) : ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-800"><?php echo esc_html($page->post_title); ?></td>
                                <td class="px-6 py-4">
                                    <select id="lang-select-page-<?php echo $page->ID; ?>" 
                                            class="p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500 focus:border-transparent text-sm">
                                        <?php foreach ($language_options as $lang): ?>
                                            <option value="<?php echo esc_attr($lang['code']); ?>">
                                                <?php echo esc_html($lang['flag'] . ' ' . $lang['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="px-6 py-4">
                                    <input type="text" 
                                        id="post-trans-<?php echo $page->ID; ?>" 
                                        placeholder="Enter Translated Title"
                                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500 focus:border-transparent text-sm">
                                </td>
                                <td class="px-6 py-4">
                                    <button id="btn-<?php echo $page->ID; ?>" 
                                        onclick="savePageTranslation(<?php echo $page->ID; ?>)" 
                                        class="text-gov-600 hover:text-gov-700 font-medium bg-gov-50 hover:bg-gov-100 px-3 py-1 rounded-md transition-colors">
                                        Save
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">All pages have been translated!</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab: Page Contents -->
    <div id="tab-tasks-content-page-contents" class="tab-tasks-content hidden">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="px-6 py-3 font-medium">Page Title</th>
                        <th class="px-6 py-3 font-medium">Excerpt</th>
                        <th class="px-6 py-3 font-medium">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    <?php if (!empty($untranslated_pages)) : ?>
                        <?php foreach ($untranslated_pages as $page) : ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-800"><?php echo esc_html($page->post_title); ?></td>
                                <td class="px-6 py-4 text-gray-500"><?php echo wp_trim_words($page->post_content, 15); ?></td>
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
                        <tr><td colspan="3" class="px-6 py-4 text-center text-gray-500">All pages have been translated!</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab: Posts -->
    <div id="tab-tasks-content-posts" class="tab-tasks-content hidden">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="px-6 py-3 font-medium">Source Post</th>
                        <th class="px-6 py-3 font-medium">Target Language</th>
                        <th class="px-6 py-3 font-medium">Status</th>
                        <th class="px-6 py-3 font-medium">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    <?php if (!empty($untranslated_posts)) : ?>
                        <?php foreach ($untranslated_posts as $post) : ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-800"><?php echo esc_html($post->post_title); ?></td>
                                <td class="px-6 py-4">
                                    <select id="lang-select-post-<?php echo $post->ID; ?>" 
                                            class="p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500 focus:border-transparent text-sm">
                                        <?php foreach ($language_options as $lang): ?>
                                            <option value="<?php echo esc_attr($lang['code']); ?>">
                                                <?php echo esc_html($lang['flag'] . ' ' . $lang['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="px-6 py-4">
                                    <input type="text" 
                                        id="post-trans-<?php echo $post->ID; ?>" 
                                        placeholder="Enter Translated Title (Optional)"
                                        oninput="updateButtonText(<?php echo $post->ID; ?>)"
                                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500 focus:border-transparent text-sm">
                                </td>
                                <td class="px-6 py-4">
                                    <button id="btn-<?php echo $post->ID; ?>" 
                                        onclick="translatePost(<?php echo $post->ID; ?>)" 
                                        class="text-gov-600 hover:text-gov-700 font-medium bg-gov-50 hover:bg-gov-100 px-3 py-1 rounded-md transition-colors">
                                        Translate
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">All posts have been translated!</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab: Post Contents -->
    <div id="tab-tasks-content-post-contents" class="tab-tasks-content hidden">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="px-6 py-3 font-medium">Post Title</th>
                        <th class="px-6 py-3 font-medium">Excerpt</th>
                        <th class="px-6 py-3 font-medium">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    <?php if (!empty($untranslated_posts)) : ?>
                        <?php foreach ($untranslated_posts as $post) : ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-800"><?php echo esc_html($post->post_title); ?></td>
                                <td class="px-6 py-4 text-gray-500"><?php echo wp_trim_words($post->post_content, 15); ?></td>
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
                        <tr><td colspan="3" class="px-6 py-4 text-center text-gray-500">All posts have been translated!</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php require GOV_HYBRID_TRANSLATOR_PATH . 'inc/Admin/views/tasks-categories.php'; ?>
    <?php require GOV_HYBRID_TRANSLATOR_PATH . 'inc/Admin/views/tasks-menus.php'; ?>
    <?php require GOV_HYBRID_TRANSLATOR_PATH . 'inc/Admin/views/tasks-by-category.php'; ?>
    <?php require GOV_HYBRID_TRANSLATOR_PATH . 'inc/Admin/views/tasks-design-tabs.php'; ?>

    <!-- Tab: Quick Translate -->
    <div id="tab-tasks-content-quick" class="tab-tasks-content hidden">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-6">Quick Translate</h3>
                <div class="space-y-6">
                    <!-- Source Text -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Source Text (Thai)
                        </label>
                        <textarea 
                            id="quick-translate-source"
                            class="w-full h-40 p-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500 focus:border-transparent text-sm resize-none"
                            placeholder="พิมพ์ข้อความที่ต้องการแปลเป็นภาษาอังกฤษ..."></textarea>
                    </div>

                    <!-- Target Language Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Target Language
                        </label>
                        <select class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500 focus:border-transparent text-sm">
                            <option value="en">English (EN)</option>
                            <option value="zh">Chinese (ZH)</option>
                            <option value="ja">Japanese (JA)</option>
                        </select>
                    </div>

                    <!-- Translate Button -->
                    <button class="w-full bg-gov-600 hover:bg-gov-700 text-white font-medium py-3 px-6 rounded-lg transition-colors flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Translate with AI
                    </button>

                    <!-- Translation Result -->
                    <div id="translation-result" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Translated Text
                        </label>
                        <div class="w-full min-h-40 p-4 bg-gray-50 border border-gray-200 rounded-lg text-sm">
                            <p class="text-gray-600 italic">Translation will appear here...</p>
                        </div>
                    </div>

                    <!-- Info Box -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex gap-3">
                            <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="text-sm text-blue-800">
                                <p class="font-medium mb-1">Quick Translation Tips:</p>
                                <ul class="list-disc list-inside space-y-1 text-blue-700">
                                    <li>Use for short texts and quick translations</li>
                                    <li>Glossary terms will be automatically applied</li>
                                    <li>Each translation consumes AI credits</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Scripts moved to assets/js/admin-dashboard.js -->
</div>

