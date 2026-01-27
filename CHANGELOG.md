# Changelog

การเปลี่ยนแปลงทั้งหมดของ Gov Hybrid Translator plugin

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

### เพิ่มเติม (Added)

- ปุ่ม **Delete Translation** ใน Review Content modal สำหรับลบ translation ที่มีอยู่
- รองรับ **Custom HTML Block** (`core/html`) ใน Gutenberg
- Method `translateHtmlDom()` สำหรับแปล HTML ที่ซับซ้อน

### แก้ไข (Fixed)

- แก้ปัญหา HTML ที่ซับซ้อน (timeline, nested divs) สูญหายระหว่างแปล
- ปรับปรุง HTML structure preservation สำหรับ content ที่มี tags มากกว่า 50 ตัว

### ปรับปรุง (Improved)

- Smart HTML detection: เลือก method แปลที่เหมาะสมตามความซับซ้อนของ HTML
- เพิ่ม Thai comments ใน code files

### ความปลอดภัย (Security)

- ลบ `console.log` และ `error_log` statements ทั้งหมด

---

## [2.2.0] - 2024-12-24

### เพิ่มเติม (Added)

- **View Original/Translated tabs** ใน Review Content modal
- รองรับ **Avada Theme Builder** header/footer rendering สำหรับ translated pages

### แก้ไข (Fixed)

- Header/footer หายไปใน translated internal pages
- 404 errors สำหรับ translated page URLs

---

## [2.1.1] - 2024-12-23

### เพิ่มเติม (Added)

- Category-Based Translation Queue

### แก้ไข (Fixed)

- 404 error เมื่อเปลี่ยนภาษา

---

## [2.1.0] - 2024-12-22

### แก้ไข (Fixed)

- ปุ่ม View Logs และ Clear Logs ไม่ทำงานใน Advanced Settings
- Dashboard statistics แสดง mock data แทน real data
- ปุ่ม TH ใน language switcher ไม่ทำงานใน English pages

### ปรับปรุง (Improved)

- Dashboard แสดงสถิติจริงจาก database
- View Logs modal แบบ dark theme terminal-like display
- Clear Logs พร้อม confirmation dialog

---

## [2.0.0] - 2024-12-20

### เพิ่มเติม (Added)

- **Gutenberg block parser** - รักษา block structure ขณะแปล
- **Elementor widget parser** - รองรับ widgets ที่ซับซ้อน
- **Auto-Translate on Publish** feature
- **TH ↔ EN Comparison Tab** - ดู translations แบบ side-by-side
- **View Translation Modal** - preview พร้อม copy functionality
- รองรับหลายภาษา (EN, ZH, JA, KO, DE, FR)

### ปรับปรุง (Improved)

- Post Contents และ Page Contents tabs แสดง English Excerpt
- Content Reviewer ตรวจจับ glossary terms ได้ดีขึ้น
- Translation feedback ใช้ notifications แทน alerts

### สถาปัตยกรรม (Architecture)

- เปลี่ยนเป็น Meta-based translation storage (ไม่สร้าง duplicate posts)

---

## [1.2.0] - 2024-12-15

### แก้ไข (Fixed)

- Language switcher button visibility กับ fixed/sticky theme headers
- เพิ่ม z-index เป็น 999999 สำหรับ floating button

### เพิ่มเติม (Added)

- Top Offset setting สำหรับปรับตำแหน่งปุ่มกับ fixed headers
- CSS transitions ที่ smoothกว่า

---

## [1.1.1] - 2024-12-10

### เพิ่มเติม (Added)

- Configurable Language Switcher พร้อม settings page
- รองรับ Floating, Menu, และ Shortcode display modes
- Dual Buttons layout (TH | EN side-by-side)
- Button Content options (Flag Only, Text Only, Both)
- Customizable floating positions

### แก้ไข (Fixed)

- PHP warning ใน Router.php สำหรับ page_link filter

---

## [1.1.0] - 2024-12-01

### เพิ่มเติม (Added)

- Glossary Custom Post Type
- Hybrid Translation Workflow
- Frontend Routing สำหรับ /en/ URLs
