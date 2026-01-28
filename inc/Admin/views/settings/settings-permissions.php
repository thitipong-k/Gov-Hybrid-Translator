<?php
/**
 * Settings Permissions Tab - จัดการสิทธิ์ผู้ใช้
 * 
 * แท็บนี้แสดง UI ให้ Admin กำหนดสิทธิ์การเข้าถึงของแต่ละ Role
 * - แสดงตาราง Roles × Capabilities
 * - Checkbox ให้เลือก grant/revoke สิทธิ์
 * - บันทึกผ่าน AJAX
 * 
 * @package GovHybridTranslator
 * @since 1.5.0
 */

use GovHybridTranslator\Core\Capabilities;

// ดึงข้อมูล Roles และ Capabilities
$roles_with_caps = Capabilities::get_roles_with_caps();
$cap_descriptions = Capabilities::get_cap_descriptions();
$all_caps = Capabilities::CAPABILITIES;
?>

<!-- Tab Content: Permissions -->
<div id="settings-content-permissions" class="settings-tab-content hidden">
    
    <!-- คำอธิบายหัวข้อ -->
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-2">จัดการสิทธิ์ผู้ใช้</h3>
        <p class="text-gray-600 text-sm">
            กำหนดว่า Role ไหนสามารถเข้าถึงฟีเจอร์ใดบ้างของ Plugin
        </p>
    </div>
    
    <!-- คำอธิบาย Capabilities -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <h4 class="font-medium text-blue-800 mb-2">รายละเอียด Capabilities</h4>
        <ul class="text-sm text-blue-700 space-y-1">
            <?php foreach ($cap_descriptions as $cap => $desc): ?>
                <li>
                    <code class="bg-blue-100 px-1 rounded"><?php echo esc_html($cap); ?></code>
                    - <?php echo esc_html($desc); ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <!-- ตารางสิทธิ์ -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Role
                    </th>
                    <?php foreach ($all_caps as $cap): ?>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <?php 
                            // แสดงชื่อย่อของ Capability
                            $short_name = str_replace('ght_', '', $cap);
                            echo esc_html(ucfirst(str_replace('_', ' ', $short_name))); 
                            ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($roles_with_caps as $role_slug => $role_data): ?>
                    <tr class="hover:bg-gray-50">
                        <!-- ชื่อ Role -->
                        <td class="px-4 py-3">
                            <span class="font-medium text-gray-800">
                                <?php echo esc_html($role_data['name']); ?>
                            </span>
                            <span class="text-gray-400 text-xs ml-1">
                                (<?php echo esc_html($role_slug); ?>)
                            </span>
                        </td>
                        
                        <!-- Checkboxes สำหรับแต่ละ Capability -->
                        <?php foreach ($all_caps as $cap): ?>
                            <td class="px-4 py-3 text-center">
                                <?php 
                                // ป้องกันไม่ให้ลบสิทธิ์ของ Administrator
                                $disabled = ($role_slug === 'administrator' && 
                                           ($cap === 'ght_manage_settings' || $cap === 'ght_view_dashboard'));
                                $checked = isset($role_data['caps'][$cap]) && $role_data['caps'][$cap];
                                ?>
                                <input type="hidden" name="permissions[<?php echo esc_attr($role_slug); ?>][<?php echo esc_attr($cap); ?>]" value="0">
                                <input type="checkbox" 
                                       name="permissions[<?php echo esc_attr($role_slug); ?>][<?php echo esc_attr($cap); ?>]"
                                       value="1"
                                       class="ght-permission-checkbox h-4 w-4 text-gov-600 focus:ring-gov-500 border-gray-300 rounded"
                                       data-role="<?php echo esc_attr($role_slug); ?>"
                                       data-cap="<?php echo esc_attr($cap); ?>"
                                       <?php checked($checked); ?>
                                       <?php disabled($disabled); ?>>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- คำเตือน -->
    <div class="mt-4 flex items-start gap-2 text-amber-700 bg-amber-50 rounded-lg p-3 text-sm">
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
        </svg>
        <div>
            <strong>ข้อควรระวัง:</strong> 
            การเปลี่ยนแปลงสิทธิ์จะมีผลทันทีหลังกด "Save Changes" 
            Administrator จะต้องมีสิทธิ์ View Dashboard และ Manage Settings เสมอ
        </div>
    </div>
    
    <!-- ปุ่ม Reset to Defaults -->
    <div class="mt-6">
        <button type="button" 
                onclick="resetPermissionsToDefaults()"
                class="text-sm text-gray-500 hover:text-gray-700 underline">
            รีเซ็ตกลับค่าเริ่มต้น
        </button>
    </div>
</div>

<!-- Scripts moved to assets/js/admin-dashboard.js -->
