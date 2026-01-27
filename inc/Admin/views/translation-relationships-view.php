<?php
/**
 * Translation Relationships View
 * 
 * แสดงความสัมพันธ์ระหว่างโพสต์ต้นฉบับและฉบับแปล
 * ให้ผู้ใช้ดูว่าแต่ละโพสต์มีการแปลไปภาษาใดบ้าง
 * 
 * @package GovHybridTranslator
 * @since 1.4.0
 */

use GovHybridTranslator\Core\Languages;
use GovHybridTranslator\Modules\Settings;

// ดึงค่า settings
$settings_obj = new Settings();
$source_language = $settings_obj->get_setting('source_language', 'th');
$target_languages = $settings_obj->get_setting('target_languages', ['en']);

// ดึง Translation Groups จาก Posts และ Pages
$translation_groups = [];

// Query posts ที่มี group_id (เป็นต้นฉบับหรือเป็นการแปล)
$args = [
    'post_type' => ['post', 'page'],
    'posts_per_page' => -1,
    'post_status' => ['publish', 'draft', 'pending'],
    'meta_query' => [
        [
            'key' => '_gov_translator_group_id',
            'compare' => 'EXISTS'
        ]
    ]
];

$query = new WP_Query($args);

if ($query->have_posts()) {
    while ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();
        $group_id = get_post_meta($post_id, '_gov_translator_group_id', true);
        $lang = get_post_meta($post_id, '_gov_translator_lang', true) ?: $source_language;
        $original_id = get_post_meta($post_id, '_gov_translator_original_id', true);
        
        if (!isset($translation_groups[$group_id])) {
            $translation_groups[$group_id] = [
                'group_id' => $group_id,
                'post_type' => get_post_type(),
                'languages' => []
            ];
        }
        
        $translation_groups[$group_id]['languages'][$lang] = [
            'post_id' => $post_id,
            'title' => get_the_title(),
            'status' => get_post_status(),
            'edit_link' => get_edit_post_link($post_id),
            'view_link' => get_permalink($post_id),
            'is_original' => empty($original_id)
        ];
        
        // ถ้าเป็นต้นฉบับ ให้เก็บข้อมูลเพิ่มเติม
        if (empty($original_id)) {
            $translation_groups[$group_id]['original_title'] = get_the_title();
            $translation_groups[$group_id]['original_id'] = $post_id;
        }
    }
    wp_reset_postdata();
}

// Filter เอาเฉพาะ groups ที่มีมากกว่า 1 ภาษา หรือแสดงทั้งหมด (ตาม filter)
$show_all = isset($_GET['show_all']) && $_GET['show_all'] === '1';
if (!$show_all) {
    $translation_groups = array_filter($translation_groups, function($group) {
        return count($group['languages']) > 1;
    });
}

// นับสถิติ
$total_groups = count($translation_groups);
$groups_with_full_translation = 0;
$groups_need_translation = 0;

foreach ($translation_groups as $group) {
    $has_all_langs = count($group['languages']) >= count($target_languages) + 1; // +1 for source
    if ($has_all_langs) {
        $groups_with_full_translation++;
    } else {
        $groups_need_translation++;
    }
}
?>

<!-- VIEW: RELATIONSHIPS -->
<div id="view-relationships" class="view-section hidden space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-800">Translation Relationships</h2>
        <div class="flex items-center gap-3">
            <!-- Filter Dropdown -->
            <select id="relationships-filter" onchange="filterRelationships(this.value)" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gov-500 focus:border-transparent">
                <option value="all">All Groups</option>
                <option value="complete">Complete (All Languages)</option>
                <option value="incomplete">Incomplete (Needs Translation)</option>
            </select>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $total_groups; ?></p>
                    <p class="text-sm text-gray-500">Translation Groups</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-green-600"><?php echo $groups_with_full_translation; ?></p>
                    <p class="text-sm text-gray-500">Complete</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-amber-600"><?php echo $groups_need_translation; ?></p>
                    <p class="text-sm text-gray-500">Needs Translation</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Translation Groups Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="px-6 py-3 font-medium">Original Content</th>
                    <th class="px-6 py-3 font-medium">Type</th>
                    <?php 
                    // แสดง header สำหรับ source language
                    $source_info = Languages::get_language($source_language);
                    ?>
                    <th class="px-6 py-3 font-medium text-center">
                        <?php echo $source_info ? $source_info['flag'] . ' ' . strtoupper($source_language) : strtoupper($source_language); ?>
                    </th>
                    <?php foreach ($target_languages as $lang_code): 
                        $lang_info = Languages::get_language($lang_code);
                    ?>
                    <th class="px-6 py-3 font-medium text-center">
                        <?php echo $lang_info ? $lang_info['flag'] . ' ' . strtoupper($lang_code) : strtoupper($lang_code); ?>
                    </th>
                    <?php endforeach; ?>
                    <th class="px-6 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody id="relationships-table-body" class="divide-y divide-gray-100 text-sm">
                <?php if (!empty($translation_groups)) : ?>
                    <?php foreach ($translation_groups as $group_id => $group) : 
                        $lang_count = count($group['languages']);
                        $total_langs = count($target_languages) + 1;
                        $is_complete = $lang_count >= $total_langs;
                    ?>
                        <tr class="hover:bg-gray-50 transition-colors relationship-row" 
                            data-complete="<?php echo $is_complete ? '1' : '0'; ?>">
                            <td class="px-6 py-4">
                                <div>
                                    <p class="font-medium text-gray-800"><?php echo esc_html($group['original_title'] ?? 'Unknown'); ?></p>
                                    <p class="text-xs text-gray-400">Group: <?php echo esc_html(substr($group_id, 0, 15)); ?>...</p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full <?php echo $group['post_type'] === 'page' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'; ?>">
                                    <?php echo ucfirst($group['post_type']); ?>
                                </span>
                            </td>
                            
                            <!-- Source Language Column -->
                            <td class="px-6 py-4 text-center">
                                <?php if (isset($group['languages'][$source_language])) : 
                                    $lang_post = $group['languages'][$source_language];
                                ?>
                                    <a href="<?php echo esc_url($lang_post['edit_link']); ?>" class="inline-flex items-center gap-1 text-gov-600 hover:text-gov-700">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span class="text-xs"><?php echo ucfirst($lang_post['status']); ?></span>
                                    </a>
                                <?php else : ?>
                                    <span class="text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Target Language Columns -->
                            <?php foreach ($target_languages as $lang_code) : ?>
                            <td class="px-6 py-4 text-center">
                                <?php if (isset($group['languages'][$lang_code])) : 
                                    $lang_post = $group['languages'][$lang_code];
                                    $status_class = $lang_post['status'] === 'publish' ? 'text-green-600' : 'text-amber-600';
                                ?>
                                    <a href="<?php echo esc_url($lang_post['edit_link']); ?>" class="inline-flex items-center gap-1 <?php echo $status_class; ?> hover:opacity-80">
                                        <?php if ($lang_post['status'] === 'publish') : ?>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        <?php else : ?>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        <?php endif; ?>
                                        <span class="text-xs"><?php echo ucfirst($lang_post['status']); ?></span>
                                    </a>
                                <?php else : ?>
                                    <button onclick="translateToLanguage('<?php echo esc_attr($group['original_id'] ?? ''); ?>', '<?php echo esc_attr($lang_code); ?>')" 
                                            class="text-gray-400 hover:text-gov-600 transition-colors" 
                                            title="Translate to <?php echo strtoupper($lang_code); ?>">
                                        <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                    </button>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                            
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <?php if (!empty($group['original_id'])) : ?>
                                    <a href="<?php echo get_edit_post_link($group['original_id']); ?>" 
                                       class="text-gray-500 hover:text-gov-600 transition-colors" 
                                       title="Edit Original">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <a href="<?php echo get_permalink($group['original_id']); ?>" 
                                       target="_blank"
                                       class="text-gray-500 hover:text-gov-600 transition-colors" 
                                       title="View Original">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="<?php echo count($target_languages) + 4; ?>" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <p class="text-gray-500">No translation groups found</p>
                                <p class="text-sm text-gray-400">Start translating content to see relationships here</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Filter relationships by completion status
function filterRelationships(filter) {
    const rows = document.querySelectorAll('.relationship-row');
    rows.forEach(row => {
        const isComplete = row.dataset.complete === '1';
        if (filter === 'all') {
            row.style.display = '';
        } else if (filter === 'complete') {
            row.style.display = isComplete ? '' : 'none';
        } else if (filter === 'incomplete') {
            row.style.display = !isComplete ? '' : 'none';
        }
    });
}

// Translate post to specific language
function translateToLanguage(postId, targetLang) {
    if (!postId) {
        showNotification('Cannot find original post', 'error');
        return;
    }
    
    // Show loading
    const btn = event.target.closest('button');
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<svg class="w-5 h-5 animate-spin mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
    btn.disabled = true;
    
    const formData = new FormData();
    formData.append('action', 'ght_translate_to_language');
    formData.append('nonce', '<?php echo wp_create_nonce('ght_translate_to_language'); ?>');
    formData.append('post_id', postId);
    formData.append('target_lang', targetLang);
    
    fetch(ajaxurl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Translation created successfully!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            btn.innerHTML = originalHTML;
            btn.disabled = false;
            showNotification(data.data?.message || 'Error translating', 'error');
        }
    })
    .catch(err => {
        btn.innerHTML = originalHTML;
        btn.disabled = false;
        showNotification('Error translating', 'error');
    });
}
</script>
