<?php
/**
 * Capabilities Class - จัดการสิทธิ์ผู้ใช้สำหรับ Plugin
 * 
 * คลาสนี้รับผิดชอบในการ:
 * - ลงทะเบียน Custom Capabilities สำหรับ Plugin
 * - ตรวจสอบสิทธิ์ผู้ใช้ก่อนดำเนินการต่างๆ
 * - จัดการการมอบ/เพิกถอนสิทธิ์ให้แต่ละ Role
 * 
 * @package GovHybridTranslator
 * @since 1.5.0
 */

namespace GovHybridTranslator\Core;

if (!defined('ABSPATH')) exit;

class Capabilities {
    
    /**
     * รายการ Capabilities ทั้งหมดที่ Plugin ใช้งาน
     * 
     * ght_view_dashboard  - เข้าถึง Dashboard หลักของ Plugin
     * ght_translate       - แปล Posts/Pages และใช้งาน Review Content
     * ght_manage_glossary - จัดการ Glossary (เพิ่ม/ลบ/แก้ไข คำศัพท์)
     * ght_manage_settings - แก้ไข Settings ของ Plugin
     */
    const CAPABILITIES = [
        'ght_view_dashboard',
        'ght_translate',
        'ght_manage_glossary',
        'ght_manage_settings'
    ];
    
    /**
     * Default Capabilities สำหรับแต่ละ Role
     * กำหนดว่า Role ไหนจะได้รับ Capability อะไรเป็นค่าเริ่มต้น
     */
    const DEFAULT_ROLE_CAPS = [
        'administrator' => [
            'ght_view_dashboard'  => true,
            'ght_translate'       => true,
            'ght_manage_glossary' => true,
            'ght_manage_settings' => true
        ],
        'editor' => [
            'ght_view_dashboard'  => true,
            'ght_translate'       => true,
            'ght_manage_glossary' => false,
            'ght_manage_settings' => false
        ]
    ];
    
    /**
     * ลงทะเบียน Capabilities ให้กับ Roles (เรียกตอน activate plugin)
     * 
     * ฟังก์ชันนี้จะ:
     * 1. วนลูปผ่านแต่ละ Role ที่กำหนดใน DEFAULT_ROLE_CAPS
     * 2. เพิ่ม Capabilities ตามที่กำหนดให้แต่ละ Role
     * 
     * @return void
     */
    public static function register() {
        // วนลูปผ่านแต่ละ Role
        foreach (self::DEFAULT_ROLE_CAPS as $role_slug => $caps) {
            // ดึง Role object จาก WordPress
            $role = get_role($role_slug);
            
            if ($role) {
                // เพิ่มแต่ละ Capability ให้ Role
                foreach ($caps as $cap => $grant) {
                    if ($grant) {
                        $role->add_cap($cap);
                    }
                }
            }
        }
    }
    
    /**
     * ลบ Capabilities ออกจากทุก Roles (เรียกตอน deactivate plugin)
     * 
     * ฟังก์ชันนี้จะทำความสะอาดโดยลบ Capabilities ทั้งหมด
     * ที่ Plugin เพิ่มเข้าไปใน WordPress
     * 
     * @return void
     */
    public static function unregister() {
        // ดึง Roles ทั้งหมดใน WordPress
        global $wp_roles;
        
        if (!isset($wp_roles)) {
            $wp_roles = new \WP_Roles();
        }
        
        // วนลูปลบ Capability ออกจากทุก Role
        foreach ($wp_roles->role_objects as $role) {
            foreach (self::CAPABILITIES as $cap) {
                $role->remove_cap($cap);
            }
        }
    }
    
    /**
     * ตรวจสอบว่าผู้ใช้ปัจจุบันมี Capability ที่กำหนดหรือไม่
     * 
     * @param string $cap ชื่อ Capability ที่ต้องการตรวจสอบ
     * @return bool true ถ้ามีสิทธิ์, false ถ้าไม่มี
     */
    public static function can($cap) {
        // ใช้ current_user_can ของ WordPress
        return current_user_can($cap);
    }
    
    /**
     * ตรวจสอบว่าผู้ใช้ปัจจุบันสามารถเข้าถึง Dashboard ได้หรือไม่
     * 
     * @return bool true ถ้าเข้าถึงได้
     */
    public static function can_view_dashboard() {
        return self::can('ght_view_dashboard');
    }
    
    /**
     * ตรวจสอบว่าผู้ใช้ปัจจุบันสามารถแปลเนื้อหาได้หรือไม่
     * 
     * @return bool true ถ้าแปลได้
     */
    public static function can_translate() {
        return self::can('ght_translate');
    }
    
    /**
     * ตรวจสอบว่าผู้ใช้ปัจจุบันสามารถจัดการ Glossary ได้หรือไม่
     * 
     * @return bool true ถ้าจัดการได้
     */
    public static function can_manage_glossary() {
        return self::can('ght_manage_glossary');
    }
    
    /**
     * ตรวจสอบว่าผู้ใช้ปัจจุบันสามารถแก้ไข Settings ได้หรือไม่
     * 
     * @return bool true ถ้าแก้ไขได้
     */
    public static function can_manage_settings() {
        return self::can('ght_manage_settings');
    }
    
    /**
     * ดึงรายการ Roles ทั้งหมดพร้อม Capabilities ของ Plugin
     * ใช้สำหรับแสดงใน Settings UI
     * 
     * @return array รายการ Roles พร้อมข้อมูล Capabilities
     */
    public static function get_roles_with_caps() {
        global $wp_roles;
        
        if (!isset($wp_roles)) {
            $wp_roles = new \WP_Roles();
        }
        
        $result = [];
        
        // ดึงข้อมูลแต่ละ Role
        foreach ($wp_roles->roles as $role_slug => $role_data) {
            $role_caps = [];
            
            // ตรวจสอบว่า Role มี Capability ของ Plugin หรือไม่
            foreach (self::CAPABILITIES as $cap) {
                $role_caps[$cap] = isset($role_data['capabilities'][$cap]) && $role_data['capabilities'][$cap];
            }
            
            $result[$role_slug] = [
                'name' => translate_user_role($role_data['name']),
                'caps' => $role_caps
            ];
        }
        
        return $result;
    }
    
    /**
     * มอบ Capability ให้ Role
     * 
     * @param string $role_slug ชื่อ Role (เช่น 'editor', 'author')
     * @param string $cap ชื่อ Capability
     * @return bool true ถ้าสำเร็จ
     */
    public static function grant_cap_to_role($role_slug, $cap) {
        // ตรวจสอบว่าเป็น Capability ที่ถูกต้อง
        if (!in_array($cap, self::CAPABILITIES)) {
            return false;
        }
        
        $role = get_role($role_slug);
        if ($role) {
            $role->add_cap($cap);
            return true;
        }
        
        return false;
    }
    
    /**
     * เพิกถอน Capability จาก Role
     * 
     * @param string $role_slug ชื่อ Role
     * @param string $cap ชื่อ Capability
     * @return bool true ถ้าสำเร็จ
     */
    public static function revoke_cap_from_role($role_slug, $cap) {
        // ตรวจสอบว่าเป็น Capability ที่ถูกต้อง
        if (!in_array($cap, self::CAPABILITIES)) {
            return false;
        }
        
        $role = get_role($role_slug);
        if ($role) {
            $role->remove_cap($cap);
            return true;
        }
        
        return false;
    }
    
    /**
     * อัพเดท Capabilities ของ Role ทั้งหมดในครั้งเดียว
     * ใช้สำหรับบันทึกจาก Settings UI
     * 
     * @param array $permissions Array ของ [role_slug => [cap => bool, ...], ...]
     * @return bool true ถ้าสำเร็จ
     */
    public static function update_permissions($permissions) {
        foreach ($permissions as $role_slug => $caps) {
            $role = get_role($role_slug);
            
            if (!$role) continue;
            
            foreach ($caps as $cap => $grant) {
                // ตรวจสอบว่าเป็น Capability ที่ถูกต้อง
                if (!in_array($cap, self::CAPABILITIES)) continue;
                
                if ($grant) {
                    $role->add_cap($cap);
                } else {
                    $role->remove_cap($cap);
                }
            }
        }
        
        return true;
    }
    
    /**
     * ดึงคำอธิบายของแต่ละ Capability (ภาษาไทย)
     * ใช้แสดงใน Settings UI
     * 
     * @return array [cap => description, ...]
     */
    public static function get_cap_descriptions() {
        return [
            'ght_view_dashboard'  => 'เข้าถึง Dashboard หลัก',
            'ght_translate'       => 'แปล Posts/Pages และ Review Content',
            'ght_manage_glossary' => 'จัดการ Glossary (เพิ่ม/ลบ/แก้ไข คำศัพท์)',
            'ght_manage_settings' => 'แก้ไข Settings ของ Plugin'
        ];
    }
}
