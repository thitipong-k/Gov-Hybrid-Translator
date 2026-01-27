<?php
/**
 * Tasks By Category View
 * 
 * แสดงรายการ Posts ที่ยังแปลไม่ครบ จัดกลุ่มตาม Category
 * แต่ละ post แสดง:
 * - รูปภาพ thumbnail
 * - Title (TH และ translations ที่มี)
 * - ภาษาที่ยังขาด
 * - ปุ่ม Translate
 * 
 * @package GovHybridTranslator
 * @since 2.1.1
 */

use GovHybridTranslator\Core\Languages;

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

<!-- Tab: By Category -->
<div id="tab-tasks-content-by-category" class="tab-tasks-content hidden">
    
    <?php if (empty($incomplete_by_category)) : ?>
        <!-- ไม่มีเนื้อหาที่ต้องแปล -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
            <div class="text-gray-400 mb-4">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">All Content Translated!</h3>
            <p class="text-gray-500">ทุกเนื้อหาได้รับการแปลครบทุกภาษาเป้าหมายแล้ว</p>
        </div>
    <?php else : ?>
    
        <!-- Category Sub-Tabs -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-4 overflow-x-auto pb-px" id="category-tabs-nav">
                <?php 
                $first = true;
                foreach ($incomplete_by_category as $slug => $cat_data) : 
                    $category = $cat_data['category'];
                ?>
                    <button 
                        onclick="switchCategoryTab('<?php echo esc_attr($slug); ?>')" 
                        id="cat-tab-btn-<?php echo esc_attr($slug); ?>" 
                        class="<?php echo $first ? 'cat-tab-active' : 'cat-tab-inactive'; ?> whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm rounded-t-lg transition-all">
                        <?php echo esc_html($category->name); ?>
                        <span class="ml-1 px-2 py-0.5 text-xs rounded-full <?php echo $first ? 'bg-gov-600 text-white' : 'bg-gray-200 text-gray-600'; ?>">
                            <?php echo esc_html($cat_data['count']); ?>
                        </span>
                    </button>
                <?php 
                    $first = false;
                endforeach; 
                ?>
            </nav>
        </div>
        
        <!-- Category Content Panels -->
        <?php 
        $first = true;
        foreach ($incomplete_by_category as $slug => $cat_data) : 
            $category = $cat_data['category'];
            $posts = $cat_data['posts'];
        ?>
            <div id="cat-tab-content-<?php echo esc_attr($slug); ?>" 
                 class="cat-tab-content <?php echo $first ? '' : 'hidden'; ?>">
                
                <!-- Category Header -->
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">
                        📁 <?php echo esc_html($category->name); ?>
                        <span class="text-sm font-normal text-gray-500 ml-2">
                            (<?php echo count($posts); ?> posts ยังแปลไม่ครบ)
                        </span>
                    </h3>
                </div>
                
                <!-- Posts List -->
                <div class="space-y-4">
                    <?php foreach ($posts as $item) : 
                        $post = $item['post'];
                        $thumbnail = $item['thumbnail'];
                        $translated_langs = $item['translated_langs'];
                        $missing_langs = $item['missing_langs'];
                    ?>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow"
                             id="post-card-<?php echo $post->ID; ?>">
                            <div class="flex gap-4">
                                <!-- Thumbnail -->
                                <div class="flex-shrink-0">
                                    <?php if ($thumbnail) : ?>
                                        <img src="<?php echo esc_url($thumbnail); ?>" 
                                             alt="<?php echo esc_attr($post->post_title); ?>"
                                             class="w-24 h-24 object-cover rounded-lg">
                                    <?php else : ?>
                                        <div class="w-24 h-24 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Content -->
                                <div class="flex-grow min-w-0">
                                    <!-- Title -->
                                    <h4 class="font-semibold text-gray-800 mb-2 truncate">
                                        <?php echo esc_html($post->post_title); ?>
                                    </h4>
                                    
                                    <!-- Translations Preview -->
                                    <div class="space-y-1 mb-3">
                                        <!-- Thai (Original) -->
                                        <div class="flex items-center gap-2 text-sm">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                🇹🇭 TH
                                            </span>
                                            <span class="text-gray-600 truncate">
                                                <?php echo esc_html(wp_trim_words($post->post_content, 10)); ?>
                                            </span>
                                        </div>
                                        
                                        <!-- Existing Translations -->
                                        <?php foreach ($translated_langs as $lang => $trans) : ?>
                                            <div class="flex items-center gap-2 text-sm">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                    <?php echo esc_html($language_options[$lang]['flag'] ?? ''); ?> 
                                                    <?php echo strtoupper(esc_html($lang)); ?>
                                                    ✓
                                                </span>
                                                <span class="text-gray-600 truncate">
                                                    <?php echo esc_html($trans['excerpt'] ?? $trans['title']); ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <!-- Missing Languages & Actions -->
                                    <div class="flex items-center gap-3 flex-wrap">
                                        <span class="text-sm text-gray-500">ยังไม่แปล:</span>
                                        <?php foreach ($missing_langs as $lang) : ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800">
                                                <?php echo esc_html($language_options[$lang]['flag'] ?? ''); ?> 
                                                <?php echo strtoupper(esc_html($lang)); ?>
                                            </span>
                                        <?php endforeach; ?>
                                        
                                        <!-- Translate Button -->
                                        <div class="ml-auto flex items-center gap-2">
                                            <select id="cat-lang-select-<?php echo $post->ID; ?>" 
                                                    class="text-sm p-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500 focus:border-transparent">
                                                <?php foreach ($missing_langs as $lang) : ?>
                                                    <option value="<?php echo esc_attr($lang); ?>">
                                                        <?php echo esc_html(($language_options[$lang]['flag'] ?? '') . ' ' . ($language_options[$lang]['name'] ?? strtoupper($lang))); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button 
                                                onclick="translateFromCategory(<?php echo $post->ID; ?>, '<?php echo esc_attr($slug); ?>')"
                                                class="text-white bg-gov-600 hover:bg-gov-700 px-4 py-1.5 rounded-lg text-sm font-medium transition-colors flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                        d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                                </svg>
                                                Translate
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php 
            $first = false;
        endforeach; 
        ?>
    
    <?php endif; ?>
</div>

<style>
/* Category Tab Styles */
.cat-tab-active {
    border-color: #0066b3;
    color: #0066b3;
    background-color: #f0f9ff;
}

.cat-tab-inactive {
    border-color: transparent;
    color: #6b7280;
}

.cat-tab-inactive:hover {
    border-color: #d1d5db;
    color: #374151;
}
</style>

<script>
/**
 * สลับ Category Tab
 */
function switchCategoryTab(slug) {
    // ซ่อน content ทั้งหมด
    document.querySelectorAll('.cat-tab-content').forEach(el => el.classList.add('hidden'));
    
    // ลบ active state จากทุก tab
    document.querySelectorAll('#category-tabs-nav button').forEach(btn => {
        btn.classList.remove('cat-tab-active');
        btn.classList.add('cat-tab-inactive');
        const badge = btn.querySelector('span.rounded-full');
        if (badge) {
            badge.classList.remove('bg-gov-600', 'text-white');
            badge.classList.add('bg-gray-200', 'text-gray-600');
        }
    });
    
    // แสดง content ที่เลือก
    const targetContent = document.getElementById('cat-tab-content-' + slug);
    if (targetContent) {
        targetContent.classList.remove('hidden');
    }
    
    // Set active tab
    const targetBtn = document.getElementById('cat-tab-btn-' + slug);
    if (targetBtn) {
        targetBtn.classList.remove('cat-tab-inactive');
        targetBtn.classList.add('cat-tab-active');
        const badge = targetBtn.querySelector('span.rounded-full');
        if (badge) {
            badge.classList.remove('bg-gray-200', 'text-gray-600');
            badge.classList.add('bg-gov-600', 'text-white');
        }
    }
}

/**
 * แปล Post จากหน้า Category
 */
function translateFromCategory(postId, categorySlug) {
    const langSelect = document.getElementById('cat-lang-select-' + postId);
    const targetLang = langSelect ? langSelect.value : 'en';
    const btn = event.target.closest('button');
    const originalHtml = btn.innerHTML;
    const card = document.getElementById('post-card-' + postId);

    btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="4" stroke="currentColor" class="opacity-25"></circle><path stroke-width="4" d="M4 12a8 8 0 018-8" class="opacity-75"></path></svg> Translating...';
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
            btn.innerHTML = '✓ Translated';
            btn.classList.remove('bg-gov-600', 'hover:bg-gov-700');
            btn.classList.add('bg-green-500', 'hover:bg-green-600');
            
            if (typeof showNotification === 'function') {
                showNotification('Translation created successfully!', 'success');
            }
            
            // ลบ option ภาษาที่แปลแล้ว
            const option = langSelect.querySelector('option[value="' + targetLang + '"]');
            if (option) option.remove();
            
            // ถ้าไม่มี option เหลือ = แปลครบ → ซ่อน card
            if (langSelect.options.length === 0) {
                card.style.transition = 'opacity 0.3s, transform 0.3s';
                card.style.opacity = '0';
                card.style.transform = 'translateX(20px)';
                
                setTimeout(() => {
                    card.remove();
                    
                    // อัพเดท counter ใน category tab
                    const tabBtn = document.getElementById('cat-tab-btn-' + categorySlug);
                    if (tabBtn) {
                        const badge = tabBtn.querySelector('span.rounded-full');
                        if (badge) {
                            const currentCount = parseInt(badge.textContent) || 0;
                            badge.textContent = Math.max(0, currentCount - 1);
                        }
                    }
                    
                    // อัพเดท counter ใน main tab
                    const mainTabBtn = document.getElementById('tab-tasks-btn-by-category');
                    if (mainTabBtn) {
                        const match = mainTabBtn.innerText.match(/\((\d+)\)/);
                        if (match) {
                            const newCount = Math.max(0, parseInt(match[1]) - 1);
                            mainTabBtn.innerText = mainTabBtn.innerText.replace(/\(\d+\)/, '(' + newCount + ')');
                        }
                    }
                }, 300);
            } else {
                // ยังมีภาษาเหลือ → reset button
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    btn.classList.remove('bg-green-500', 'hover:bg-green-600');
                    btn.classList.add('bg-gov-600', 'hover:bg-gov-700');
                    btn.disabled = false;
                }, 1500);
            }
        } else {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            if (typeof showNotification === 'function') {
                showNotification(data.data?.message || 'Error translating', 'error');
            }
        }
    })
    .catch(err => {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
        if (typeof showNotification === 'function') {
            showNotification('Error translating', 'error');
        }
    });
}
</script>
