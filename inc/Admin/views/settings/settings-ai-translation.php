<?php
/**
 * AI & Translation Settings Tab
 * 
 * หน้า Settings สำหรับตั้งค่า AI Provider และ API Keys
 * 
 * ฟีเจอร์:
 * - เลือก AI Provider (Google, OpenAI, DeepL, Azure, Claude)
 * - กรอก API Keys แยกตาม provider
 * - แสดง API Keys ที่บันทึกไว้ (masked)
 * - Test Connection ก่อนบันทึก
 * - Edit/Delete API Keys
 * 
 * ตัวแปรที่ใช้:
 * - $settings: array ของ settings ทั้งหมด (ส่งมาจาก settings-view.php)
 * 
 * @package GovHybridTranslator
 * @since 1.0.0
 * @updated 2024-12-10 - เพิ่ม Multi AI Providers support
 */
?>
<!-- AI & TRANSLATION SETTINGS TAB -->
<div id="settings-content-ai" class="settings-tab-content hidden">
    <div class="max-w-3xl space-y-8">
        <!-- AI Provider -->
        <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">🤖 AI Provider</h3>
            <select name="ai_provider" id="ai_provider" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500">
                <option value="google" <?php selected($settings['ai_provider'] ?? 'google', 'google'); ?>>🔷 Google Cloud Translation API</option>
                <option value="openai" <?php selected($settings['ai_provider'] ?? 'google', 'openai'); ?>>🟢 OpenAI GPT</option>
                <option value="deepl" <?php selected($settings['ai_provider'] ?? 'google', 'deepl'); ?>>🔵 DeepL API</option>
                <option value="azure" <?php selected($settings['ai_provider'] ?? 'google', 'azure'); ?>>🔶 Azure Translator</option>
                <option value="claude" <?php selected($settings['ai_provider'] ?? 'google', 'claude'); ?>>🟣 Anthropic Claude</option>
                <option value="simulator" <?php selected($settings['ai_provider'] ?? 'google', 'simulator'); ?>>⚙️ Simulator (Dev Mode)</option>
            </select>
            <p class="text-xs text-gray-500 mt-2">เลือก AI Provider ที่ต้องการใช้สำหรับการแปลภาษา</p>
        </div>

        <!-- API Credentials -->
        <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">🔑 API Credentials</h3>
            <div class="space-y-4">
                <!-- Google API Key -->
                <div id="field-google" class="provider-field" data-provider="google">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Google API Key</label>
                    <input type="password" name="google_api_key" value="<?php echo esc_attr($settings['google_api_key'] ?? ''); ?>" placeholder="AIza..." class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500">
                    <p class="text-xs text-gray-500 mt-1">API Key จาก Google Cloud Console</p>
                </div>

                <!-- OpenAI API Key -->
                <div id="field-openai" class="provider-field hidden" data-provider="openai">
                    <label class="block text-sm font-medium text-gray-700 mb-2">OpenAI API Key</label>
                    <input type="password" name="openai_api_key" value="<?php echo esc_attr($settings['openai_api_key'] ?? ''); ?>" placeholder="sk-..." class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500">
                    <p class="text-xs text-gray-500 mt-1">API Key จาก OpenAI Platform</p>
                    <label class="block text-sm font-medium text-gray-700 mb-2 mt-4">Model</label>
                    <select name="openai_model" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500">
                        <option value="gpt-3.5-turbo" <?php selected($settings['openai_model'] ?? 'gpt-3.5-turbo', 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo (เร็ว, ประหยัด)</option>
                        <option value="gpt-4" <?php selected($settings['openai_model'] ?? 'gpt-3.5-turbo', 'gpt-4'); ?>>GPT-4 (ดีที่สุด)</option>
                        <option value="gpt-4-turbo-preview" <?php selected($settings['openai_model'] ?? 'gpt-3.5-turbo', 'gpt-4-turbo-preview'); ?>>GPT-4 Turbo</option>
                    </select>
                </div>

                <!-- DeepL API Key -->
                <div id="field-deepl" class="provider-field hidden" data-provider="deepl">
                    <label class="block text-sm font-medium text-gray-700 mb-2">DeepL API Key</label>
                    <input type="password" name="deepl_api_key" value="<?php echo esc_attr($settings['deepl_api_key'] ?? ''); ?>" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx:fx" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500">
                    <p class="text-xs text-gray-500 mt-1">API Key จาก DeepL (Free หรือ Pro)</p>
                    <label class="block text-sm font-medium text-gray-700 mb-2 mt-4">Plan</label>
                    <select name="deepl_plan" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500">
                        <option value="free" <?php selected($settings['deepl_plan'] ?? 'free', 'free'); ?>>Free (500,000 chars/month)</option>
                        <option value="pro" <?php selected($settings['deepl_plan'] ?? 'free', 'pro'); ?>>Pro (Unlimited)</option>
                    </select>
                    <p class="text-xs text-orange-600 mt-2">⚠️ DeepL ไม่รองรับภาษาไทยเป็น source ใช้ได้เฉพาะแปลจากภาษาอื่น</p>
                </div>

                <!-- Azure API Key -->
                <div id="field-azure" class="provider-field hidden" data-provider="azure">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Azure Subscription Key</label>
                    <input type="password" name="azure_api_key" value="<?php echo esc_attr($settings['azure_api_key'] ?? ''); ?>" placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500">
                    <p class="text-xs text-gray-500 mt-1">Subscription Key จาก Azure Portal</p>
                    <label class="block text-sm font-medium text-gray-700 mb-2 mt-4">Region</label>
                    <select name="azure_region" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500">
                        <option value="southeastasia" <?php selected($settings['azure_region'] ?? 'southeastasia', 'southeastasia'); ?>>Southeast Asia (Singapore)</option>
                        <option value="eastasia" <?php selected($settings['azure_region'] ?? 'southeastasia', 'eastasia'); ?>>East Asia (Hong Kong)</option>
                        <option value="japaneast" <?php selected($settings['azure_region'] ?? 'southeastasia', 'japaneast'); ?>>Japan East</option>
                        <option value="koreacentral" <?php selected($settings['azure_region'] ?? 'southeastasia', 'koreacentral'); ?>>Korea Central</option>
                        <option value="westus" <?php selected($settings['azure_region'] ?? 'southeastasia', 'westus'); ?>>West US</option>
                        <option value="eastus" <?php selected($settings['azure_region'] ?? 'southeastasia', 'eastus'); ?>>East US</option>
                        <option value="westeurope" <?php selected($settings['azure_region'] ?? 'southeastasia', 'westeurope'); ?>>West Europe</option>
                    </select>
                </div>

                <!-- Claude API Key -->
                <div id="field-claude" class="provider-field hidden" data-provider="claude">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Claude API Key</label>
                    <input type="password" name="claude_api_key" value="<?php echo esc_attr($settings['claude_api_key'] ?? ''); ?>" placeholder="sk-ant-..." class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500">
                    <p class="text-xs text-gray-500 mt-1">API Key จาก Anthropic Console</p>
                    <label class="block text-sm font-medium text-gray-700 mb-2 mt-4">Model</label>
                    <select name="claude_model" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500">
                        <option value="claude-3-haiku-20240307" <?php selected($settings['claude_model'] ?? 'claude-3-sonnet-20240229', 'claude-3-haiku-20240307'); ?>>Claude 3 Haiku (เร็ว, ประหยัด)</option>
                        <option value="claude-3-sonnet-20240229" <?php selected($settings['claude_model'] ?? 'claude-3-sonnet-20240229', 'claude-3-sonnet-20240229'); ?>>Claude 3 Sonnet (สมดุล)</option>
                        <option value="claude-3-opus-20240229" <?php selected($settings['claude_model'] ?? 'claude-3-sonnet-20240229', 'claude-3-opus-20240229'); ?>>Claude 3 Opus (ดีที่สุด)</option>
                    </select>
                </div>

                <!-- Simulator (no fields needed) -->
                <div id="field-simulator" class="provider-field hidden" data-provider="simulator">
                    <p class="text-sm text-gray-500 bg-gray-50 p-4 rounded-lg">
                        ⚙️ <strong>Simulator Mode</strong> - ใช้สำหรับทดสอบโดยไม่ต้องใช้ API Key<br>
                        ผลลัพธ์จะเป็น "[Simulated] + ข้อความเดิม"
                    </p>
                </div>

                <button type="button" id="btn-test-connection" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    🔗 Test Connection
                </button>
            </div>
        </div>

        <!-- Configured APIs Section -->
        <?php
        // ดึง configured providers จาก settings
        $provider_names = [
            'google' => ['name' => 'Google Cloud Translation', 'icon' => '🔷', 'key_field' => 'google_api_key'],
            'openai' => ['name' => 'OpenAI GPT', 'icon' => '🟢', 'key_field' => 'openai_api_key'],
            'deepl' => ['name' => 'DeepL', 'icon' => '🔵', 'key_field' => 'deepl_api_key'],
            'azure' => ['name' => 'Azure Translator', 'icon' => '🔶', 'key_field' => 'azure_api_key'],
            'claude' => ['name' => 'Anthropic Claude', 'icon' => '🟣', 'key_field' => 'claude_api_key'],
        ];

        // ดึง providers ที่มี API Key
        $configured = [];
        foreach ($provider_names as $slug => $info) {
            $key_field = $info['key_field'];
            $api_key = $settings[$key_field] ?? '';
            
            if (!empty($api_key)) {
                // Mask API Key
                $masked = strlen($api_key) > 8 
                    ? substr($api_key, 0, 4) . str_repeat('•', min(strlen($api_key) - 8, 20)) . substr($api_key, -4)
                    : str_repeat('•', strlen($api_key));
                
                $configured[$slug] = [
                    'masked_key' => $masked,
                    'extra' => [],
                ];
                
                // เพิ่ม extra data
                if ($slug === 'openai' && !empty($settings['openai_model'])) {
                    $configured[$slug]['extra']['model'] = $settings['openai_model'];
                }
                if ($slug === 'deepl' && !empty($settings['deepl_plan'])) {
                    $configured[$slug]['extra']['plan'] = $settings['deepl_plan'];
                }
                if ($slug === 'azure' && !empty($settings['azure_region'])) {
                    $configured[$slug]['extra']['region'] = $settings['azure_region'];
                }
                if ($slug === 'claude' && !empty($settings['claude_model'])) {
                    $configured[$slug]['extra']['model'] = $settings['claude_model'];
                }
            }
        }
        ?>
        
        <?php if (!empty($configured)): ?>
        <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">✅ API Keys ที่ตั้งค่าไว้</h3>
            <div class="space-y-3">
                <?php foreach ($configured as $provider_slug => $data): 
                    $info = $provider_names[$provider_slug] ?? ['name' => ucfirst($provider_slug), 'icon' => '🔑'];
                ?>
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl"><?php echo $info['icon']; ?></span>
                        <div>
                            <p class="font-medium text-gray-800"><?php echo esc_html($info['name']); ?></p>
                            <p class="text-sm text-gray-500 font-mono"><?php echo esc_html($data['masked_key']); ?></p>
                            <?php if (!empty($data['extra'])): ?>
                                <p class="text-xs text-gray-400 mt-1">
                                    <?php 
                                    $extra_items = [];
                                    foreach ($data['extra'] as $key => $value) {
                                        $extra_items[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . $value;
                                    }
                                    echo esc_html(implode(' | ', $extra_items));
                                    ?>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($data['updated_at'])): ?>
                                <p class="text-xs text-gray-400">อัพเดท: <?php echo esc_html($data['updated_at']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" 
                                onclick="editApiKey('<?php echo esc_attr($provider_slug); ?>')"
                                class="text-blue-600 hover:text-blue-800 px-3 py-1 text-sm font-medium hover:bg-blue-50 rounded">
                            ✏️ แก้ไข
                        </button>
                        <button type="button" 
                                onclick="deleteApiKey('<?php echo esc_attr($provider_slug); ?>', '<?php echo esc_attr($info['name']); ?>')"
                                class="text-red-600 hover:text-red-800 px-3 py-1 text-sm font-medium hover:bg-red-50 rounded">
                            🗑️ ลบ
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <p class="text-sm text-yellow-800">
                ⚠️ ยังไม่มี API Key ที่ตั้งค่าไว้ กรุณาเลือก Provider และกรอก API Key ด้านบน
            </p>
        </div>
        <?php endif; ?>

        <script>
        // แก้ไข API Key
        function editApiKey(provider) {
            // เลือก provider ใน dropdown
            document.getElementById('ai_provider').value = provider;
            updateProviderFields();
            
            // Scroll ไปที่ form
            document.getElementById('ai_provider').scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Focus ที่ input
            setTimeout(() => {
                const input = document.querySelector('#field-' + provider + ' input[type="password"]');
                if (input) {
                    input.focus();
                    input.placeholder = 'กรอก API Key ใหม่เพื่ออัพเดท...';
                }
            }, 300);
        }

        // ลบ API Key
        function deleteApiKey(provider, name) {
            if (!confirm('ต้องการลบ API Key ของ ' + name + ' ใช่หรือไม่?')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'ght_delete_api_key');
            formData.append('nonce', '<?php echo wp_create_nonce('ght_save_settings'); ?>');
            formData.append('provider', provider);

            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + (data.data?.message || 'Unknown error'));
                }
            })
            .catch(err => {
                alert('Connection error');
            });
        }
        </script>

        <script>
        // แสดง/ซ่อน fields ตาม provider ที่เลือก
        function updateProviderFields() {
            const provider = document.getElementById('ai_provider').value;
            document.querySelectorAll('.provider-field').forEach(field => {
                if (field.dataset.provider === provider) {
                    field.classList.remove('hidden');
                } else {
                    field.classList.add('hidden');
                }
            });
        }

        // เรียกครั้งแรกและเมื่อเปลี่ยน
        document.addEventListener('DOMContentLoaded', updateProviderFields);
        document.getElementById('ai_provider').addEventListener('change', updateProviderFields);
        </script>
        <script>
        document.getElementById('btn-test-connection').addEventListener('click', function() {
            const btn = this;
            const originalText = btn.innerText;
            const provider = document.getElementById('ai_provider').value;
            
            // ดึง API Key จาก field ที่ตรงกับ provider
            let apiKey = '';
            let extraData = {};
            
            switch (provider) {
                case 'google':
                    apiKey = document.querySelector('input[name="google_api_key"]')?.value || '';
                    break;
                case 'openai':
                    apiKey = document.querySelector('input[name="openai_api_key"]')?.value || '';
                    extraData.openai_model = document.querySelector('select[name="openai_model"]')?.value || 'gpt-3.5-turbo';
                    break;
                case 'deepl':
                    apiKey = document.querySelector('input[name="deepl_api_key"]')?.value || '';
                    extraData.deepl_plan = document.querySelector('select[name="deepl_plan"]')?.value || 'free';
                    break;
                case 'azure':
                    apiKey = document.querySelector('input[name="azure_api_key"]')?.value || '';
                    extraData.azure_region = document.querySelector('select[name="azure_region"]')?.value || 'southeastasia';
                    break;
                case 'claude':
                    apiKey = document.querySelector('input[name="claude_api_key"]')?.value || '';
                    extraData.claude_model = document.querySelector('select[name="claude_model"]')?.value || 'claude-3-sonnet-20240229';
                    break;
                case 'simulator':
                    alert('✅ Simulator mode - ไม่ต้องใช้ API Key');
                    return;
            }

            if (!apiKey) {
                alert('⚠️ กรุณากรอก API Key ก่อน');
                return;
            }

            btn.innerText = '🔄 Testing...';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'ght_test_ai_connection');
            formData.append('nonce', '<?php echo wp_create_nonce('ght_save_settings'); ?>');
            formData.append('provider', provider);
            formData.append(provider + '_api_key', apiKey);
            
            // เพิ่ม extra data
            for (const [key, value] of Object.entries(extraData)) {
                formData.append(key, value);
            }

            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btn.innerText = originalText;
                btn.disabled = false;
                if (data.success) {
                    alert('✅ ' + data.data.message);
                } else {
                    alert('❌ ' + (data.data?.message || 'Connection failed'));
                }
            })
            .catch(err => {
                btn.innerText = originalText;
                btn.disabled = false;
                console.error('Test connection error:', err);
                alert('❌ Connection error. Check console for details.');
            });
        });
        </script>

        <!-- Translation Quality -->
        <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Translation Quality</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Quality Level</label>
                    <div class="flex gap-3">
                        <label class="flex-1 p-3 border <?php echo ($settings['quality_level'] ?? 'high') === 'standard' ? 'border-gov-500 bg-gov-50' : 'border-gray-200'; ?> rounded-lg hover:bg-gray-50 cursor-pointer text-center">
                            <input type="radio" name="quality_level" value="standard" <?php checked($settings['quality_level'] ?? 'high', 'standard'); ?> class="sr-only">
                            <span class="text-sm font-medium">Standard</span>
                        </label>
                        <label class="flex-1 p-3 border <?php echo ($settings['quality_level'] ?? 'high') === 'high' ? 'border-gov-500 bg-gov-50' : 'border-gray-200'; ?> rounded-lg cursor-pointer text-center">
                            <input type="radio" name="quality_level" value="high" <?php checked($settings['quality_level'] ?? 'high', 'high'); ?> class="sr-only">
                            <span class="text-sm font-medium text-gov-700">High</span>
                        </label>
                        <label class="flex-1 p-3 border <?php echo ($settings['quality_level'] ?? 'high') === 'premium' ? 'border-gov-500 bg-gov-50' : 'border-gray-200'; ?> rounded-lg hover:bg-gray-50 cursor-pointer text-center">
                            <input type="radio" name="quality_level" value="premium" <?php checked($settings['quality_level'] ?? 'high', 'premium'); ?> class="sr-only">
                            <span class="text-sm font-medium">Premium</span>
                        </label>
                    </div>
                </div>
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="auto_detect" value="1" <?php checked($settings['auto_detect'] ?? true, true); ?> class="w-4 h-4 text-gov-600 rounded focus:ring-gov-500">
                    <span class="text-sm text-gray-700">Auto-detect language</span>
                </label>
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="preserve_html" value="1" <?php checked($settings['preserve_html'] ?? true, true); ?> class="w-4 h-4 text-gov-600 rounded focus:ring-gov-500">
                    <span class="text-sm text-gray-700">Preserve HTML tags</span>
                </label>
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="preserve_shortcodes" value="1" <?php checked($settings['preserve_shortcodes'] ?? true, true); ?> class="w-4 h-4 text-gov-600 rounded focus:ring-gov-500">
                    <span class="text-sm text-gray-700">Preserve WordPress shortcodes</span>
                </label>
            </div>
        </div>

        <!-- Usage Limits -->
        <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Usage Limits</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Monthly Credit Limit (tokens)</label>
                    <input type="number" name="credit_limit" value="<?php echo esc_attr($settings['credit_limit'] ?? 50000); ?>" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Alert at usage (%)</label>
                    <input type="number" name="alert_threshold" value="<?php echo esc_attr($settings['alert_threshold'] ?? 80); ?>" min="0" max="100" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gov-500">
                </div>
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="auto_pause" value="1" <?php checked($settings['auto_pause'] ?? true, true); ?> class="w-4 h-4 text-gov-600 rounded focus:ring-gov-500">
                    <span class="text-sm text-gray-700">Auto-pause translations when limit is reached</span>
                </label>
            </div>
        </div>
    </div>
</div>
