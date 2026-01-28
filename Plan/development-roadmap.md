# Gov Hybrid Translator - Development Roadmap

เอกสารนี้รวบรวมแผนการพัฒนาปลั๊กอิน Gov Hybrid Translator ในระยะถัดไป เพื่อยกระดับความสามารถให้เหมาะสมกับการใช้งานในหน่วยงานราชการ

---

## Phase 3: Transparency & Efficiency (ความโปร่งใสและประสิทธิภาพ)

**เป้าหมาย:** เพิ่มระบบตรวจสอบย้อนกลับ (Audit) และเครื่องมือที่ช่วยให้เจ้าหน้าที่ทำงานได้ง่ายขึ้น

### 3.1 Activity Logs (Audit Trail) 📜 (Completed)

ระบบบันทึกประวัติการทำงาน เพื่อความโปร่งใสและตรวจสอบได้ตามมาตรฐานราชการ

- **Features:**
  - [x] บันทึกความเคลื่อนไหวทุกอย่าง: ใคร (User), ทำอะไร (Action), กับหน้าไหน (Object), เมื่อไหร่ (Timestamp)
  - [x] Actions ที่บันทึก: `Translation Generated`, `Content Edited`, `Published`, `Glossary Added/Deleted`
  - [x] หน้า Dashboard ใหม่สำหรับดู Logs พร้อมตัวกรอง (Filter) ตามวันที่และผู้ใช้
  - [x] **Dashboard Interactivity**: คลิกที่ Card เพื่อไปยังหน้าต่างๆ ได้ทันที
  - [x] **Recent Translations Fix**: เรียงลำดับงานแปลตามเวลาแก้ไขจริง
- **Technical:**
  - สร้าง Table ใหม่ `wp_ght_activity_logs`
  - สร้าง Class `ActivityLogger`

### 3.2 Frontend Visual Editor 👁️

ระบบแก้ไขคำแปลจากหน้าเว็บไซต์จริง เพื่อให้เห็นบริบทและจัดรูปแบบได้ถูกต้อง

- **Features:**
  - ปุ่ม "Edit Translation" บน Admin Bar เมื่อดูหน้าเว็บภาษาต่างประเทศ
  - Modal หรือ Sidebar Editor ที่แก้ไขข้อความได้ทันที
  - Live Preview การเปลี่ยนแปลง
- **Technical:**
  - ใช้ JavaScript/AJAX โหลดข้อมูล TranslationMeta มาแสดง
  - บันทึกกลับด้วย `save_full_translation` API เดิม

### 3.3 Advanced Workflow (Approval Chain) ✅

เพิ่มระดับการอนุมัติเนื้อหา เพื่อรองรับกระบวนการทำงานของราชการ

- **Features:**
  - สถานะเพิ่มเติม: `Draft` -> `Reviewing` -> `Approved` -> `Published`
  - ระบบแจ้งเตือนทาง Email เมื่อมีงานรอตรวจ
  - Role Capabilities ใหม่: `ght_translator` (แปลได้อย่างเดียว), `ght_approver` (อนุมัติได้)

---

## Phase 4: Expansion & Integration (การขยายผล)

**เป้าหมาย:** รองรับเอกสารราชการและการเชื่อมต่อภายนอก

### 4.1 Document Translation 📄

ระบบแปลเอกสารไฟล์แนบ (PDF, Word)

- **Features:**
  - หน้า Upload ไฟล์เอกสาร
  - ระบบ Extract ข้อความจากไฟล์ (อาจใช้ AI Service)
  - ระบบสร้างไฟล์ใหม่ที่แปลแล้ว (Translated Document Generation)
  - คลังเก็บไฟล์ที่แปลแล้ว (Document Archive)

### 4.2 data Export / Import 📦

ระบบสำรองและโอนย้ายข้อมูลคำแปล

- **Features:**
  - Export คำแปลเป็น `.csv`, `.json`, หรือ `.xliff` (มาตรฐานงานแปล)
  - Import คำแปลกลับเข้าสู่ระบบ (Restore)
  - ใช้สำหรับส่งงานให้หน่วยงานภายนอก หรือย้าย Server

### 4.3 External AI Integration 🧠

เพิ่มทางเลือก AI Engine อื่นๆ

- **Features:**
  - รองรับ Google Cloud Translation Advanced API (สำหรับอภิธานศัพท์เฉพาะทาง)
  - รองรับ Azure AI Translator
  - รองรับ Local LLM (Ollama) สำหรับหน่วยงานที่ต้องการความเป็นส่วนตัวสูง

---

## Timeline (Example)

| Phase | Task | Est. Time | Priority |
|-------|------|-----------|----------|
| **3** | Activity Logs | 1 Week | High |
| **3** | Frontend Editor | 2 Weeks | High |
| **3** | Advanced Workflow | 1 Week | Medium |
| **4** | Export/Import | 1 Week | Medium |
| **4** | Document Translation | 3-4 Weeks | Low (Complex) |
