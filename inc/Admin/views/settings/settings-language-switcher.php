<?php
/**
 * Language Switcher Settings Tab
 * 
 * การตั้งค่าต่างๆ สำหรับ Language Switcher:
 * - Display Options: ประเภท, แสดง flags/names, แสดงภาษาหลัก
 * - Placement: ตำแหน่งที่แสดง (Floating, Menu, Widget)
 * - Floating Button: ตำแหน่งมุม, ระยะห่างจากขอบ
 * - Behavior: จดจำภาษาผู้ใช้, Auto-redirect
 * 
 * @package GovHybridTranslator
 * @since 1.3.0
 * @updated 1.5.0 - เพิ่มตัวเลือกตำแหน่ง Floating Button
 */
?>
<!-- LANGUAGE SWITCHER SETTINGS TAB -->
<div id="settings-content-switcher" class="settings-tab-content hidden">
    <div class="max-w-3xl space-y-8">
        <!-- Display Options -->
        <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Display Options</h3>
            <div class="space-y-4">
                <!-- Switcher Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Switcher Type (รูปแบบการแสดง)</label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <!-- Dropdown -->
                        <label class="p-4 border <?php echo ($settings['switcher_type'] ?? 'flags') === 'dropdown' ? 'border-gov-500 bg-gov-50' : 'border-gray-200'; ?> rounded-lg hover:bg-gray-50 cursor-pointer text-center">
                            <input type="radio" name="switcher_type" value="dropdown" <?php checked($settings['switcher_type'] ?? 'flags', 'dropdown'); ?> class="sr-only">
                            <div class="text-2xl mb-2">🌐</div>
                            <span class="text-sm font-medium">Dropdown</span>
                            <p class="text-xs text-gray-500 mt-1">เมนูเลือก</p>
                        </label>
                        <!-- Flags -->
                        <label class="p-4 border <?php echo ($settings['switcher_type'] ?? 'flags') === 'flags' ? 'border-gov-500 bg-gov-50' : 'border-gray-200'; ?> rounded-lg cursor-pointer text-center">
                            <input type="radio" name="switcher_type" value="flags" <?php checked($settings['switcher_type'] ?? 'flags', 'flags'); ?> class="sr-only">
                            <div class="text-2xl mb-2">🚩</div>
                            <span class="text-sm font-medium">Flags</span>
                            <p class="text-xs text-gray-500 mt-1">ธงทีละอัน</p>
                        </label>
                        <!-- Flag Pair (ธงคู่สลับภาษา) -->
                        <label class="p-4 border <?php echo ($settings['switcher_type'] ?? 'flags') === 'flag_pair' ? 'border-gov-500 bg-gov-50' : 'border-gray-200'; ?> rounded-lg hover:bg-gray-50 cursor-pointer text-center">
                            <input type="radio" name="switcher_type" value="flag_pair" <?php checked($settings['switcher_type'] ?? 'flags', 'flag_pair'); ?> class="sr-only">
                            <div class="text-2xl mb-2">🇹🇭⟷🇬🇧</div>
                            <span class="text-sm font-medium">Flag Pair</span>
                            <p class="text-xs text-gray-500 mt-1">ธงคู่สลับ</p>
                        </label>
                        <!-- Text -->
                        <label class="p-4 border <?php echo ($settings['switcher_type'] ?? 'flags') === 'text' ? 'border-gov-500 bg-gov-50' : 'border-gray-200'; ?> rounded-lg hover:bg-gray-50 cursor-pointer text-center">
                            <input type="radio" name="switcher_type" value="text" <?php checked($settings['switcher_type'] ?? 'flags', 'text'); ?> class="sr-only">
                            <div class="text-2xl mb-2">Aa</div>
                            <span class="text-sm font-medium">Text</span>
                            <p class="text-xs text-gray-500 mt-1">ตัวอักษร</p>
                        </label>
                    </div>
                </div>
                
                <!-- Display Checkboxes -->
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="show_flags" value="1" <?php checked($settings['show_flags'] ?? true, true); ?> class="w-4 h-4 text-gov-600 rounded">
                    <span class="text-sm text-gray-700">Show flag icons (แสดงธงชาติ)</span>
                </label>
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="show_names" value="1" <?php checked($settings['show_names'] ?? true, true); ?> class="w-4 h-4 text-gov-600 rounded">
                    <span class="text-sm text-gray-700">Show language names (แสดงชื่อภาษา)</span>
                </label>
                <!-- แสดงภาษาหลัก (Source Language) -->
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="show_source_lang" value="1" <?php checked($settings['show_source_lang'] ?? true, true); ?> class="w-4 h-4 text-gov-600 rounded">
                    <span class="text-sm text-gray-700">Show source language (แสดงภาษาหลัก/ภาษาไทย)</span>
                </label>
            </div>
        </div>

        <!-- Placement -->
        <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Placement (ตำแหน่งที่แสดง)</h3>
            <div class="space-y-2">
                <?php
                $placement = $settings['placement'] ?? ['floating', 'menu'];
                ?>
                <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                    <input type="checkbox" name="placement[]" value="floating" <?php checked(in_array('floating', $placement), true); ?> class="w-4 h-4 text-gov-600 rounded" id="placement_floating">
                    <span class="text-sm text-gray-700">Floating Button (ปุ่มลอยบนหน้าจอ)</span>
                </label>
                <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                    <input type="checkbox" name="placement[]" value="menu" <?php checked(in_array('menu', $placement), true); ?> class="w-4 h-4 text-gov-600 rounded">
                    <span class="text-sm text-gray-700">Navigation Menu (เมนูนำทาง)</span>
                </label>
                <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                    <input type="checkbox" name="placement[]" value="widget" <?php checked(in_array('widget', $placement), true); ?> class="w-4 h-4 text-gov-600 rounded">
                    <span class="text-sm text-gray-700">Widget Area (พื้นที่ Widget)</span>
                </label>
            </div>
        </div>

        <!-- Floating Button Settings -->
        <div id="floating-button-settings" class="p-4 bg-gray-50 rounded-lg border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Floating Button Settings</h3>
            
            <!-- Position -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">ตำแหน่งมุม (Position)</label>
                <?php $floating_position = $settings['floating_position'] ?? 'bottom-right'; ?>
                <div class="grid grid-cols-2 gap-3">
                    <label class="p-3 border <?php echo $floating_position === 'top-left' ? 'border-gov-500 bg-gov-50' : 'border-gray-200'; ?> rounded-lg hover:bg-gray-50 cursor-pointer text-center">
                        <input type="radio" name="floating_position" value="top-left" <?php checked($floating_position, 'top-left'); ?> class="sr-only">
                        <span class="text-sm font-medium">↖ บนซ้าย</span>
                    </label>
                    <label class="p-3 border <?php echo $floating_position === 'top-right' ? 'border-gov-500 bg-gov-50' : 'border-gray-200'; ?> rounded-lg hover:bg-gray-50 cursor-pointer text-center">
                        <input type="radio" name="floating_position" value="top-right" <?php checked($floating_position, 'top-right'); ?> class="sr-only">
                        <span class="text-sm font-medium">↗ บนขวา</span>
                    </label>
                    <label class="p-3 border <?php echo $floating_position === 'bottom-left' ? 'border-gov-500 bg-gov-50' : 'border-gray-200'; ?> rounded-lg hover:bg-gray-50 cursor-pointer text-center">
                        <input type="radio" name="floating_position" value="bottom-left" <?php checked($floating_position, 'bottom-left'); ?> class="sr-only">
                        <span class="text-sm font-medium">↙ ล่างซ้าย</span>
                    </label>
                    <label class="p-3 border <?php echo $floating_position === 'bottom-right' ? 'border-gov-500 bg-gov-50' : 'border-gray-200'; ?> rounded-lg hover:bg-gray-50 cursor-pointer text-center">
                        <input type="radio" name="floating_position" value="bottom-right" <?php checked($floating_position, 'bottom-right'); ?> class="sr-only">
                        <span class="text-sm font-medium">↘ ล่างขวา</span>
                    </label>
                </div>
            </div>
            
            <!-- Margin from Edge -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ระยะห่างแนวนอน (Horizontal)</label>
                    <div class="flex items-center gap-2">
                        <input type="number" name="floating_margin_x" 
                               value="<?php echo esc_attr($settings['floating_margin_x'] ?? 20); ?>" 
                               min="0" max="100" step="5"
                               class="w-20 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <span class="text-sm text-gray-500">px</span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ระยะห่างแนวตั้ง (Vertical)</label>
                    <div class="flex items-center gap-2">
                        <input type="number" name="floating_margin_y" 
                               value="<?php echo esc_attr($settings['floating_margin_y'] ?? 20); ?>" 
                               min="0" max="100" step="5"
                               class="w-20 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <span class="text-sm text-gray-500">px</span>
                    </div>
                </div>
            </div>
            
            <!-- Preview -->
            <div class="mt-4 p-3 bg-white rounded border border-gray-200">
                <p class="text-xs text-gray-500 mb-2">Preview (ตัวอย่าง)</p>
                <div class="relative h-24 bg-gray-100 rounded" id="floating-preview">
                    <div id="floating-preview-button" class="absolute bg-gov-600 text-white px-3 py-2 rounded-lg text-xs shadow-lg">
                        🌐 TH
                    </div>
                </div>
            </div>
        </div>

        <!-- Behavior -->
        <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Behavior (พฤติกรรม)</h3>
            <div class="space-y-3">
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="remember_preference" value="1" <?php checked($settings['remember_preference'] ?? true, true); ?> class="w-4 h-4 text-gov-600 rounded">
                    <span class="text-sm text-gray-700">Remember user language preference (จดจำภาษาที่ผู้ใช้เลือก)</span>
                </label>
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="auto_redirect" value="1" <?php checked($settings['auto_redirect'] ?? false, true); ?> class="w-4 h-4 text-gov-600 rounded">
                    <span class="text-sm text-gray-700">Auto-redirect based on browser language (เปลี่ยนภาษาตาม browser)</span>
                </label>
            </div>
        </div>
    </div>
</div>

<!-- Script moved to assets/js/admin-dashboard.js -->


