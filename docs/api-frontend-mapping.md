# REMI API → Frontend mapping

เอกสารนี้ map API จาก Google Doc “REMI Redesign” เข้ากับ data contract ที่หน้า Management ปัจจุบันใช้ โดยยึดหลักว่า Blade ไม่ควรรู้จัก response ดิบจาก API และให้มี adapter/service แปลง response ก่อนส่งเข้า view

## สถานะปัจจุบัน

- ตรวจเมื่อ 11 ตุลาคม 2569: production release `/var/www/remi_ai` ไม่มี route ชุดใหม่ จึงตอบ `404 Not Found`
- พบ source ของ API ใน backup `/var/www/remi_ai_backup_20261011_0920` ซึ่งเป็นไฟล์ก่อน deploy แบบ CI/CD เวลา 16:02 น.
- ทดสอบ API จาก backup ผ่าน temporary localhost server บน VM แล้ว endpoint สำหรับ dashboard, patient detail, health, nutrition, diabetes, food review และ conversations ตอบ `200` ด้วยบัญชี test
- หน้า Management ชุดใหม่ใน Git ยังใช้ข้อมูล mock ใน `app/Http/Controllers/ApiController.php`
- ห้ามคัดลอก `ApiController.php` จาก backup ทับทั้งไฟล์ เพราะ backup ไม่มีโค้ดหน้า Management รุ่นล่าสุด ต้องย้ายเฉพาะ API methods/routes หรือแยกเป็น controller/service ใหม่

## Endpoint และหน้าที่ใช้

| API | Request | หน้า/ส่วนที่ใช้ | Provider ปัจจุบัน | สถานะ |
| --- | --- | --- | --- | --- |
| `POST /api/dashboard_overview` | `doctor_id`, `patient_page`, `task_page` | `/dashboard`, `/patients` | `dashboardOverviewData()`, `patientsDirectoryData()` | พบใน backup และทดสอบ `200` |
| `POST /api/get_patient_detail` | `doctor_id`, `user_id` (LINE ID) | `/patients/{patient}/detail` และ header ของทุกแท็บผู้ป่วย | `patientDetailData()` | พบใน backup และทดสอบ `200` |
| `POST /api/get_health_history` | `doctor_id`, `user_id` | `/patients/{patient}/health` | `patientDetailData().health` | พบใน backup และทดสอบ `200` |
| `POST /api/get_nutrition_history` | `doctor_id`, `user_id`, `start_date`, `end_date` | `/patients/{patient}/nutrition` | `patientDetailData().nutrition` | พบใน backup และทดสอบ `200` |
| `POST /api/get_diabetes_history` | `doctor_id`, `user_id`, `start_date`, `end_date` | `/patients/{patient}/glucose` | `patientDetailData().glucose` | พบใน backup และทดสอบ `200` |
| `POST /api/food_review_queue` | `doctor_id`, filters, pagination | `/nutrition-review` คิวด้านซ้าย | `nutritionReviewData()` | พบใน backup และทดสอบ `200` |
| `POST /api/food_review_detail` | `doctor_id`, `meal_id` | `/nutrition-review` รายละเอียดมื้อ | `nutritionReviewData()` | พบใน backup; ต้องมี `meal_id` สำหรับทดสอบ |
| `POST /api/doctor_patient_conversations` | `doctor_id`, filters, pagination | `/conversations` และรายการกลุ่ม | `conversationsHistoryData()` | พบใน backup และทดสอบ `200` |
| `POST /api/patient_conversation_detail` | `doctor_id`, `user_id`, filters, pagination | conversation แบบ 1:1 | `conversationsHistoryData()` | พบใน backup |
| `POST /api/insert_food` | patient/doctor IDs, meal และ foods | บันทึกอาหารจากหน้า review | ยังไม่มีการเชื่อมจริง | เป็น write endpoint; ต้องทดสอบสิทธิ์และ transaction ก่อนใช้ |

## 1. Dashboard Overview

Request ที่ระบุในเอกสาร:

```json
{
  "doctor_id": "test",
  "patient_page": 1,
  "task_page": 1
}
```

### Frontend contract

| Contract ที่หน้าเว็บใช้ | ใช้แสดงผล | Transformation ที่ adapter ต้องทำ |
| --- | --- | --- |
| `updated_label` | ข้อความเวลาอัปเดตบน dashboard | format timestamp เป็นภาษาไทย |
| `summary[]` | การ์ดสรุป 4 ใบ | map ค่า total patient, AI reviewed/pending และ continuous care; `tone`, `icon`, `progress` เป็น presentation metadata ที่ frontend เติมเอง |
| `activity[]: {day,value}` | จำนวนผู้รับบริการที่มีกิจกรรมกับระบบรายวัน | รวมอาหาร/รูปภาพ ออกกำลังกาย น้ำหนัก น้ำตาล ลูกดิ้น วิตามิน และข้อความ แล้วนับผู้รับบริการไม่ซ้ำต่อวัน; เติมวันที่ไม่มีข้อมูลด้วย `0` |
| `weight_signals[]` | pie/doughnut สัญญาณน้ำหนัก | group/count ด้วย `weight_status`; label และสีเป็น frontend metadata |
| `weight_total` | จำนวนรวมกลางกราฟน้ำหนัก | ใช้ผลรวมทุกสถานะ หรือ total จาก API |
| `actions[]` | รายการ “ต้องดำเนินการ” | map task list จาก `task_page`; แปลง severity เป็น `tone` |
| `follow_up[]` | ตารางผู้รับบริการที่ต้องติดตาม | map patient list จาก `patient_page`; ต้องส่ง identifier ที่เปิดหน้ารายละเอียดได้ |
| `filters[]` + `patients[]` | หน้า `/patients` | ใช้รายการ/จำนวนผู้รับบริการจาก response เดียวกัน ถ้า API ส่งมาให้ครบ |

### `weight_status`

| API value | ข้อความหน้าเว็บ | tone/color |
| --- | --- | --- |
| `0` | ไม่มีการบันทึก | `muted` / `#a3979b` |
| `1` | น้ำหนักปกติ | `success` / `#34aa80` |
| `2` | น้ำหนักต่ำกว่าเกณฑ์ | `info` / `#4e79e8` |
| `3` | น้ำหนักเกินเกณฑ์ | `warning` / `#f07a2b` |
| `4` | ภาวะแทรกซ้อน | `danger` / `#df476d` |

> ห้าม map `weight_status` ด้วย truthy/falsy เพราะค่า `0` เป็นสถานะที่มีความหมาย

## 2. Patient Detail

Request ที่ระบุในเอกสาร:

```json
{
  "doctor_id": "test",
  "user_id": "Ucd1d3d9310f1afd627bbd1ea729f5be5"
}
```

### Frontend contract

| Contract ที่หน้าเว็บใช้ | ใช้แสดงผล | หมายเหตุ |
| --- | --- | --- |
| `id` | route parameter ของหน้าผู้ป่วย | ปัจจุบัน mock ใช้ HN แต่ API ระบุ `user_id` เป็น LINE ID; ต้องแยก `user_id` กับ `hn` ชัดเจน |
| `user_id` | key ที่ส่งต่อไป health/nutrition API | ไม่ควรแสดงตรง ๆ |
| `hn` | HN ที่แสดงใน UI | ห้ามใช้แทน LINE ID ใน API request |
| `name`, `age`, `pregnancy` | header ผู้ป่วย | กำหนดหน่วย `pregnancy` ให้ชัดว่าเป็นสัปดาห์ |
| `due_date` | กำหนดคลอด | format เป็นวันที่ไทยที่ frontend/adapter จุดเดียว |
| `risk_label`, `risk_tone` | badge ความเสี่ยง | tone เป็น presentation metadata |
| `last_updated` | เวลาข้อมูลล่าสุด | ควรได้ timestamp แบบ ISO 8601 จาก API แล้วค่อย format |
| `summary[]` | การ์ด overview: น้ำหนัก, ออกกำลังกาย, อาหาร, น้ำตาล | ถ้า endpoint นี้ไม่มีข้อมูลสรุปครบ ให้ merge จาก health/nutrition/glucose response ใน service layer |
| `weight_chart` | กราฟน้ำหนักใน overview | ถ้าซ้ำกับ health history ให้ health API เป็น source of truth |
| `health_summary[]` | สรุปการบันทึกสุขภาพ | aggregate จาก endpoint ประวัติแต่ละประเภท |
| `follow_ups[]` | ประเด็นติดตาม | ต้องมี timestamp และ severity ที่เป็น machine-readable |

### Identifier ที่ควรใช้

หน้า directory ควรเก็บทั้งสองค่า:

```php
[
    'id' => $patient['user_id'], // route/API key
    'user_id' => $patient['user_id'],
    'hn' => $patient['hospital_num'],
]
```

ถ้าต้องการให้ URL แสดง HN ให้ controller resolve HN → `user_id` ก่อนเรียก API ห้ามส่ง HN เข้า field `user_id` โดยตรง

## 3. Health History

### Frontend contract

| Contract ที่หน้าเว็บใช้ | ข้อมูลที่ต้องได้จาก API |
| --- | --- |
| `health.weight.labels` | อายุครรภ์/วันที่ของแต่ละจุด |
| `health.weight.values` | น้ำหนักที่บันทึก |
| `health.weight.recommended_min/max` | ช่วงน้ำหนักแนะนำ; หาก API ไม่มีต้องคำนวณใน domain service ไม่ใช่ใน Blade |
| `health.latest[]` | timeline รวม น้ำหนัก/ออกกำลังกาย/ลูกดิ้น เรียงใหม่ไปเก่า |
| `health.exercise.labels/values` | นาทีออกกำลังกายรายวัน |
| `health.exercise.history[]` | เวลา, ประเภท, ระยะเวลา, ความหนัก |
| `health.fetal.labels/values` | จำนวนลูกดิ้นรายวัน |
| `health.fetal.history[]` | วันที่, จำนวนครั้ง, ระยะเวลาที่นับ |

กติกา normalize:

- ส่งค่าเชิงตัวเลขเป็น number ไม่รวมหน่วย เช่น `62.4`, `28`, `8`
- แยก timestamp ออกจากข้อความ display
- เรียง chart จากเก่าไปใหม่ และ history จากใหม่ไปเก่า
- วันที่ไม่มีข้อมูลใช้ `null` สำหรับ line chart; ใช้ `0` เฉพาะ metric ที่ `0` มีความหมายจริง

## 4. Nutrition History

Request ต้องใช้ช่วงวันที่จาก filter ของหน้า:

```json
{
  "doctor_id": "test",
  "user_id": "Ucd1d3d9310f1afd627bbd1ea729f5be5",
  "start_date": "2026-01-01",
  "end_date": "2027-01-01"
}
```

### Frontend contract

| Contract ที่หน้าเว็บใช้ | ข้อมูลที่ต้องได้/คำนวณ |
| --- | --- |
| `nutrition.summary[]` | พลังงานเป้าหมาย, ค่าเฉลี่ยที่บริโภค, จำนวนวันที่ถึงเป้า, จำนวนมื้อ/สถานะตรวจ |
| `nutrition.insights[]` | insight ที่คำนวณจากช่วงวันที่; `tone` เติมใน frontend adapter |
| `nutrition.energy.labels` | วันที่ตามช่วงที่เลือก |
| `nutrition.energy.carbs/protein/fat/fiber` | พลังงานหรือปริมาณของแต่ละ nutrient ต้องยืนยันหน่วยจาก API ก่อนต่อกราฟ |
| `nutrition.nutrients[]` | ค่าเฉลี่ย, target, unit ของสารอาหาร |
| `nutrition.vitamins[]` | ชื่อวิตามินและสถานะรายวัน |
| `nutrition.meals[]` | วันที่, มื้อ, เวลา, รายการอาหาร, พลังงาน, review status |

จุดที่ต้องแก้พร้อมตอนเชื่อมจริง:

- วันใน vitamin calendar ปัจจุบัน hard-code และต้องสร้างจาก `start_date`–`end_date`
- badge “ขาดรวม ... kcal” ปัจจุบันคำนวณจากค่าคงที่ ต้องใช้ผลรวม target minus consumed จาก API
- จำนวนมื้อในหัวตารางต้องใช้จำนวน record จริง ไม่ใช่ scale จาก 7 วัน

## API เพิ่มเติมที่พบใน backup

API ที่ไม่ถูกบันทึกไว้ใน Google Doc แต่มี implementation อยู่ใน backup:

1. `get_diabetes_history` คืน `blood_sugar_history`, `blood_sugar_by_meal_period`, `trend_and_summary`, `current_insulin` และ `medication_adjustment_history`
2. `food_review_queue` คืน paginated review queue
3. `food_review_detail` คืนรายละเอียดมื้อ รายการอาหาร และ audit logs
4. `doctor_patient_conversations` คืน conversation list, group info และข้อความแบบแบ่งหน้า
5. `patient_conversation_detail` คืนข้อมูลผู้รับบริการ สรุปสุขภาพ และข้อความ 1:1
6. `insert_food` เป็น write endpoint สำหรับสร้าง meal transaction/items

สิ่งที่ยังขาดหรือยังไม่ชัด:

1. endpoint สำหรับยืนยัน/แก้ review status หลังตรวจอาหาร
2. endpoint บันทึกการปรับ insulin จากฟอร์มหน้าเว็บ
3. endpoint ส่งข้อความและเปลี่ยน unread/read status
4. authentication middleware ที่บังคับใช้สม่ำเสมอ ปัจจุบันบาง method รับ `doctor_id` จาก request โดยตรง

## รูปแบบ response ที่แนะนำ

ทุก endpoint ควรคืน envelope เดียวกัน:

```json
{
  "success": true,
  "data": {},
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 1284,
    "updated_at": "2026-10-11T09:00:00+07:00"
  },
  "errors": []
}
```

ข้อกำหนดสำคัญ:

- ใช้ ISO 8601 สำหรับวันเวลา และระบุ timezone
- number ต้องเป็น JSON number ไม่ใช่ข้อความที่มีหน่วย
- pagination ต้องแยกสำหรับ patient และ task เพราะ request ใช้ `patient_page` และ `task_page`
- ค่าที่เป็น enum เช่น status/severity/meal type ต้องมีรายการค่าที่แน่นอน
- empty collection ใช้ `[]`; ข้อมูลเดี่ยวที่ไม่มีใช้ `null`

## ลำดับการเชื่อมที่แนะนำ

1. ย้ายเฉพาะ API routes/methods จาก backup เข้า Git โดยแนะนำให้แยกเป็น controller/service ใหม่ ไม่ merge controller ทั้งไฟล์
2. เพิ่ม imports ที่ API ใช้ (`CarbonImmutable`, `JsonResponse`) และตรวจ config/database migrations ที่ methods อ้างถึง
3. ทำ authentication/authorization ให้ทุก endpointใช้แพทย์จาก session และตรวจสิทธิ์ต่อผู้รับบริการ ไม่เชื่อ `doctor_id` จาก request เพียงอย่างเดียว
4. สร้าง mapper แยกตาม endpoint ให้คืน contract ตามเอกสารนี้; frontend อยู่ใน Laravel app เดียวกันจึงควรเรียก service โดยตรงแทน HTTP loopback
5. ใช้ response test ที่เก็บจาก VM สร้าง fixture + contract test สำหรับ null, empty list, pagination และ `weight_status` ทุกค่า โดยลบข้อมูลระบุตัวบุคคลก่อน commit
6. เปลี่ยน controller หน้าเว็บให้ใช้ service/mapper และคง mock เป็น fallback เฉพาะ local environment
7. deploy ผ่าน GitHub Actions แล้วตรวจ `php artisan route:list` และ smoke test ทั้ง public URL
