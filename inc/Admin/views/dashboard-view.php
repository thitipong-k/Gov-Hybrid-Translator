<div class="wrap" style="margin: 0; padding: 0;">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Sarabun', 'sans-serif'] },
                    colors: {
                        gov: { 50: '#f0f9ff', 100: '#e0f2fe', 500: '#0ea5e9', 600: '#0284c7', 700: '#0369a1', 900: '#0c4a6e' }
                    }
                }
            }
        }
    </script>
    <script>
        function switchView(viewName) {
            // Hide all views
            const views = document.querySelectorAll('.view-section');
            views.forEach(view => {
                view.classList.add('hidden');
            });

            // Show selected view
            const targetView = document.getElementById('view-' + viewName);
            if (targetView) {
                targetView.classList.remove('hidden');
            }

            // Update sidebar active state
            const navLinks = document.querySelectorAll('.sidebar-item');
            navLinks.forEach(link => {
                link.classList.remove('active', 'bg-gov-50', 'text-gov-600', 'font-medium');
                link.classList.add('text-gray-600');
                
                // Check if this link corresponds to the view
                if (link.getAttribute('onclick').includes("'" + viewName + "'")) {
                     link.classList.add('active', 'bg-gov-50', 'text-gov-600', 'font-medium');
                     link.classList.remove('text-gray-600');
                }
            });
            
            // Save state (optional)
            localStorage.setItem('ght_current_view', viewName);
        }

        // Restore state on load
        document.addEventListener('DOMContentLoaded', () => {
             const savedView = localStorage.getItem('ght_current_view') || 'overview';
             switchView(savedView);
        });
    </script>
    <style>
        .view-section {
            animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    <div class="flex h-screen overflow-hidden bg-gray-50 text-gray-800 font-sans">
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r border-gray-200 flex flex-col">
            <!-- Logo -->
            <div class="h-16 flex items-center px-6 border-b border-gray-200">
                <div class="flex items-center gap-2">
                    <div class="text-gov-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path></svg>
                    </div>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 p-4 space-y-1">
                <a href="javascript:void(0)" onclick="switchView('overview')" class="sidebar-item active flex items-center gap-3 px-4 py-3 rounded-lg text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    <span>Dashboard</span>
                </a>
                <a href="javascript:void(0)" onclick="switchView('tasks')" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    <span>Tasks</span>
                </a>
                <a href="javascript:void(0)" onclick="switchView('translated')" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path></svg>
                    <span>Translated</span>
                </a>
                <a href="javascript:void(0)" onclick="switchView('relationships')" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <span>Relationships</span>
                </a>
                <a href="javascript:void(0)" onclick="switchView('glossary')" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    <span>Glossary</span>
                </a>
                <a href="javascript:void(0)" onclick="switchView('about')" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>About</span>
                </a>
                <a href="javascript:void(0)" onclick="switchView('activity-logs')" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Activity Logs</span>
                </a>
                <a href="javascript:void(0)" onclick="switchView('settings')" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm text-gray-600">

                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <span>Settings</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto bg-gray-50">
            <!-- Header -->
            <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-8 sticky top-0 z-10">
                <div class="flex items-center gap-4">
                    <button class="p-2 text-gray-400 hover:text-gray-600 md:hidden">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <h1 class="text-lg font-semibold text-gray-700">Gov Translator</h1>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-gov-100 flex items-center justify-center text-gov-600 font-semibold text-sm">A</div>
                    <span class="text-sm font-medium text-gray-700 hidden sm:inline">Admin</span>
                </div>
            </header>

            <div class="p-8 max-w-7xl mx-auto">
                <?php require GOV_HYBRID_TRANSLATOR_PATH . 'inc/Admin/views/overview-view.php'; ?>
                <?php require GOV_HYBRID_TRANSLATOR_PATH . 'inc/Admin/views/settings-view.php'; ?>
                <?php require GOV_HYBRID_TRANSLATOR_PATH . 'inc/Admin/views/tasks-view.php'; ?>
                <?php require GOV_HYBRID_TRANSLATOR_PATH . 'inc/Admin/views/translated-view.php'; ?>
                <?php require GOV_HYBRID_TRANSLATOR_PATH . 'inc/Admin/views/glossary-view.php'; ?>
                <?php require GOV_HYBRID_TRANSLATOR_PATH . 'inc/Admin/views/translation-relationships-view.php'; ?>
                <?php require GOV_HYBRID_TRANSLATOR_PATH . 'inc/Admin/views/about-view.php'; ?>
                <?php require GOV_HYBRID_TRANSLATOR_PATH . 'inc/Admin/views/activity-logs-view.php'; ?>
                <?php require GOV_HYBRID_TRANSLATOR_PATH . 'inc/Admin/views/content-review-modal.php'; ?>

            </div>
        </main>
    </div>
</div>
