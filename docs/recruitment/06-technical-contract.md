<div dir="rtl" align="right">

# العقد التقني — قيَم الـ Enums والجداول الجديدة

> **نسخة 3** — بعد مطابقة الـ ERD. المرجع الملزم لأسماء الأعمدة وقيَمها بين المطورين.

---

## 1. الـ Enums

النمط: `bensampo/laravel-enum` بقيَم `int`، على غرار `App\Enums\LeaveRequestStatus` و `ContractPlace` (القرار `D6`). تُخزَّن كـ `unsignedTinyInteger`.

> القيَم **مجمّدة** — تدخل قاعدة البيانات ولا يجوز تغييرها بعد أول migration. ترتيبها لا يحمل معنى منطقياً؛ لا تُبنى عليه أي مقارنة `>` أو `<`.

### `RecruitmentRequestStatus`

| القيمة | الثابت | الحالة |
|---|---|---|
| 0 | `Draft` | مسودة |
| 1 | `Submitted` | مُرسَل |
| 2 | `UnderReview` | قيد المراجعة |
| 3 | `ChangesRequested` | مطلوب تعديل |
| 4 | `Resubmitted` | أُعيد الإرسال |
| 5 | `Approved` | موافَق عليه |
| 6 | `Rejected` | مرفوض |
| 7 | `Cancelled` | ملغى |
| 8 | `Applied` | مُطبَّق |
| 9 | `FailedApplication` | فشل التطبيق |

الحالات `4` و `7` و `9` أُضيفت بالقرار `D23` — لم تكن في الـ ERD الأصلي.

`Draft = 0` هو الافتراضي، على غرار `LeaveRequestStatus::Pending = 0`.

### `RecruitmentRequestType`

| القيمة | الثابت | النوع |
|---|---|---|
| 0 | `Unknown` | محجوز، لا يُستخدم |
| 1 | `FillVacancy` | ملء شاغر |
| 2 | `IncreaseHeadcount` | زيادة الملاك |
| 3 | `DecreaseHeadcount` | تخفيض الملاك |
| 4 | `CreatePosition` | إنشاء منصب |
| 5 | `ClosePosition` | إغلاق منصب |
| 6 | `CloseVacancy` | إغلاق شاغر |

### `RequestMode` — القرار `D15`

| القيمة | الثابت | الوصف |
|---|---|---|
| 1 | `Normal` | يمر بالمسار الثلاثي كاملاً |
| 2 | `DirectHr` | فتح مباشر بتأكيدات خارجية موثّقة |

### `HiringCampaignStatus`

| القيمة | الثابت | يظهر للعامة | يقبل تقديماً |
|---|---|---|---|
| 0 | `Draft` | ❌ | ❌ |
| 1 | `Published` | ✅ | ❌ |
| 2 | `Open` | ✅ | ✅ |
| 3 | `Closed` | ✅ | ❌ |
| 4 | `Expired` | ✅ | ❌ |
| 5 | `Disabled` | ❌ | ❌ |

### `WorkflowInstanceStatus`

| القيمة | الثابت | الوصف |
|---|---|---|
| 0 | `Running` | خطوة نشطة قائمة |
| 1 | `Completed` | اكتملت كل الخطوات |
| 2 | `Rejected` | أُنهي برفض |
| 3 | `Cancelled` | أُنهي بإلغاء من المُقدِّم |

> **لا توجد قيمة `Blocked`.** أُلغيت لندرتها ولأنها لا تضيف معنى فوق «الخطوة لم تُحسم بعد». هذه القيَم **مطابقة تماماً** لما في الـ ERD، فجدول `recruitment_workflow_instances` لا يحتاج أي تعديل.

### `WorkflowStepStatus`

| القيمة | الثابت |
|---|---|
| 0 | `Pending` |
| 1 | `Active` |
| 2 | `Approved` |
| 3 | `Rejected` |
| 4 | `Skipped` |

### `WorkflowStepKey`

| القيمة | الثابت | من |
|---|---|---|
| 1 | `ManagerApproval` | `direct_manager_id` من منصب مُقدِّم الطلب |
| 2 | `FinanceApproval` | Senior Financial Manager — مركزي |
| 3 | `GovernorateHrApproval` | حسب خريطة إسناد المحافظات (`WF-R3`) |
| 4 | `HrSyriaDirectOpenApproval` | `HR-SYR` — الخطوة الوحيدة في Instance مسار `direct_hr` |

> لا يوجد `ApproverSlot`. كان مقترحاً لخطوة بموافقَين (مدير مباشر وفني) وأُلغي بالقرار `D16` — **موافِق واحد لكل خطوة**، و `workflow_instance_steps` بقراره الواحد صحيح كما هو.

### `ApprovalDecision`

| القيمة | الثابت |
|---|---|
| 1 | `Approve` |
| 2 | `Reject` |
| 3 | `RequestChanges` |

### `WorkflowSkipReason`

| القيمة | الثابت | القاعدة |
|---|---|---|
| 1 | `SelfApprovalEscalated` | `WF-R1` — صُعِّدت لمدير الموافِق، لم تُتجاوز فعلياً |
| 2 | `TopLevelRequester` | `WF-R1` استثناء — المُقدِّم هو CEO، لا مدير أعلى |
| 3 | `NoManagerOnPosition` | المنصب بلا مدير مباشر |

> `WithinApprovedBudget` و `NoFinancialImpact` غير مستخدمتين: المراجعة المالية إلزامية دائماً في المسار المعتمد.

### `ExternalConfirmationDecision`

| القيمة | الثابت |
|---|---|
| 1 | `Approved` |
| 2 | `NotApplicable` |

### `ExternalConfirmationMethod`

| القيمة | الثابت |
|---|---|
| 1 | `Phone` |
| 2 | `Email` |
| 3 | `Meeting` |
| 4 | `Other` |

### `ScopeType`

| القيمة | الثابت |
|---|---|
| 1 | `Program` |
| 2 | `Center` |
| 3 | `City` |
| 4 | `Organization` |

### `RecruitmentEventType`

| القيمة | الثابت | | القيمة | الثابت |
|---|---|---|---|---|
| 1 | `RequestCreated` | | 12 | `RequestApplicationFailed` |
| 2 | `RequestEdited` | | 13 | `PositionChanged` |
| 3 | `VersionCreated` | | 14 | `HeadcountChanged` |
| 4 | `RequestSubmitted` | | 15 | `VacancyCreated` |
| 5 | `WorkflowStarted` | | 16 | `VacancyClosed` |
| 6 | `WorkflowAdvanced` | | 17 | `CampaignStateChanged` |
| 7 | `StepApproved` | | 18 | `JobPostPublished` |
| 8 | `StepRejected` | | 19 | `FormVersionPublished` |
| 9 | `ChangesRequested` | | 20 | `PublicLinkChanged` |
| 10 | `RequestCancelled` | | 21 | `StepSkipped` |
| 11 | `RequestApplied` | | 22 | `DirectHrOpened` |
| | | | 23 | `ExternalConfirmationRecorded` |

`21` يوثّق استثناء `TopLevelRequester`، و `22` و `23` لمسار `direct_hr`. لا يوجد حدث `WorkflowBlocked` — أُلغي مع الحالة.

> `getLocalizedName()` تُنفَّذ على كل enum بنفس توقيع `LeaveRequestStatus` — تُرجع `['value' => ..., 'color' => ...]` حسب اللغة الحالية، لتستخدمها الجداول والشارات في الواجهة.

---

## 2. جدول `recruitment_request_events` — جديد (القرار `D8`)

يُضاف **إلى جانب** `activity_log` ولا يحل محله. الجدول العام يبقى للسجل العام للنظام؛ هذا للخط الزمني للطلب.

| العمود | النوع | ملاحظات |
|---|---|---|
| `id` | bigint PK | |
| `recruitment_request_id` | FK | `onDelete('restrict')` — **لا cascade**، لا يُحذف التاريخ |
| `request_version_id` | FK nullable | النسخة التي وقع عليها الحدث |
| `event_type` | tinyint | `RecruitmentEventType` |
| `workflow_instance_id` | FK nullable | |
| `workflow_instance_step_id` | FK nullable | |
| `workflow_step_key` | tinyint nullable | `WorkflowStepKey` |
| `decision` | tinyint nullable | `ApprovalDecision` |
| `from_status` | tinyint nullable | `RecruitmentRequestStatus` |
| `to_status` | tinyint nullable | `RecruitmentRequestStatus` |
| `actor_user_id` | FK nullable | `null` للأحداث التي يولّدها النظام |
| `actor_role` | string(64) nullable | **نص ثابت** وقت الحدث |
| `scope_type` | tinyint nullable | `ScopeType` وقت الحدث |
| `scope_id` | unsignedBigInteger nullable | |
| `comment` | text nullable | سبب الرفض أو تعليق طلب التعديل |
| `meta` | json nullable | تفاصيل خاصة بالحدث فقط |
| `created_at` | timestamp | بلا `updated_at` — السجل غير قابل للتعديل |

**الفهارس:**

```text
(recruitment_request_id, created_at)   ← بناء الخط الزمني
(event_type)                            ← التقارير
(scope_type, scope_id)                  ← الفلترة حسب النطاق
(actor_user_id)
```

**قواعد ملزمة:**

- `actor_role` و `scope_type` و `scope_id` تُكتب وقت الحدث ولا تُشتق لاحقاً من حالة المستخدم الحالية
- `meta` للتفاصيل الخاصة بالحدث فقط؛ أي حقل يُستعلَم عنه أو يُفلتر به يصبح عموداً حقيقياً
- لا `update` ولا `delete` على هذا الجدول إطلاقاً
- فشل كتابة الحدث **لا يُفشل** العملية الأصلية — يُسجَّل في لوج الأخطاء

---

## 3. جدول `recruitment_request_external_confirmations` — جديد (القرار `D15`)

يحمل التأكيدات الموثّقة في وضع `direct_hr` فقط.

| العمود | النوع | ملاحظات |
|---|---|---|
| `id` | bigint PK | |
| `recruitment_request_id` | FK | `onDelete('restrict')` |
| `step_key` | tinyint nullable | `WorkflowStepKey` للأسطر الثلاثة المُولَّدة؛ `null` لما يضيفه HR |
| `is_generated` | boolean | يميّز السطر المُولَّد عن المضاف يدوياً |
| `confirmer_user_id` | FK nullable | إن كان الشخص مستخدماً في النظام |
| `confirmer_name` | string | الاسم كما سجّله HR — **إلزامي دائماً** |
| `confirmer_position_id` | FK nullable | |
| `decision` | tinyint | `ExternalConfirmationDecision` |
| `method` | tinyint | `ExternalConfirmationMethod` |
| `confirmed_at` | date | تاريخ الموافقة الخارجية |
| `notes` | text nullable | |
| `recorded_by_user_id` | FK → `users` | مستخدم HR الذي سجّل |
| `created_at` · `updated_at` | timestamps | |

**المرفقات:** عبر جدول `media` الموجود على هذا الموديل — لا عمود مسار في الجدول.

**قواعد ملزمة:**

- هذا الجدول **ليس** سجل موافقات. لا يُعرض في الـ Timeline بصيغة «فلان وافق»، بل «HR سجّل تأكيداً خارجياً من فلان عبر الهاتف بتاريخ كذا»
- التحقق قبل السماح بالفتح: الأسطر الثلاثة المُولَّدة موجودة وكل منها `Approved` أو `NotApplicable`
- الأسطر المُولَّدة **لا تُحذف** — يمكن تغيير قرارها إلى `NotApplicable` لا إزالتها

---

## 4. تعديلات على جداول الـ ERD القائمة

### `recruitment_requests`

| العمود | ملاحظات |
|---|---|
| `status` | يتوسّع من 7 حالات إلى **10** — `RecruitmentRequestStatus` |
| `mode` | جديد — `RequestMode` |
| `actual_requester_name` | جديد — من طلب التوظيف فعلياً في وضع `direct_hr` |
| `actual_requester_position_id` | جديد — FK nullable |
| `direct_open_reason` | جديد — text nullable |
| `direct_opened_by_user_id` | جديد — FK nullable |
| `direct_opened_at` | جديد — timestamp nullable |

### `recruitment_request_position_details`

| العمود | ملاحظات |
|---|---|
| `currency` | **جديد** — على غرار `contracts.currency` |
| `salary_per` | **جديد** — على غرار `contracts.salary_per` |

> رقم بلا عملة لا يستطيع المدير المالي مراجعته: في البيانات 265 عقداً بالدولار و2 بالليرة. القرار `D22`.
>
> `funding_source` **لا يُضاف** — `finance_consulted` الموجود يكفي، ومصدر التمويل يُذكر في `detailed_explanation`.

### `recruitment_workflow_instances`

**بلا تعديل.** قيَم `status` الأربع في الـ ERD (`running` · `completed` · `rejected` · `cancelled`) مطابقة للمعتمد. لا تُضاف `blocked` ولا `blocked_reason`.

### `recruitment_workflow_instance_steps`

**بلا تعديل.** قرار واحد لكل خطوة، كما هو مبني (`D16`).

---

## 5. خريطة إسناد HR للمحافظات — القرار `D21`

| المحافظة | تُسند إلى |
|---|---|
| حلب | `HR-SYR` |
| طرطوس | `HR-LAT` |
| أي محافظة بلا HR معيَّن | `HR-SYR` — احتياط عام |

**القواعد الخاصة تسبق الاحتياط العام.**

قابلة للتعديل دون تغيير كود — تُقرأ من `config/recruitment.php` أو من عمود إسناد على جدول `cities`، حسب ما يناسب بنية الـ ERD.

**الطلب لا يتوقف بسبب غياب HR للمحافظة إطلاقاً.**

---

## 6. جداول المرحلة الأولى — 19 جدولاً

| المجموعة | الجداول |
|---|---|
| الطلب ونسخه | `recruitment_requests` · `recruitment_request_versions` · `recruitment_request_position_details` · `recruitment_request_comments` |
| التدقيق والتأكيدات | `recruitment_request_events` ← جديد · `recruitment_request_external_confirmations` ← جديد |
| تعريف الـ Workflow | `recruitment_workflow_definitions` · `recruitment_workflow_definition_versions` · `recruitment_workflow_steps` |
| تنفيذ الـ Workflow | `recruitment_workflow_instances` · `recruitment_workflow_instance_steps` |
| الشواغر والحملات | `vacancies` · `hiring_campaigns` · `recruitment_campaign_invites` |
| الإعلان والنموذج | `job_posts` · `job_post_versions` · `application_forms` · `application_form_versions` |
| الوصول العام | `public_recruitment_links` |

**لا يُحذف أي جدول.** 17 جدولاً في الـ ERD + جدولان جديدان = 19.

</div>
