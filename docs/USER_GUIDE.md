# Gov Hybrid Translator - คู่มือการใช้งาน

## 📋 สารบัญ

1. [ภาพรวมระบบ](#ภาพรวมระบบ)
2. [การตั้งค่าเริ่มต้น](#การตั้งค่าเริ่มต้น)
3. [การแปลเนื้อหา](#การแปลเนื้อหา)
4. [การจัดการคำศัพท์](#การจัดการคำศัพท์)
5. [การแสดงผลหน้าเว็บ](#การแสดงผลหน้าเว็บ)
6. [การตรวจสอบเนื้อหา](#การตรวจสอบเนื้อหา)
7. [การตั้งค่าสิทธิ์](#การตั้งค่าสิทธิ์)
8. [คำถามที่พบบ่อย](#คำถามที่พบบ่อย)

---

## ภาพรวมระบบ

**Gov Hybrid Translator** เป็นปลั๊กอิน WordPress สำหรับแปลเนื้อหาเว็บไซต์ราชการ โดยใช้ระบบ **Hybrid** ที่ผสานการทำงานระหว่าง AI และมนุษย์ พร้อมระบบ **Advanced Workflow** (Draft/Publish) เพื่อการตรวจสอบความถูกต้องสูงสุด

### 🆕 ฟีเจอร์ใหม่ในเวอร์ชัน 2.4.0

- **Translation Workflow:** ระบบตรวจสอบสถานะแบบร่าง (Draft) ก่อนเผยแพร่ (Publish)
- **Smart Glossary:** ระบบคลังคำศัพท์อัจฉริยะ (Regex Pattern) ที่แม่นยำยิ่งขึ้น
- **Status Visibility:** ซ่อนเนื้อหาฉบับร่างจากบุคคลทั่วไป ให้สิทธิ์ Admin ตรวจสอบได้เท่านั้น

| ประเภทเนื้อหา | รองรับ |
|---------------|--------|
| Pages (หน้าเพจ) | ✅ |
| Posts (บทความ) | ✅ |
| Categories/Tags | ✅ |
| Navigation Menus | ✅ |
| Site Title/Tagline | ✅ |
| Custom Post Types | ✅ |

### คุณสมบัติหลัก

- 🤖 **AI Translation** - รองรับ Google, OpenAI, DeepL, Azure, Claude
- 📝 **Manual Translation** - แปลด้วยตนเองได้
- 📚 **Glossary** - กำหนดคำศัพท์เฉพาะราชการ
- 🔄 **Language Switcher** - สลับภาษาแบบ Path-based URL
- 📊 **Dashboard** - จัดการแปลในที่เดียว
- 🔒 **Role-based Permissions** - กำหนดสิทธิ์ตามบทบาท
- 📱 **Avada Theme Integration** - รองรับธีม Avada

---

## การตั้งค่าเริ่มต้น

### 1. เข้าสู่ระบบ

1. เข้า WordPress Admin
2. คลิก **Gov Translator** ในเมนูด้านซ้าย
3. จะเห็นหน้า Dashboard พร้อมสถิติ

### 2. ตั้งค่า AI Translation

ไปที่ **Settings > AI & Translation**

#### AI Providers ที่รองรับ

| Provider | ข้อดี | ข้อจำกัด |
|----------|-------|----------|
| **Google Cloud** | แม่นยำ, รวดเร็ว | ต้องมี API Key |
| **OpenAI GPT** | เข้าใจบริบท | ราคาสูงกว่า |
| **DeepL** | ภาษายุโรปดี | ภาษาไทยจำกัด |
| **Azure Translator** | Enterprise-grade | ตั้งค่าซับซ้อน |
| **Claude AI** | เข้าใจบริบทดี | ช้ากว่า |

#### ขั้นตอนตั้งค่า

1. เลือก **AI Provider**
2. กรอก **API Key**
3. คลิก **Test Connection**
4. ถ้าสำเร็จ → คลิก **Save Settings**

### 3. ตั้งค่า Site Identity (ชื่อเว็บ)

1. ไปที่ **Settings > General**
2. กรอกชื่อเว็บภาษาอังกฤษ
3. กรอก Tagline ภาษาอังกฤษ
4. คลิก **Save**

### 4. ตั้งค่า Language Switcher

1. ไปที่ **Settings > Language Switcher**
2. เลือกรูปแบบ:
   - **Floating Button** - ปุ่มลอยมุมจอ
   - **Menu Integration** - รวมในเมนู
   - **Shortcode** - `[gov_lang_switcher]`
3. ตั้งค่าตำแหน่งและ style
4. คลิก **Save Settings**

---

## การแปลเนื้อหา

### โหมดการแปล

Plugin ใช้ระบบ **Meta-based Translation** (ไม่สร้าง post ซ้ำ):

- เก็บคำแปลใน `post_meta`
- Post เดิมยังคงอยู่
- ลดความซับซ้อนในการจัดการ

### 📄 การแปล Pages/Posts

#### วิธีที่ 1: จาก Translation Tasks

1. ไปที่ **Translation Tasks > Pages** หรือ **Posts**
2. พิมพ์ชื่อภาษาอังกฤษ
3. คลิก **Save**
4. ✅ รายการย้ายไป **Translated**

#### วิธีที่ 2: จาก Editor (Meta Box)

1. เปิด Post/Page ใน Editor
2. หา **Gov Translator** meta box ด้านขวา
3. กรอก Title และ Content ภาษาอังกฤษ
4. คลิก **Update**

#### วิธีที่ 3: ใช้ AI แปลอัตโนมัติ (Advanced Workflow)

1. ไปที่ **Translation Tasks > Posts**
2. คลิก **🤖 AI Translate**
3. ระบบจะบันทึกเป็นสถานะ **Draft (สีเทา)** ทันที
4. **สำคัญ:** เนื้อหาจะยัง **ไม่แสดงบนหน้าเว็บ** จนกว่าจะได้รับการอนุมัติ (Status: Published)

### 🚦 การจัดการ Translation Status (ใหม่)

ระบบเวอร์ชัน 2.4.0 เพิ่มฟีเจอร์สถานะสำหรับการตรวจสอบ:

1. **Draft (ฉบับร่าง)**:
   - เกิดขึ้นเมื่อใช้ AI Translate ครั้งแรก
   - มองเห็นได้เฉพาะในหน้า Admin และผู้ดูแลระบบเท่านั้น
   - ใช้สำหรับตรวจสอบความถูกต้องก่อนเผยแพร่

2. **Published (เผยแพร่)**:
   - เนื้อหาพร้อมใช้งานและแสดงผลบนหน้าเว็บไซต์จริง
   - เปลี่ยนสถานะได้โดยการคลิก **Edit** และเลือก **Status: Published**

### ✏️ การแก้ไขและอนุมัติ (Manual Edit & Approval)

1. คลิกปุ่ม **Edit** หลังรายการแปล
2. ในหน้าต่าง Edit Modal:
   - ตรวจสอบ/แก้ไข **Title**, **Content**, **Excerpt**
   - เปลี่ยน **Status** จาก `Draft` เป็น `Published`
3. คลิก **Save Changes**
4. ตรวจสอบสถานะเปลี่ยนเป็น **Published (สีเขียว)**

### 📁 การแปล Categories/Tags

1. ไปที่ **Translation Tasks > Categories**
2. พิมพ์ชื่อภาษาอังกฤษ
3. คลิก **Save**

**ตัวอย่าง:**

| ภาษาไทย | ภาษาอังกฤษ |
|---------|------------|
| ข่าวสาร | News |
| กิจกรรม | Activities |
| ประกาศ | Announcements |

### 🍔 การแปล Menus

1. ไปที่ **Translation Tasks > Menus**
2. พิมพ์ชื่อภาษาอังกฤษของแต่ละรายการ
3. คลิก **Save**

**ตัวอย่าง:**

| ภาษาไทย | ภาษาอังกฤษ |
|---------|------------|
| หน้าแรก | Home |
| เกี่ยวกับเรา | About Us |
| ติดต่อเรา | Contact Us |

---

## การจัดการคำศัพท์ (Glossary)

### เพิ่มคำศัพท์เฉพาะ

1. ไปที่ **Glossary > Add New**
2. กรอกข้อมูล:
   - **Title:** คำภาษาไทย
   - **English Translation:** คำภาษาอังกฤษ
3. เลือก **Term Type** (ถ้ามี)
4. คลิก **Publish**

### ประเภทคำศัพท์

| ประเภท | ตัวอย่าง |
|--------|----------|
| **ชื่อบุคคล** | นายกรัฐมนตรี → Prime Minister |
| **ตำแหน่ง** | ผู้อำนวยการ → Director |
| **หน่วยงาน** | กระทรวงการคลัง → Ministry of Finance |
| **ศัพท์เฉพาะ** | พ.ร.บ. → Act |

### การทำงานของ Smart Glossary (v2.4.0+)

ระบบได้รับการอัพเกรดให้ฉลาดขึ้น:

1. **Regex Pattern:** ใช้ Regular Expression ในการค้นหาคำศัพท์
2. **Case Insensitive:** ค้นหาโดยไม่สนใจตัวพิมพ์เล็ก/ใหญ่ (เช่น "Bangkok", "bangkok", "BANGKOK")
3. **HTML Safe:** 🛡️ ระบบจะ **ข้าม** การแทนที่คำใน HTML Tags (เช่น `<img src="...">`, `<a href="...">`) เพื่อป้องกันหน้าเว็บพัง

```
ข้อความต้นฉบับ → AI แปล → Smart Glossary Replacement → ผลลัพธ์สุดท้าย
```

---

## การแสดงผลหน้าเว็บ

### URL Structure

Plugin ใช้ระบบ **Path-based URL**:

| ภาษา | URL Pattern |
|------|-------------|
| ไทย (default) | `yoursite.com/page-name/` |
| อังกฤษ | `yoursite.com/en/page-name/` |
| จีน | `yoursite.com/zh/page-name/` |

### วิธีดูเนื้อหาภาษาอังกฤษ

1. **คลิก Language Switcher** - ปุ่มธงชาติ
2. **พิมพ์ URL โดยตรง** - `/en/page-name/`
3. **Query String** - `?lang=en`

### SEO Features

- ✅ **hreflang Tags** - บอก Google ว่ามีหลายภาษา
- ✅ **Canonical URLs** - ป้องกัน duplicate content
- ✅ **Translated Meta** - Meta description แต่ละภาษา

---

## การตรวจสอบเนื้อหา (Content Review)

### ระบบ Review Status

| สถานะ | ความหมาย |
|-------|----------|
| 🟡 Pending | รอตรวจสอบ |
| ✅ Approved | ตรวจสอบแล้ว |
| 🔄 Needs Update | ต้นฉบับเปลี่ยน, ต้องอัพเดท |

### ขั้นตอน Review

1. ไปที่ **Content Review**
2. เลือกรายการที่ต้องการตรวจ
3. ตรวจสอบความถูกต้อง
4. คลิก **Approve** หรือ **Edit**

---

## การตั้งค่าสิทธิ์ (Permissions)

### Roles & Capabilities

ไปที่ **Settings > Permissions**

| สิทธิ์ | Admin | Editor | Author |
|--------|-------|--------|--------|
| Manage Settings | ✅ | ❌ | ❌ |
| Translate Posts | ✅ | ✅ | ✅ |
| Approve Translations | ✅ | ✅ | ❌ |
| Manage Glossary | ✅ | ✅ | ❌ |

---

## คำถามที่พบบ่อย (FAQ)

### ❓ หน้า /en/ ไม่แสดงเนื้อหา?

**ตอบ:**

1. ไปที่ **Settings > Permalinks**
2. คลิก **Save Changes** (flush rewrite rules)
3. Refresh หน้าเว็บ

### ❓ ชื่อเว็บไม่แปลเป็นภาษาอังกฤษ?

**ตอบ:**

1. ไปที่ **Settings > General**
2. กรอกชื่อเว็บภาษาอังกฤษ
3. คลิก **Save**

### ❓ เมนูไม่แสดงภาษาอังกฤษ?

**ตอบ:**

1. ไปที่ **Translation Tasks > Menus**
2. แปลทุกรายการเมนู
3. คลิก **Save**

### ❓ AI แปลไม่ถูกต้อง?

**ตอบ:**

1. เพิ่มคำใน **Glossary**
2. แก้ไขด้วยตนเองใน **Translation Tasks**

### ❓ เกิด "Critical Error"?

**ตอบ:**

1. ตรวจสอบ `wp-content/debug.log`
2. ติดต่อผู้ดูแลระบบ

---

## 📞 ติดต่อและรายงานปัญหา

1. ตรวจสอบ Browser Console (F12)
2. ตรวจสอบ WordPress Debug Log
3. อ่านเอกสาร `TRANSLATION_FLOW.md`

---

## 📚 เอกสารเพิ่มเติม

| เอกสาร | รายละเอียด |
|--------|------------|
| `README.md` | ข้อมูลทั่วไป |
| `TRANSLATION_FLOW.md` | การทำงานของระบบ |
| `CHANGELOG.md` | ประวัติการอัพเดท |
| `SECURITY_GUIDE.md` | คู่มือความปลอดภัย |

---

**เวอร์ชัน:** 2.4.0  
**อัพเดทล่าสุด:** 2026-01-27
