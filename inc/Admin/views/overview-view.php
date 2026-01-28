<?php
/**
 * Overview View - Dashboard หลัก
 * 
 * แสดงผล:
 * - Stats Cards (Total Translations, Glossary Terms, TM Hit Rate, etc.)
 * - Translation Status Cards (ยังไม่แปล, รอแปล, แปลบางส่วน, แปลครบ)
 * - Monthly Translation Trends Chart
 * - Language Distribution
 * - Top Categories
 * - API Quota Usage
 * - Recent Translations
 * 
 * @package GovHybridTranslator
 * @since 1.0.0
 * @modified 2.1.0 - แสดงข้อมูลจริงจาก database แทน mock data
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;
?>
<!-- VIEW: OVERVIEW -->
<div id="view-overview" class="view-section space-y-6">
    <!-- Stats Cards Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Total Translations -->
        <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-200 cursor-pointer hover:shadow-md transition-shadow" onclick="switchView('translated')">

            <div class="flex items-center gap-2 text-gray-500 text-xs mb-2">
                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <span>Total Translations</span>
            </div>
            <div class="text-2xl font-bold text-gray-900"><?php echo number_format($translated_count); ?></div>
            <div class="text-xs text-gray-400 mt-1">Posts/Pages translated</div>
        </div>

        <!-- Glossary Terms -->
        <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-200 cursor-pointer hover:shadow-md transition-shadow" onclick="switchView('glossary')">

            <div class="flex items-center gap-2 text-gray-500 text-xs mb-2">
                <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                <span>Glossary Terms</span>
            </div>
            <div class="text-2xl font-bold text-gray-900"><?php echo number_format($glossary_terms); ?></div>
            <div class="text-xs text-purple-600 mt-1">Active</div>
        </div>

        <!-- Average Translation Time -->
        <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-200">
            <div class="flex items-center gap-2 text-gray-500 text-xs mb-2">
                <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>Avg. Translation Time</span>
            </div>
            <div class="text-2xl font-bold text-gray-900"><?php echo is_numeric($avg_translation_time) ? $avg_translation_time . ' min' : $avg_translation_time; ?></div>
            <div class="text-xs text-gray-400 mt-1">Per page</div>
        </div>

        <!-- AI Credits Used -->
        <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-200">
            <div class="flex items-center gap-2 text-gray-500 text-xs mb-2">
                <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                <span>AI Credits Used</span>
            </div>
            <div class="text-2xl font-bold text-gray-900"><?php echo number_format($ai_credits_used); ?></div>
            <div class="text-xs text-gray-400 mt-1">Translation Memory Uses</div>
        </div>

        <!-- TM Hit Rate -->
        <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-200">
            <div class="flex items-center gap-2 text-gray-500 text-xs mb-2">
                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>TM Hit Rate</span>
            </div>
            <div class="text-2xl font-bold text-gray-900"><?php echo number_format($success_rate, 1); ?>%</div>
            <div class="text-xs text-green-600 mt-1">TM Hit Rate</div>
        </div>
    </div>

    <!-- === Translation Status Stats (Phase 1) === -->
    <?php
    // ดึงสถิติการแปลจาก TranslationStatus class (พร้อม defensive check)
    $stats = ['none' => 0, 'pending' => 0, 'partial' => 0, 'translated' => 0, 'draft' => 0, 'needs_update' => 0];
    if (class_exists('\GovHybridTranslator\Core\TranslationStatus')) {
        $translation_status = new \GovHybridTranslator\Core\TranslationStatus();
        $stats = $translation_status->get_statistics();
    }
    ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- ยังไม่แปล -->
        <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-200 cursor-pointer hover:shadow-md transition-shadow" onclick="switchView('tasks')">

            <div class="flex items-center gap-2 text-gray-500 text-xs mb-2">
                <span class="text-lg">⬜</span>
                <span>ยังไม่แปล</span>
            </div>
            <div class="text-2xl font-bold text-gray-400"><?php echo number_format($stats['none']); ?></div>
            <div class="text-xs text-gray-400 mt-1">None</div>
        </div>

        <!-- รอแปล -->
        <div class="bg-white p-5 rounded-lg shadow-sm border border-yellow-200 bg-yellow-50 cursor-pointer hover:shadow-md transition-shadow" onclick="switchView('tasks')">

            <div class="flex items-center gap-2 text-yellow-600 text-xs mb-2">
                <span class="text-lg">🟡</span>
                <span>รอแปล</span>
            </div>
            <div class="text-2xl font-bold text-yellow-600"><?php echo number_format($stats['pending']); ?></div>
            <div class="text-xs text-yellow-500 mt-1">Pending</div>
        </div>

        <!-- ฉบับร่าง (Draft) -->
        <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-200 bg-gray-50 cursor-pointer hover:shadow-md transition-shadow" onclick="switchView('tasks')">

            <div class="flex items-center gap-2 text-gray-600 text-xs mb-2">
                <span class="text-lg">📝</span>
                <span>ฉบับร่าง</span>
            </div>
            <div class="text-2xl font-bold text-gray-600"><?php echo number_format($stats['draft']); ?></div>
            <div class="text-xs text-gray-500 mt-1">Draft</div>
        </div>

        <!-- แปลบางส่วน -->
        <div class="bg-white p-5 rounded-lg shadow-sm border border-blue-200 bg-blue-50 cursor-pointer hover:shadow-md transition-shadow" onclick="switchView('tasks')">

            <div class="flex items-center gap-2 text-blue-600 text-xs mb-2">
                <span class="text-lg">🔵</span>
                <span>แปลบางส่วน</span>
            </div>
            <div class="text-2xl font-bold text-blue-600"><?php echo number_format($stats['partial']); ?></div>
            <div class="text-xs text-blue-500 mt-1">Partial</div>
        </div>

        <!-- แปลครบแล้ว -->
        <div class="bg-white p-5 rounded-lg shadow-sm border border-green-200 bg-green-50 cursor-pointer hover:shadow-md transition-shadow" onclick="switchView('translated')">

            <div class="flex items-center gap-2 text-green-600 text-xs mb-2">
                <span class="text-lg">✅</span>
                <span>แปลครบแล้ว</span>
            </div>
            <div class="text-2xl font-bold text-green-600"><?php echo number_format($stats['translated']); ?></div>
            <div class="text-xs text-green-500 mt-1">Translated</div>
        </div>

        <!-- ต้องอัพเดท -->
        <div class="bg-white p-5 rounded-lg shadow-sm border border-orange-200 bg-orange-50 cursor-pointer hover:shadow-md transition-shadow" onclick="switchView('tasks')">

            <div class="flex items-center gap-2 text-orange-600 text-xs mb-2">
                <span class="text-lg">🟠</span>
                <span>ต้องอัพเดท</span>
            </div>
            <div class="text-2xl font-bold text-orange-600"><?php echo number_format($stats['needs_update']); ?></div>
            <div class="text-xs text-orange-500 mt-1">Needs Update</div>
        </div>
    </div>

    <!-- Charts and Data Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Monthly Trends Chart -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4">Monthly Translation Trends</h3>
            <div class="h-64">
                <!-- Simple Bar Chart -->
                <div class="flex items-end justify-between h-full gap-2">
                    <?php 
                    $max_value = max(array_column($monthly_trends, 'translations'));
                    foreach ($monthly_trends as $trend) : 
                        $height = ($trend['translations'] / $max_value) * 100;
                    ?>
                        <div class="flex-1 flex flex-col items-center gap-2">
                            <div class="w-full bg-gov-500 rounded-t hover:bg-gov-600 transition-colors cursor-pointer relative group" style="height: <?php echo $height; ?>%">
                                <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                    <?php echo $trend['translations']; ?> translations
                                </div>
                            </div>
                            <span class="text-xs text-gray-600 font-medium"><?php echo $trend['month']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Language Distribution -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4">Language Distribution</h3>
            <div class="space-y-4">
                <?php 
                $total_lang_count = array_sum(array_column($language_distribution, 'count'));
                foreach ($language_distribution as $lang) : 
                    $percentage = $total_lang_count > 0 ? ($lang['count'] / $total_lang_count) * 100 : 0;
                ?>
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-medium text-gray-700"><?php echo $lang['name']; ?></span>
                            <span class="text-sm text-gray-500"><?php echo number_format($lang['count']); ?> (<?php echo round($percentage); ?>%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="h-2 rounded-full transition-all" style="width: <?php echo $percentage; ?>%; background-color: <?php echo $lang['color']; ?>"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Bottom Row: Categories and Recent Translations -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Most Translated Categories -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4">Top Categories</h3>
            <div class="space-y-3">
                <?php foreach ($top_categories as $index => $category) : ?>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-6 h-6 rounded-full bg-gov-100 text-gov-600 flex items-center justify-center text-xs font-semibold">
                                <?php echo $index + 1; ?>
                            </div>
                            <span class="text-sm text-gray-700"><?php echo $category['name']; ?></span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900"><?php echo $category['count']; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- API Quota + Recent Translations -->
        <div class="lg:col-span-3 space-y-6">
            <!-- API Quota -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-base font-semibold text-gray-800 mb-4">API Quota Usage</h3>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-gray-600">Used</span>
                    <span class="text-sm font-semibold text-gov-600"><?php echo number_format($ai_credits_used); ?> / <?php echo number_format($ai_credits_limit); ?></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-gov-500 h-2 rounded-full transition-all" style="width: <?php echo ($ai_credits_used / $ai_credits_limit) * 100; ?>%"></div>
                </div>
                <p class="text-xs text-gray-500 mt-2">Tokens consumed for AI translations</p>
            </div>

            <!-- Recent Translations -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-800">Recent Translations</h3>
                    <a href="#" onclick="switchView('translated'); return false;" class="text-sm text-gov-600 hover:text-gov-700 font-medium">View All</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Post Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Languages</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php 
                            // Merge and Sort by Modified Date
                            // รวมข้อมูล Pages และ Posts แล้วเรียงลำดับตามวันที่แก้ไขล่าสุด (post_modified)
                            // เพื่อให้รายการที่เพิ่งแปลหรือแก้ไข แสดงขึ้นมาเป็นอันดับแรก
                            $recent_items = array_merge($translated_pages, $translated_posts);
                            usort($recent_items, function($a, $b) {
                                return strtotime($b->post_modified) - strtotime($a->post_modified);
                            });
                            
                            $recent_items = array_slice($recent_items, 0, 5);
                            
                            if (!empty($recent_items)) : 
                                foreach ($recent_items as $item) : 
                            ?>

                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-3">
                                        <div class="text-sm font-medium text-gray-900 truncate max-w-xs"><?php echo esc_html($item->post_title); ?></div>
                                    </td>
                                    <td class="px-6 py-3">
                                        <?php 
                                        // ตรวจสอบสถานะการแปล
                                        $translation_status = new \GovHybridTranslator\Core\TranslationStatus();
                                        $status = $translation_status->get_status($item->ID);
                                        $label = \GovHybridTranslator\Core\TranslationStatus::get_status_label($status);
                                        
                                        $status_class = 'bg-gray-100 text-gray-800'; // Default
                                        if ($status === 'translated') $status_class = 'bg-green-100 text-green-800';
                                        elseif ($status === 'draft') $status_class = 'bg-gray-200 text-gray-800 border border-gray-300';
                                        elseif ($status === 'partial') $status_class = 'bg-blue-100 text-blue-800';
                                        elseif ($status === 'pending') $status_class = 'bg-yellow-100 text-yellow-800';
                                        elseif ($status === 'needs_update') $status_class = 'bg-orange-100 text-orange-800';
                                        ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?php echo $status_class; ?>"><?php echo esc_html($label['label']); ?></span>
                                    </td>
                                    <td class="px-6 py-3 text-sm text-gray-500">
                                        <?php 
                                        // นับ languages ที่มีการแปลแล้ว
                                        $langs_translated = [];
                                        if (class_exists('\GovHybridTranslator\Core\TranslationMeta')) {
                                            $target_languages = $settings['target_languages'] ?? ['en'];
                                            foreach ($target_languages as $lang) {
                                                $title = \GovHybridTranslator\Core\TranslationMeta::get_title($item->ID, $lang);
                                                if (!empty($title)) {
                                                    $langs_translated[] = $lang;
                                                }
                                            }
                                        }
                                        echo count($langs_translated) . ' lang(s)';
                                        ?>
                                    </td>
                                    <td class="px-6 py-3 text-sm text-gray-500"><?php echo human_time_diff(strtotime($item->post_modified), current_time('timestamp')) . ' ago'; ?></td>
                                    <td class="px-6 py-3 text-sm">
                                        <a href="<?php echo get_edit_post_link($item->ID); ?>" class="text-gov-600 hover:text-gov-700 font-medium">Edit</a>
                                    </td>
                                </tr>
                            <?php 
                                endforeach;
                            else : 
                            ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">No recent translations</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
