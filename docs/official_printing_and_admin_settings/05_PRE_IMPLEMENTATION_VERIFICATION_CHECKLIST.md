# قائمة التحقق والمراجعة المسبقة قبل بدء التنفيذ
**Pre-Implementation Audit & Verification Checklist**

قبل الشروع في تطبيق التعديلات البرمجية، يجب التأكد من استيفاء كافة المتطلبات التالية:

---

## 1. فحص ملف الشعار المركزي (Branding Asset Check)
- [ ] التأكد من وجود مجلد `public/images/`.
- [ ] إنشاء ووضع ملف الشعار عالي الدقة `logo.png` و `logo.svg` لضمان عدم حدوث صور مفقودة (404 Broken Images) في المطبوعات أو الواجهات.
- [ ] التأكد من أن مسار الشعار الافتراضي في جدول `administrative_settings` مضبوط على `/images/logo.png`.
- [ ] التأكد من صلاحيات مجلد `storage/app/public/branding` لتمكين رفع وتحديث الشعار والختم من لوحة الإعدادات الإدارية.

---

## 2. فحص قاعدة البيانات والمتحكمات (Backend & Database Readiness)
- [ ] التأكد من تسجيل قيم الإعدادات الإدارية الافتراضية في `administrative_settings`.
- [ ] إضافة إعدادات توقيعات السجل العام `student_registry` في `OfficialDocumentSignatory` ضمن Seeder أو الهجرة (Slots: `prepared_by`, `verified_by`, `approved_by`).
- [ ] التأكد من أن `StudentRegistryReportController.php` يعيد بيانات المعهد والتوقيعات والشعار في مخرجات شهادة القيد وحسن السيرة والتقرير السري.

---

## 3. فحص التحميل الأولي للواجهة (Frontend Boot & State Management)
- [ ] استدعاء `loadAdminSettingsMaster()` داخل دالة الإقلاع `init()` في واجهة `dashboard.blade.php`.
- [ ] توفير الدالة المساعدة `getSignatoryInfo(docCode, slotKey, defaultLabel, defaultName)` في كائن Alpine.js لتسهيل قراءة بيانات الموقعين في أي وقت.
- [ ] ربط ترويسات كافة القوالب الحالية بالمتغيرات:
  - `adminSettings.profile.state_name`
  - `adminSettings.profile.supervising_body`
  - `adminSettings.profile.supervising_department`
  - `adminSettings.profile.institute_name`
  - `adminSettings.profile.logo_url`
  - `adminSettings.profile.phone` و `adminSettings.profile.email`

---

## 4. قائمة قوالب الطباعة المستهدفة بالتحديث (Targeted Print Templates)
- [ ] **قالب 1**: `printableOfficialRegistry` (كشف السجل العام للطلاب بكافة خيارات التصفية والفرز).
- [ ] **قالب 2**: `printableEnrollmentCertificate` (شهادة تعريف وقيد طالب معتمدة).
- [ ] **قالب 3**: `printableGoodConductCertificate` (شهادة حسن سيرة وسلوك).
- [ ] **قالب 4**: `printableConfidentialReport` (التقرير السري لملف الطالب).
- [ ] **قالب 5**: `printableStudentCardWrapper` (بطاقة قيد الطالب الأكاديمية).
- [ ] **قوالب إضافية عامة في النظام**:
  - `printableAttendanceReport` (كشف حضور وغياب الطلاب).
  - `printableWarningNotice` (إشعار إنذار غياب رسمي).
  - `officialTranscriptSheet` (كشف الدرجات المعتمد).

---

## 5. اختبار المعاينة والطباعة الفعالة (Quality Assurance)
- [ ] فتح صفحة سجل الطلاب العام والضغط على زر "الطباعة الرسمية للسجل 🖨️".
- [ ] التحقق من مطابقة الترويسة لبيانات صفحة الإعدادات الإدارية.
- [ ] التحقق من ظهور الشعار بوضوح في منتصف الترويسة بدلاً من الرمز القديم.
- [ ] التحقق من ظهور مسميات التوقيعات بدقة (المستخرج، مسجل الطلاب، مدير المعهد).
- [ ] اختبار تعديل اسم المعهد أو الشعار في صفحة الإعدادات الإدارية والتأكد من انعكاسه الفوري في كشف الطباعة.
