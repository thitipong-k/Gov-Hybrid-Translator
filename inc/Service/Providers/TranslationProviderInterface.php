<?php
/**
 * Translation Provider Interface
 * 
 * กำหนด contract สำหรับ Translation Providers ทั้งหมด
 * ทุก Provider ต้อง implement methods เหล่านี้
 * 
 * @package GovHybridTranslator
 * @since 1.6.0
 */
namespace GovHybridTranslator\Service\Providers;

// Prevent direct file access
if (!defined('ABSPATH')) exit;

interface TranslationProviderInterface {

    /**
     * แปลข้อความ
     * 
     * @param string $text ข้อความที่ต้องการแปล
     * @param string $targetLang รหัสภาษาเป้าหมาย (เช่น 'en', 'zh')
     * @param string $sourceLang รหัสภาษาต้นฉบับ (เช่น 'th')
     * @return string ข้อความที่แปลแล้ว
     */
    public function translate($text, $targetLang, $sourceLang = 'th');

    /**
     * ทดสอบการเชื่อมต่อ API
     * 
     * @return bool true = เชื่อมต่อสำเร็จ
     */
    public function testConnection();

    /**
     * ดึงชื่อ Provider
     * 
     * @return string ชื่อที่แสดงใน UI (เช่น 'Google Translate', 'DeepL')
     */
    public function getName();

    /**
     * ดึงรหัส Provider
     * 
     * @return string รหัสสำหรับใช้ใน code (เช่น 'google', 'deepl')
     */
    public function getSlug();

    /**
     * ดึง fields ที่ต้องกรอกใน Settings
     * 
     * @return array [
     *   'field_name' => [
     *     'label' => 'Label ที่แสดง',
     *     'type' => 'text' | 'password' | 'select',
     *     'required' => true | false,
     *     'options' => [] // สำหรับ type=select
     *   ]
     * ]
     */
    public function getRequiredFields();

    /**
     * ดึง error ล่าสุด
     * 
     * @return string error message หรือ empty string
     */
    public function getLastError();
}
