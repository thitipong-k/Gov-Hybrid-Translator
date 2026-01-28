<div id="view-activity-logs" class="view-section hidden">
    <!-- 
        VIEW: ACTIVITY LOGS
        แสดงตารางประวัติการใช้งาน (Audit Trail)
        - แสดงรายการ User, Action, Object, Details, Time
        - รองรับ Pagination
    -->
    <!-- Header -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Activity Logs (Audit Trail)</h2>
            <p class="text-gray-500">ตรวจสอบประวัติการทำงานย้อนหลัง</p>
        </div>
    </div>
    
    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 font-semibold">
                    <th class="px-6 py-4">User</th>
                    <th class="px-6 py-4">Action</th>
                    <th class="px-6 py-4">Object</th>
                    <th class="px-6 py-4">Details</th>
                    <th class="px-6 py-4">Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (!empty($logs_data['items'])): ?>
                    <?php foreach ($logs_data['items'] as $log): ?>
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php 
                                    $user = get_userdata($log->user_id);
                                    if ($user) {
                                        echo '<div class="flex items-center gap-2">';
                                        echo '<div class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-600">' . substr($user->display_name, 0, 1) . '</div>';
                                        echo '<span class="text-sm font-medium text-gray-700">' . esc_html($user->display_name) . '</span>';
                                        echo '</div>';
                                    } else {
                                        echo '<span class="text-gray-500 text-sm">System</span>';
                                    }
                                ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                    $action_colors = [
                                        'translation_saved' => 'bg-green-100 text-green-800',
                                        'translation_updated' => 'bg-blue-100 text-blue-800',
                                        'translation_generated' => 'bg-purple-100 text-purple-800',
                                        'translation_deleted' => 'bg-red-100 text-red-800',
                                        'term_translation_saved' => 'bg-teal-100 text-teal-800',
                                        'menu_translation_saved' => 'bg-indigo-100 text-indigo-800',
                                        'glossary_added' => 'bg-emerald-100 text-emerald-800',
                                        'glossary_updated' => 'bg-cyan-100 text-cyan-800',
                                        'glossary_deleted' => 'bg-orange-100 text-orange-800',
                                        'settings_updated' => 'bg-gray-100 text-gray-800 border border-gray-300',
                                    ];
                                    $color_class = $action_colors[$log->action] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $color_class; ?>">
                                    <?php echo esc_html(str_replace('_', ' ', strtoupper($log->action))); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?php echo esc_html(ucfirst($log->object_type) . ' #' . $log->object_id); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 font-mono text-xs">
                                <?php 
                                    $decoded_details = json_decode($log->details, true);
                                    if ($decoded_details) {
                                        // Pretty print slightly
                                        foreach ($decoded_details as $k => $v) {
                                            if (is_array($v)) $v = json_encode($v);
                                            echo "<strong>$k:</strong> " . esc_html(mb_strimwidth($v, 0, 50, '...')) . "<br>";
                                        }
                                    } else {
                                        echo esc_html(mb_strimwidth($log->details, 0, 100, '...'));
                                    }
                                ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                <?php echo mysql2date('d M, H:i', $log->created_at); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-400 italic">No activity logs found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($logs_data['pages'] > 1): ?>
    <div class="mt-6 flex justify-center gap-2">
        <?php 
            // Simple pagination with prev/next and numbers
            $current = $paged_logs;
            $total = $logs_data['pages'];
            $range = 2;
        ?>

        <?php if ($current > 1): ?>
            <a href="<?php echo add_query_arg('paged_logs', $current - 1); ?>" class="px-3 py-1 rounded border border-gray-300 bg-white text-gray-600 hover:bg-gray-50 text-sm">Previous</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total; $i++): ?>
            <?php if ($i == 1 || $i == $total || ($i >= $current - $range && $i <= $current + $range)): ?>
                <a href="<?php echo add_query_arg('paged_logs', $i); ?>" 
                   class="px-3 py-1 rounded border text-sm <?php echo $current == $i ? 'bg-gov-600 text-white border-gov-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php elseif (($i == $current - $range - 1) || ($i == $current + $range + 1)): ?>
                <span class="px-2 text-gray-400">...</span>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($current < $total): ?>
            <a href="<?php echo add_query_arg('paged_logs', $current + 1); ?>" class="px-3 py-1 rounded border border-gray-300 bg-white text-gray-600 hover:bg-gray-50 text-sm">Next</a>
        <?php endif; ?>
    </div>
    <div class="mt-2 text-center text-xs text-gray-400">
        Page <?php echo $current; ?> of <?php echo $total; ?>
    </div>
    <?php endif; ?>
</div>
