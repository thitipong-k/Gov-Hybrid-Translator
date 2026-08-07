=== Gov Hybrid Translator ===
Contributors: govtechteam
Tags: translation, hybrid, glossary, government, multilingual, gutenberg
Requires at least: 5.8
Tested up to: 7.1
Stable tag: 2.5.6
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A hybrid translation system (Manual + AI) with Glossary support, Gutenberg blocks, and Elementor page builder translation.
ระบบแปลภาษาเว็บภาครัฐแบบ Hybrid (Manual + AI) รองรับ Glossary, Gutenberg และ Elementor

== Description ==

Gov Hybrid Translator is designed to streamline the translation workflow for government websites. It uses a Meta-based architecture for storing translations alongside the original content, without creating duplicate posts.

**Gov Hybrid Translator** ถูกออกแบบมาเพื่อเพิ่มประสิทธิภาพกระบวนการแปลภาษาสำหรับเว็บไซต์หน่วยงานราชการ โดยใช้สถาปัตยกรรมแบบ Meta-based สำหรับจัดเก็บข้อมูลการแปลควบคู่ไปกับเนื้อหาต้นฉบับ โดยไม่ต้องสร้าง Post ซ้ำซ้อน

**Key Features (คุณสมบัติหลัก):**

*   **Glossary System**: ระบบคลังคำศัพท์เฉพาะ (Manage official terms) จัดการคำศัพท์ราชการ (บุคคล, ตำแหน่ง, หน่วยงาน) ผ่าน Custom Post Type
*   **AI-Powered Translation**: แปลภาษาอัตโนมัติด้วย AI พร้อมระบบแทนที่คำศัพท์จาก Glossary เพื่อความถูกต้องแม่นยำ
*   **Gutenberg Support**: รองรับการแปลเนื้อหาจาก Gutenberg Block โดยยังคงโครงสร้างเดิมไว้อย่างสมบูรณ์
*   **Elementor Support**: รองรับการแปลข้อมูลจาก Elementor Widget
*   **Auto-Translate on Publish**: แปลภาษาให้อัตโนมัติทันทีที่กดเผยแพร่เนื้อหา (Publish)
*   **Comparison View**: มุมมองเปรียบเทียบภาษาไทย vs อังกฤษ แบบ Side-by-side
*   **View Translation Modal**: หน้าต่างดูตัวอย่างเนื้อหาที่แปลแล้ว พร้อมฟังก์ชัน Copy
*   **Routing**: รองรับโครงสร้าง URL แบบ `example.go.th/en/`
*   **Language Switcher**: ปุ่มสลับภาษาที่ตั้งค่าการแสดงผลได้หลากหลายรูปแบบ
*   **Multi-Language Support**: รองรับหลายภาษา (อังกฤษ, จีน, ญี่ปุ่น, เกาหลี, เยอรมัน, ฝรั่งเศส)

== Installation ==

1.  อัปโหลดไฟล์ปลั๊กอินไปยังไดเรกทอรี `/wp-content/plugins/gov-hybrid-translator` หรือติดตั้งผ่านหน้า Plugins ของ WordPress โดยตรง
2.  เปิดใช้งานปลั๊กอิน (Activate) ผ่านหน้า 'Plugins' ใน WordPress
3.  ไปที่เมนู "Gov Glossary" เพื่อเริ่มเพิ่มคำศัพท์เฉพาะ
4.  ตั้งค่าการใช้งานที่เมนู "Gov Translator"
5.  (ทางเลือก) เปิดใช้งาน Auto-Translate ใน Settings → Content & SEO

== Changelog ==

= 2.5.6 =
*   **IMPROVED**: Corrected screenshots redirection folder path to `assets/images/` to match the actual folder location of screenshot files.

= 2.5.5 =
*   **IMPROVED**: Reworked the plugin details screenshots tab to dynamically fetch and display screenshot images from the GitHub repository instead of falling back to WordPress.org CDN URLs.

= 2.5.4 =
*   **SECURITY**: Fixed critical vulnerability in Glossary AJAX endpoint validation (`verify_request`) where Nonce checks and Capability checks were grouped together using `&&`, leading to CSRF and Privilege Escalation bypasses. They are now verified independently.

= 2.5.3 =
*   **FIXED**: Overlapping buttons in the Glossary add, delete, and edit modal footer by scoping close buttons CSS.
*   **FIXED**: 500 Internal Server Error when saving, editing, or deleting terms due to undefined function `wp_cache_delete_group()`.

= 2.5.2 =
*   **FIXED**: Glossary term replacement issue where terms were ignored during translation. Implemented Pre-Translation Protection & Post-Translation Restoration using placeholders (`{{GLOSSARY_X}}`).
*   **NEW**: `GlossaryReplacer` Service class with automated term length sorting, HTML-safe protection, and transient caching.
*   **NEW**: REST API Glossary integration for `POST /translate` endpoint.
*   **IMPROVED**: Automatic Glossary cache clearing on term create/update/delete.

= 2.5.1 =
*   **FIXED**: Saved settings disappearing issue due to AJAX data format conflict.
*   **FIXED**: Checkboxes for permissions and language switchers not saving properly.
*   **IMPROVED**: Refactored frontend editor logic and settings dashboard.

= 2.5.0 =
*   **NEW**: Frontend Editor - ระบบแก้ไขคำแปลจากหน้าบ้าน (Frontend Editor)
*   **NEW**: Advanced Approval Workflow - โครงสร้างขั้นตอนการอนุมัติคำแปลขั้นสูง
*   **NEW**: Verification Dashboard & Email Notifications - แดชบอร์ดตรวจสอบการทำงานและระบบส่งอีเมลแจ้งเตือนงานแปล

= 2.4.0 =
*   **NEW**: Advanced Translation Workflow - เพิ่มสถานะ Draft & Publish สำหรับการแปล
*   **NEW**: Smart Glossary - ระบบแทนที่คำศัพท์ด้วย Regex รองรับ Case Sensitivity
*   **NEW**: Manual Edit Modal - เพิ่มตัวเลือก "Status" (Draft/Published) ในหน้าแก้ไข
*   **NEW**: Frontend Visibility Control - เนื้อหาที่ยังเป็น Draft จะถูกซ่อนจากผู้ใช้งานทั่วไป
*   **IMPROVED**: ปรับปรุงระบบ Glossary ให้ปลอดภัยขึ้น โดยไม่กระทบกับ HTML tags หรือ attributes
*   **FIXED**: แก้ไขบั๊ก Language Prefix ซ้ำซ้อนในโลโก้และลิงก์หน้าแรก
*   **FIXED**: แก้ไขตรรกะ Quick Edit (บันทึกการแปลหน้า) ให้คงสถานะเดิมไว้

= 2.3.0 =
*   **NEW**: เพิ่มปุ่มลบเนื้อหาการแปล (Delete Translation) ในหน้าต่าง Review Content
*   **NEW**: รองรับการแปล Custom HTML Block (`core/html`)
*   **FIXED**: แก้ไขปัญหาโครงสร้าง HTML ซับซ้อน (timeline, nested divs) หายไปหลังการแปล
*   **IMPROVED**: ปรับปรุงการแปล HTML ด้วยเทคนิค DOM-based extraction สำหรับเนื้อหาที่ซับซ้อน
*   **IMPROVED**: เพิ่มเมธอด translateHtmlDom() เพื่อรักษาโครงสร้าง HTML ได้ดียิ่งขึ้น
*   **SECURITY**: ลบคำสั่ง debug log ทั้งหมด (console.log, error_log) เพื่อความปลอดภัย

= 2.2.0 =
*   **NEW**: เพิ่มแท็บ View Original/Translated ในหน้าต่าง Review Content
*   **NEW**: รองรับการแสดงผล Header/Footer ของ Avada Theme Builder ในหน้าแปล
*   **FIXED**: แก้ไขปัญหา Header/Footer หายในหน้าภายในที่แปลแล้ว
*   **FIXED**: แก้ไข Error 404 ใน URL หน้าภาษาอังกฤษ

= 2.1.0 =
*   **FIXED**: ปุ่ม View Logs และ Clear Logs ใน Advanced Settings ไม่ทำงาน
*   **FIXED**: แดชบอร์ดแสดงข้อมูลจำลอง (Mock data) แทนข้อมูลจริง
*   **FIXED**: ปุ่มสลับภาษา TH ไม่ทำงานในหน้าภาษาอังกฤษ
*   **IMPROVED**: แดชบอร์ดแสดงข้อมูลสถิติจริงจากฐานข้อมูล (จำนวนการแปล, คำศัพท์, เครดิต AI, TM Hit Rate, สัดส่วนภาษา, แนวโน้มรายเดือน, หมวดหมู่ยอดนิยม, การแปลล่าสุด)
*   **IMPROVED**: หน้าต่าง View Logs ปรับดีไซน์เป็น Dark theme แบบ Terminal
*   **IMPROVED**: ฟังก์ชัน Clear Logs พร้อมกล่องยืนยันก่อนลบ

= 2.0.0 =
*   **NEW**: Gutenberg block translation parser - รักษาระบบโครงสร้างบล็อกขณะแปล
*   **NEW**: Elementor widget translation parser - รองรับ Widget ที่ซับซ้อน
*   **NEW**: ฟีเจอร์ Auto-Translate on Publish พร้อมการตั้งค่า
*   **NEW**: แท็บเปรียบเทียบ TH ↔ EN - ดูเนื้อหาต้นฉบับเทียบกับคำแปลได้ทันที
*   **NEW**: View Translation Modal - ดูตัวอย่างและ Copy ได้รวดเร็ว
*   **NEW**: ตัวเลือกภาษาปลายทาง (Target Language) ในหน้า Review Content
*   **NEW**: รองรับหลายภาษา (EN, ZH, JA, KO, DE, FR)
*   **IMPROVED**: แท็บ Post/Page Contents แสดง Excerpt ภาษาอังกฤษ
*   **IMPROVED**: ระบบตรวจทานเนื้อหา (Content Reviewer) ตรวจจับคำศัพท์ Glossary ได้ดียิ่งขึ้น
*   **IMPROVED**: แจ้งเตือนสถานะการแปล (Notifications) แทนการใช้ Alerts
*   **ARCHITECTURE**: เปลี่ยนมาใช้ระบบจัดเก็บแบบ Meta-based (ไม่สร้าง Post ซ้ำ)
*   **Updated**: ปรับปรุงคำอธิบายและเวอร์ชันของปลั๊กอิน

= 1.2.0 =
*   แก้ไขปุ่มสลับภาษาไม่แสดงใน Theme ที่มี Fixed Header
*   เพิ่ม z-index เป็น 999999 ให้ปุ่ม Floating อยู่เหนือ Elements อื่นๆ
*   เพิ่มการตั้งค่า Top Offset ปรับตำแหน่งปุ่ม
*   ปรับปรุง CSS Transition ให้การ Hover นุ่มนวลขึ้น

= 1.1.1 =
*   เพิ่มหน้าตั้งค่า Language Switcher
*   รองรับการแสดงผลแบบ Floating, Menu, และ Shortcode
*   เพิ่ม Layout แบบ Dual Buttons (TH | EN เรียงติดกัน)
*   เพิ่มตัวเลือกเนื้อหาปุ่ม (ธงอย่างเดียว, ข้อความอย่างเดียว, หรือทั้งคู่)
*   ปรับแต่งตำแหน่ง Floating ได้ (ขวาบน, ขวากลาง, ขวาล่าง)
*   แก้ไข Warning PHP ใน Router.php
*   ปรับขนาดไอคอนธงเป็น 30px

= 1.1.0 =
*   Initial Release สำหรับทดสอบ
*   เพิ่มระบบ Glossary Custom Post Type
*   เพิ่มระบบ Hybrid Translation Workflow
*   เพิ่มระบบ Frontend Routing สำหรับ URL /en/

== Frequently Asked Questions ==

= ปลั๊กอินนี้สร้าง Post ซ้ำหรือไม่? (Does this plugin create duplicate posts?) =

ไม่ครับ เวอร์ชัน 2.0.0 ขึ้นไปใช้สถาปัตยกรรมแบบ Meta-based โดยเก็บข้อมูลการแปลไว้ใน `post_meta` ของโพสต์ต้นฉบับ ไม่มีการสร้างโพสต์ใหม่ให้รกฐานข้อมูล

= รองรับ Gutenberg หรือไม่? (Does it work with Gutenberg?) =

รองรับครับ! เวอร์ชัน 2.0.0 มี Parser สำหรับ Gutenberg โดยเฉพาะ สามารถแปลเนื้อหาภายในบล็อกโดยไม่ทำลายโครงสร้าง Layout

= รองรับ Elementor หรือไม่? (Does it work with Elementor?) =

รองรับครับ! มี Elementor parser ที่จัดการข้อมูล Widget และโครงสร้างที่ซ้อนกันได้เป็นอย่างดี

= ใช้บริการ AI เจ้าไหน? (What AI service does it use?) =

ปลั๊กอินรองรับ AI หลายค่ายตามการตั้งค่าของคุณ (เช่น Google, Azure, OpenAI) สามารถกำหนด API Key ได้ที่เมนู Settings → API Settings

== Screenshots ==

1. Dashboard overview (ภาพรวมแดชบอร์ด)
2. Translation comparison view (มุมมองเปรียบเทียบคำแปล)
3. Review Content modal (หน้าต่างตรวจทานเนื้อหา)
4. Settings page (หน้าตั้งค่า)
5. Glossary management (ระบบจัดการคำศัพท์)

== Upgrade Notice ==

= 2.1.0 =
แก้ไขบั๊ก View Logs และปรับปรุง Dashboard ให้แสดงข้อมูลสถิติจริงจากฐานข้อมูล

= 2.0.0 =
อัปเดตใหญ่ รองรับ Gutenberg/Elementor, ระบบ Auto-Translate และปรับปรุง UI แนะนำให้ผู้ใช้อัปเดตทันที
