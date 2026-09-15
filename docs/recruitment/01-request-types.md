<div dir="rtl" align="right">

# مصفوفة أنواع الطلبات — Request Type Matrix

ستة أنواع طلبات. كل نوع له حقول خاصة، قواعد تحقق، وأثر واحد محدد بعد الموافقة النهائية.

## نظرة عامة

| القيمة | النوع | الثابت | الكيان المستهدف | أثر مالي | يُنتج شاغراً |
|---|---|---|---|---|---|
| 1 | ملء شاغر | `FillVacancy` | Vacancy قائم أو Position | نعم | نعم |
| 2 | زيادة الملاك | `IncreaseHeadcount` | Position | نعم | نعم |
| 3 | تخفيض الملاك | `DecreaseHeadcount` | Position | نعم | لا |
| 4 | إنشاء منصب | `CreatePosition` | Position (جديد) | نعم | نعم |
| 5 | إغلاق منصب | `ClosePosition` | Position | نعم | لا |
| 6 | إغلاق شاغر | `CloseVacancy` | Vacancy | لا | لا |

> القيمة `0 = Unknown` محجوزة ولا تُستخدم. المرجع الملزم للقيَم: [06-technical-contract.md](06-technical-contract.md).

> **المراجعة المالية إلزامية على كل الأنواع** في المسار المعتمد. خطة التنفيذ كانت تتيح تجاوزها لـ `FillVacancy` ضمن ميزانية معتمدة، لكن ذلك يحتاج نطاق راتب مخزَّناً وهو غير موجود. راجع البند 1 في القسم 10 من [وثيقة قواعد الـ Workflow](03-workflow-rules.md).

---

## 1. ملء شاغر — `fill_vacancy`

**الغرض:** توظيف شخص على شاغر موجود ضمن ملاك معتمد. لا يغيّر `max_emp`.

| الحقل | إلزامي | ملاحظات |
|---|---|---|
| `position_id` | نعم | يجب أن يكون ضمن نطاق مُقدِّم الطلب |
| `vacancy_id` | لا | إن وُجد شاغر مفتوح يُربط به؛ وإلا يُنشأ شاغر عند الموافقة |
| `justification` | نعم | نص حر |
| `needed_by_date` | نعم | التاريخ المطلوب لشغل المنصب |
| `salary_amount` / `currency` / `salary_per` | نعم | **لا يوجد راتب على `positions`** — راجع `D5` و `D22` |
| `contract_type_id` | نعم | من جدول `contract_types` الحالي |
| `finance_consulted` | نعم | إقرار بأن التنسيق مع المالية تم |

**قواعد التحقق عند الإرسال:**
- `positions.is_active = true`
- `positions.exist_emp < positions.max_emp` — يجب وجود مساحة فعلية في الملاك
- لا يوجد طلب active آخر على نفس الشاغر (راجع المهمة 6)

**الأثر بعد الموافقة:** إنشاء أو تفعيل Vacancy مرتبط بالمنصب. لا تغيير على `max_emp`.

---

## 2. زيادة الملاك — `increase_headcount`

**الغرض:** رفع `positions.max_emp` وإنتاج شاغر جديد.

| الحقل | إلزامي | ملاحظات |
|---|---|---|
| `position_id` | نعم | |
| `additional_count` | نعم | عدد صحيح موجب |
| `current_max_emp` | نعم | يُلتقط من النظام وقت الإرسال (snapshot) |
| `justification` | نعم | |
| `salary_amount` / `currency` / `salary_per` | نعم | |
| `contract_type_id` | نعم | |
| `finance_consulted` | نعم | إقرار بأن التنسيق مع المالية تم |
| `needed_by_date` | نعم | |

**قواعد التحقق:** `positions.is_active = true`، و `additional_count >= 1`.

**الأثر بعد الموافقة:** `max_emp += additional_count` + إنشاء شاغر واحد لكل وحدة مضافة.

**تنبيه Revalidation:** إذا تغيّر `max_emp` في النظام بين الإرسال والموافقة النهائية، تُعاد الحسبة على القيمة الحالية لا على الـ snapshot. راجع `RV-3`.

---

## 3. تخفيض الملاك — `decrease_headcount`

| الحقل | إلزامي | ملاحظات |
|---|---|---|
| `position_id` | نعم | |
| `reduce_by` | نعم | عدد صحيح موجب |
| `current_max_emp` / `current_exist_emp` | نعم | snapshot |
| `reason` | نعم | |
| `effective_date` | نعم | |

**قواعد التحقق — الحرجة:**
- `max_emp - reduce_by >= exist_emp` — **لا يجوز أن ينزل الملاك تحت عدد الموظفين الفعليين**
- يُعاد فحص هذه القاعدة إلزامياً قبل الموافقة النهائية (`RV-4`) لأن `exist_emp` يتغيّر بالعقود

**الأثر بعد الموافقة:** `max_emp -= reduce_by`، وإغلاق أي شواغر مفتوحة زائدة على الملاك الجديد.

---

## 4. إنشاء منصب — `create_position`

**الغرض:** منصب غير موجود في `positions`. تفاصيله تُخزَّن على الطلب حتى الموافقة، ولا تُكتب في HR Master Data قبلها.

| الحقل | إلزامي | ملاحظات |
|---|---|---|
| `program_id` | نعم | ضمن نطاق مُقدِّم الطلب |
| `center_id` | لا | |
| `city_id` | نعم | يحدد HR المحافظة المسنَد حسب خريطة `WF-R3` |
| `department_id` | نعم | |
| `job_level_id` | نعم | من `job_levels` الموجود |
| `title_en` / `title_ar` | نعم | العمودان معاً — اصطلاح `{attribute}_{locale}` |
| `qualifications` | نعم | |
| `responsibilities` | نعم | |
| `max_emp` | نعم | افتراضي 1 |
| `direct_manager_id` | لا | `positions.id` |
| `tech_manager_id` | لا | `positions.id` — لا يُستخدم في مسار الموافقات (`D16`) |
| `salary_amount` / `currency` / `salary_per` | نعم | |
| `contract_type_id` | نعم | |
| `finance_consulted` | نعم | إقرار بأن التنسيق مع المالية تم |

**قواعد التحقق:** `city_id` متوافق مع `center_id` إن وُجد (نفس منطق `Position::scopeInCity`).

**الأثر بعد الموافقة:** إنشاء `Position` ثم إنشاء شواغر بعدد `max_emp` — **داخل معاملة واحدة** (تحذير صريح في المهمة 9).

---

## 5. إغلاق منصب — `close_position`

| الحقل | إلزامي | ملاحظات |
|---|---|---|
| `position_id` | نعم | |
| `reason` | نعم | |
| `effective_date` | نعم | |

**قواعد التحقق — الحرجة:**
- **يُمنع الإغلاق إذا كان على المنصب عقود فعّالة** (`exist_emp > 0` / `activeContracts` غير فارغة)
- يُعاد الفحص قبل الموافقة النهائية (`RV-5`)

**الأثر بعد الموافقة:** `positions.is_active = false` + إغلاق كل الشواغر المرتبطة.

---

## 6. إغلاق شاغر — `close_vacancy`

**الغرض:** إلغاء الحاجة للتوظيف دون المساس بالمنصب. المنصب يبقى فعّالاً وملاكه دون تغيير.

| الحقل | إلزامي | ملاحظات |
|---|---|---|
| `vacancy_id` | نعم | |
| `reason` | نعم | |

**قواعد التحقق:** الشاغر مفتوح، ولا توجد حملة توظيف نشطة عليه (أو يجب إغلاقها أولاً — قرار سياسة).

**الأثر بعد الموافقة:** إغلاق الشاغر فقط. **لا** تغيير على `max_emp` ولا على `is_active`.

> **الفرق الجوهري:** `close_position` يلغي المنصب من الهيكل التنظيمي. `close_vacancy` يقول «لم نعد بحاجة للتوظيف الآن» مع بقاء المنصب معتمداً.

---

## الحقول المالية

`positions` لا يحوي أي عمود راتب — الراتب على `contracts` فقط. فتفاصيل الطلب تحمل راتبها بنفسها:

`salary_amount` · `currency` · `salary_per` · `contract_type_id`

> `currency` و `salary_per` أُضيفا بالقرار `D22`. في البيانات: 265 عقداً بالدولار و2 بالليرة — رقم بلا عملة لا يستطيع المدير المالي مراجعته.
>
> `funding_source` لا يُضاف؛ الحقل `finance_consulted` الموجود يكفي، ومصدر التمويل يُذكر في `detailed_explanation`.

---

## ملخص الأثر بعد الموافقة

| النوع | `max_emp` | `is_active` | Vacancy |
|---|---|---|---|
| `fill_vacancy` | — | — | إنشاء / تفعيل |
| `increase_headcount` | `+n` | — | إنشاء `n` |
| `decrease_headcount` | `-n` | — | إغلاق الزائد |
| `create_position` | تعيين أولي | `true` | إنشاء `max_emp` |
| `close_position` | — | `false` | إغلاق الكل |
| `close_vacancy` | — | — | إغلاق واحد |

</div>
