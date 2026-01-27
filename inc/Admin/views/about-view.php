<!-- VIEW: ABOUT -->
<div id="view-about" class="view-section hidden space-y-6">
    <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 text-center">
        <div class="w-20 h-20 bg-gov-100 rounded-full flex items-center justify-center mx-auto mb-4 text-gov-600">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path></svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Gov Hybrid Translator</h2>
        <p class="text-gray-500 mb-6">Version 1.2.0</p>
        <p class="text-gray-600 max-w-2xl mx-auto mb-8">
            ปลั๊กอินสำหรับแปลภาษาเว็บไซต์หน่วยงานราชการ ด้วยระบบ Hybrid (Google Translate + Custom Glossary) 
            ช่วยให้การสื่อสารข้อมูลภาครัฐเข้าถึงชาวต่างชาติได้อย่างถูกต้องและแม่นยำ
        </p>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-left max-w-4xl mx-auto">
            <div class="bg-gray-50 p-6 rounded-lg">
                <h3 class="font-bold text-gray-800 mb-3">✨ Key Features</h3>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li>• Hybrid Translation (AI + Manual)</li>
                    <li>• Custom Glossary Support</li>
                    <li>• One Post per Language Architecture</li>
                    <li>• SEO-Friendly URLs</li>
                    <li>• Language Switcher Widget</li>
                </ul>
            </div>
            
            <div class="bg-gray-50 p-6 rounded-lg">
                <h3 class="font-bold text-gray-800 mb-3">📖 How to Use</h3>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li>1. Go to <strong>Tasks</strong> to view untranslated content</li>
                    <li>2. Click <strong>Translate Now</strong> to start translation</li>
                    <li>3. Review and edit translations</li>
                    <li>4. Manage glossary terms for consistency</li>
                    <li>5. Monitor usage in <strong>Overview</strong></li>
                </ul>
            </div>
        </div>

        <div class="mt-8 pt-6 border-t border-gray-200">
            <p class="text-sm text-gray-500">
                Developed by <strong>Gov Tech Team</strong> | 
                <a href="<?php echo esc_url(plugin_dir_url(GOV_HYBRID_TRANSLATOR_FILE) . 'docs/USER_GUIDE.html'); ?>" target="_blank" class="text-gov-600 hover:text-gov-700">Documentation</a> | 
                <a href="https://zone4.oae.go.th/" class="text-gov-600 hover:text-gov-700">Support</a>
            </p>
        </div>
    </div>
</div>
