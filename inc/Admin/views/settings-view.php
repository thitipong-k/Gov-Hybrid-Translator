<?php
/**
 * Settings View - หน้าตั้งค่า Plugin
 * 
 * แสดง tabs สำหรับตั้งค่าต่างๆ:
 * - General, AI & Translation, Content & SEO
 * - Language Switcher, Advanced, Permissions
 * 
 * @package GovHybridTranslator
 * @since 1.0.0
 * @updated 1.5.0 - เพิ่ม Permissions tab
 */
?>
<!-- VIEW: SETTINGS -->
<div id="view-settings" class="view-section hidden space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-800">Settings</h2>
        <button onclick="saveSettings()" class="settings-save-btn bg-gov-600 hover:bg-gov-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
            Save Changes
        </button>
    </div>

    <!-- Settings Tabs -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px overflow-x-auto">
                <button onclick="switchSettingsTab('general')" id="settings-tab-general" class="settings-tab-active whitespace-nowrap px-6 py-4 border-b-2 font-medium text-sm">
                    General
                </button>
                <button onclick="switchSettingsTab('ai')" id="settings-tab-ai" class="settings-tab-inactive whitespace-nowrap px-6 py-4 border-b-2 font-medium text-sm">
                    AI & Translation
                </button>
                <button onclick="switchSettingsTab('content')" id="settings-tab-content" class="settings-tab-inactive whitespace-nowrap px-6 py-4 border-b-2 font-medium text-sm">
                    Content & SEO
                </button>
                <button onclick="switchSettingsTab('switcher')" id="settings-tab-switcher" class="settings-tab-inactive whitespace-nowrap px-6 py-4 border-b-2 font-medium text-sm">
                    Language Switcher
                </button>
                <button onclick="switchSettingsTab('advanced')" id="settings-tab-advanced" class="settings-tab-inactive whitespace-nowrap px-6 py-4 border-b-2 font-medium text-sm">
                    Advanced
                </button>
                <button onclick="switchSettingsTab('permissions')" id="settings-tab-permissions" class="settings-tab-inactive whitespace-nowrap px-6 py-4 border-b-2 font-medium text-sm">
                    Permissions
                </button>
            </nav>
        </div>

        <!-- Tab Content - ครอบด้วย form เพื่อให้ saveSettings เก็บค่าได้ -->
        <form id="settings-form" onsubmit="return false;">
            <div class="p-8">
                <?php
                // Include separate settings tab files
                require GOV_HYBRID_TRANSLATOR_PATH . 'inc/Admin/views/settings/settings-general.php';
                require GOV_HYBRID_TRANSLATOR_PATH . 'inc/Admin/views/settings/settings-ai-translation.php';
                require GOV_HYBRID_TRANSLATOR_PATH . 'inc/Admin/views/settings/settings-content-seo.php';
                require GOV_HYBRID_TRANSLATOR_PATH . 'inc/Admin/views/settings/settings-language-switcher.php';
                require GOV_HYBRID_TRANSLATOR_PATH . 'inc/Admin/views/settings/settings-advanced.php';
                require GOV_HYBRID_TRANSLATOR_PATH . 'inc/Admin/views/settings/settings-permissions.php';
                ?>
            </div>
        </form>
    </div>
</div>

