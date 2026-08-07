# Changelog

การเปลี่ยนแปลงทั้งหมดของ Gov Hybrid Translator plugin

## [2.5.4] - 2026-08-07

### ความปลอดภัย (Security)
- **Glossary AJAX Security Fix**: แก้ไขช่องโหว่ความปลอดภัยระดับร้ายแรงในส่วนของการยืนยันคำขอ AJAX (`verify_request`) ของ Glossary โดยแยกการตรวจสอบ Nonce (ป้องกัน CSRF) และการตรวจสอบสิทธิ์การใช้งาน (Capabilities - ป้องกัน Privilege Escalation) ออกจากกันเป็นอิสระต่อกัน (เนื่องจากเดิมใช้การเชื่อมด้วย `&&` ทำให้สามารถหลีกเลี่ยงการเช็คสิทธิ์หรือ nonce ได้)

---

## [2.5.3] - 2026-08-07

### แก้ไข (Fixed)
- **Glossary Modal Layout**: แก้ไขปุ่ม "ยกเลิก" และปุ่ม "บันทึก / ลบ" ซ้อนทับกันใน Modal ท้ายหน้าจอ โดยการกำหนดขอบเขต CSS ของ `.ght-modal-close` เฉพาะในส่วนหัวข้อ (Header) เท่านั้น
- **AJAX Internal Server Error (500)**: แก้ไขปัญหา Fatal Error ในหลังบ้านจากการเรียกใช้ฟังก์ชัน `wp_cache_delete_group` ที่ไม่มีอยู่จริง โดยเปลี่ยนมาใช้ฟังก์ชันมาตรฐาน `wp_cache_delete` เพื่อล้างแคชตามคู่ภาษาเป้าหมายที่ถูกกำหนดไว้ใน Settings

---

## [2.5.2] - 2026-08-05

### แก้ไข (Fixed)
- **Pre-Translation Glossary Protection & Restoration**: แก้ไขปัญหาศัพท์เฉพาะใน Glossary ไม่ถูกนำมาใช้เมื่อกดแปล โดยเปลี่ยนจากวิธีแทนที่คำหลังแปล เป็นการซ่อนคำไทยด้วย Placeholder (`{{GLOSSARY_X}}`) **ก่อนส่งแปล AI** แล้วคืนค่าด้วยคำแปลจาก Glossary **หลังแปลเสร็จ**
- **REST API Glossary Integration**: เพิ่มการใช้งาน Glossary ใน `POST /translate` REST API endpoint
- **Cache Invalidation**: เพิ่มระบบล้าง Cache อัตโนมัติใน `GlossaryManager` เมื่อมีการสร้าง, แก้ไข หรือลบคำศัพท์ใน Glossary

### เพิ่มเติม (Added)
- `GlossaryReplacer` Service class สำหรับจัดการ Pre/Post Glossary Protection, Restoration และ Caching

---

## [2.5.1] - 2026-01-28

### แก้ไข (Fixed)

- **Critical:** แก้ไขปัญหาการบันทึก Settings แล้วค่าหายทั้งหมด (รวมถึง API Key) เนื่องจาก AJAX data format conflict
- **Permissions:** แก้ไข Checkbox "Approve translation" ไม่บันทึกค่าเมื่อติ๊กออก
- **Content & SEO:** เพิ่ม Whitelist settings สำหรับ Auto-translate on Publish ที่ขาดหายไป
- **Language Switcher:** แก้ไข logic การบันทึก Checkbox (False values) และกู้คืน Javascript ที่หายไป (Preview/Interactive UI)
- **Refactor:** ย้าย Inline JavaScript ออกจากไฟล์ PHP view ทั้งหมดไปยัง `admin-dashboard.js` เพื่อประสิทธิภาพและลด Syntax Errors

---

## [2.5.0] - 2026-01-27

### เพิ่มเติม (Added)

- **Advanced Translation Workflow**: ระบบสถานะ Draft/Publish สำหรับการตรวจสอบก่อนเผยแพร่
- **Smart Glossary**: ระบบคลังคำศัพท์อัจฉริยะ รองรับ Regex และ Case Insensitivity
- **Manual Edit**: เพิ่มตัวเลือกสถานะ (Status) ในหน้าต่างแก้ไขงานแปล
- **Visibility Control**: ซ่อนเนื้อหาฉบับร่าง (Draft) จากผู้ใช้งานทั่วไป (แสดงเฉพาะ Admin)

### แก้ไข (Fixed)

- ปัญหา Double Language Prefix ในลิงก์หน้าแรกและโลโก้ (`/n/en/`)
- ปัญหาสถานะถูกรีเซ็ตเป็น Published เมื่อใช้ Quick Edit (Save Page Translation)

---

## [2.4.0] - 2026-01-27

### เพิ่มเติม (Added)

- **Advanced Translation Workflow**: ระบบสถานะ Draft/Publish สำหรับการตรวจสอบก่อนเผยแพร่
- **Smart Glossary**: ระบบคลังคำศัพท์อัจฉริยะ รองรับ Regex และ Case Insensitivity
- **Manual Edit**: เพิ่มตัวเลือกสถานะ (Status) ในหน้าต่างแก้ไขงานแปล
- **Visibility Control**: ซ่อนเนื้อหาฉบับร่าง (Draft) จากผู้ใช้งานทั่วไป (แสดงเฉพาะ Admin)

### แก้ไข (Fixed)

- ปัญหา Double Language Prefix ในลิงก์หน้าแรกและโลโก้ (`/n/en/`)
- ปัญหาสถานะถูกรีเซ็ตเป็น Published เมื่อใช้ Quick Edit (Save Page Translation)

### ปรับปรุง (Improved)

- Glossary ยกเว้นการแทนที่คำใน HTML Tags/Attributes เพื่อความปลอดภัยของโครงสร้างเว็บ
- เพิ่ม Thai comments อธิบายการทำงานในโค้ด (Router, TranslationAjax, Post, etc.)

---

## [2.3.0] - 2026-01-25

### เพิ่มเติม (Added)

- ปุ่ม **Delete Translation** ใน Review Content modal สำหรับลบ translation ที่มีอยู่
- รองรับ **Custom HTML Block** (`core/html`) ใน Gutenberg
- Method `translateHtmlDom()` สำหรับแปล HTML ที่ซับซ้อน

### แก้ไข (Fixed)

- **Complex HTML Translation**: แก้ไขปัญหาโครงสร้าง HTML หลุดหายเมื่อแปลด้วย AI
- **Avada Theme Integration**: เพิ่มการรองรับ Fusion Builder elements และ dynamic data
- **Duplicate Tags**: ป้องกันการซ้ำซ้อนของ HTML tags หลังแปลเสร็จ

---

## [2.2.0] - 2026-01-20

### เพิ่มเติม (Added)

- **Original/Translated View Tabs**: สลับดูเนื้อหาต้นฉบับภาษาไทยและเนื้อหาที่แปลแล้วใน Review Content modal
- **Avada Theme Builder Support**: รองรับ Header/Footer rendering สำหรับหน้าภาษาอังกฤษ
- **AJAX Translated Content API**: Endpoint สำหรับดึงเนื้อหาที่แปลแล้วไปแสดงใน Modal

### แก้ไข (Fixed)

- ปัญหา 404 Not Found เมื่อเปิดหน้าภาษาอังกฤษที่ใช้ Avada Theme Builder
- Header/Footer หายในหน้าแปลภาษาอังกฤษ

---

## [2.1.1] - 2026-01-15

### เพิ่มเติม (Added)

- **Category-Based Queue**: แสดงรายการเนื้อหาที่ยังแปลไม่ครบแยกตามหมวดหมู่ (Categories)
- **Missing Languages Indicator**: แสดงภาษาที่ยังขาดการแปลของแต่ละ Post/Page
- **Page Translation List**: แสดงรายการ Pages ที่ยังแปลไม่ครบแยกต่างหาก

### แก้ไข (Fixed)

- ปัญหา 404 Error เมื่อสลับภาษากลับมาเป็นภาษาไทยบนบางเพจ

---

## [2.1.0] - 2026-01-10

### เพิ่มเติม (Added)

- **Multi-language Support**: รองรับภาษาอังกฤษ (EN), จีน (ZH), ญี่ปุ่น (JA), เกาหลี (KO), ฯลฯ
- **Path-based URLs**: URL โครงสร้าง `example.go.th/en/page-name/`
- **Real-time Statistics**: แสดงสถิติจริงจาก Database ใน Dashboard
- **Log Viewer**: ดูและล้าง Activity Logs ใน Advanced Settings

---

## [2.0.0] - 2026-01-01

### เพิ่มเติม (Added)

- **Meta-based Architecture**: เก็บเนื้อหาที่แปลใน `post_meta` โดยไม่ต้องสร้าง Duplicate Posts
- **Gutenberg Parser**: แปล block content โดยยังคงรักษาโครงสร้าง blocks
- **Elementor Parser**: แปล widget data และ nested elements ของ Elementor
- **Auto-Translate on Publish**: แปลอัตโนมัติเมื่อกด Publish บทความใหม่
- **Comparison View**: ดูเนื้อหาภาษาไทยและอังกฤษแบบ side-by-side
