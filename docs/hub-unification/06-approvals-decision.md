# 06 — قرار الموافقات: إعادة تقييم D9

> مهمة الخارطة 5.4. كُتب في 2026-11-20 قبل وحدة المشتريات.
> مصدر كل وصف هو الكود نفسه، والمسار مذكور بجانب كل workflow.
> لا كود ولا migration في هذه الوثيقة.

## الخلاصة في سطر

نُبقي D9 كما هو. لا محرّك عام الآن. نوحّد شكل الأثر فقط (الخيار A).
عند المشتريات نبدأ بنواة رفيعة (الخيار B) فقط إذا تحققت شروط التفعيل في القسم 7.
المحرّك الكامل القابل للإعداد (C) مرفوض.

---

## 1. ماذا وجدنا

- في الكود **16 workflow** فيها قرار موافقة أو مراجعة. يُضاف إليها workflow مخطّط في المخزون.
- لكل workflow enum خاص به، وأعمدة `*_by` / `*_at` خاصة به، وقاعدة Policy خاصة به.
- لا يوجد محرّك فعلي إلا في **التوظيف**. فيه تعريفات بنسخ، و instances، وخطوات، وجدول أحداث، و resolver للموافق.
- النمط المتكرّر في الباقي واحد تقريباً:
  - قفل الصف (`lockForUpdate`).
  - التحقق من الحالة المتوقعة.
  - التحقق من الـ Gate.
  - كتابة من قرّر ومتى.
  - إشعار بعد الـ commit.
- لكن **من يقرّر** يختلف في كل workflow. هذا هو الجزء الصعب.

---

## 2. جدول المقارنة

الاختصارات: **خ** = خطوة، **إ** = إرجاع للتعديل، **ت** = تفويض.

| # | Workflow | الحالات | من يقرّر | خطوات | إ | ت | أين الأثر | الظهور في "بانتظاري" | الدليل |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | إجازة | Pending / Accepted / Rejected | شاغل منصب المدير المباشر (`position.direct_manager_id`) على عقد ساري اليوم | خ واحدة | لا. الرفض يحتاج سبباً | نعم: HR بصلاحية `leaves.decide` "on_behalf" إذا كل المدراء في إجازة، أو "no_manager" | أعمدة `approved_by`، `decision_basis`، `decided_on_behalf_of`، `reject_reason` + `activities` | `hr-inbox` (decide) + Needs you | `modules/Hr/Actions/Leave/DecideLeaveRequest.php`، `modules/Hr/Support/Leave/LeaveDecisionAuthority.php`، `modules/Hr/Enums/LeaveRequestStatus.php` |
| 2 | طلب توظيف | 10 حالات (Draft … Applied / FailedApplication) | resolver من بيانات HR الحيّة لكل خطوة. يفشل مغلقاً إذا وُجد صفر أو أكثر من مرشّح | عدة خطوات متتالية (Manager، Finance، Governorate HR، Direct open) | نعم: `RequestChanges` مع تعليق وقائمة حقول مفتوحة | لا | صف الخطوة + جدول `recruitment_request_events` (دور الفاعل، النطاق، من/إلى) | Needs you عبر `DeskDecisions::recruitment` | `modules/Hr/Actions/Recruitment/RecruitmentApprovalService.php`، `RecruitmentApproverResolver.php`، `RecruitmentRequestStateMachine.php`، `RecruitmentWorkflowInstanceService.php` |
| 3 | تعديل عقد | Draft / Approved / Applied | موظف HR يسجّل أن الورقة الموقّعة عادت. القرار نفسه خارج النظام | خ واحدة | لا. التعديل يُستبدل وهو مسودة | لا | أعمدة `approved_by`، `approved_at`، `applied_at` | لا | `modules/Hr/Actions/Contracts/ContractAmendmentService.php` |
| 4 | طلب تعديل ملف شخصي | pending / approved / rejected | HR من صندوق العمل | خ واحدة | لا. يوجد `reason` | لا | أعمدة `decided_by`، `decided_at`، `reason` | `hr-inbox` (profile:approve) | `modules/Hr/Models/ProfileChangeRequest.php`، `modules/Hr/Support/Inbox/WorkInbox.php` |
| 5 | يوم عطلة الميلاد | pending / decided | HR | خ واحدة | لا | لا | أعمدة `decided_by`، `decided_at` | `hr-inbox` | `modules/Hr/Actions/Leave/DecideBirthdayHoliday.php`، `modules/Hr/Support/Leave/BirthdayHoliday.php` |
| 6 | دورة حياة المقترح | 10 حالات فعّالة (concept note … completed) | `proposals.update` أو مالك المشروع. "approved" تعني أن المانح الخارجي وافق | انتقالات حسب جدول `ProposalTransitions` | الرفض يحتاج سبباً ≥ 10 أحرف | لا | جدول `proposal_status_notes` + حدث `ProposalStatusChanged` | Needs you (خطوات المالك) | `modules/Tracking/Actions/Proposals/TransitionProposal.php`، `modules/Tracking/Models/ProposalStatusNote.php` |
| 7 | مراجعو المقترح | seen / غير seen | شاغلو slots من مناصب HR (MEAL، مالية، جمع تبرعات، CEO) + مالك المشروع + مدير البرنامج | استشاري، غير مانع | لا. ملاحظة اختيارية | لا | `seen_at`، `note` + activity `proposal.reviewer_seen` | `reviews` + Needs you | `modules/Tracking/Actions/Proposals/MarkProposalReviewed.php`، `modules/Tracking/Support/ReviewWorkItems.php` |
| 8 | طلب تغيير مقترح | pending / approved / rejected + مراجعة no_objection / remarks / skipped | مراجعة استشارية (مالية، MEAL). القرار لصاحب `proposals.approve` | مراجعات ثم قرار | Remarks تحتاج ملاحظة ≥ 10. تُفحص الحمولة من جديد عند الموافقة | لا. slot شاغر يُتخطّى تلقائياً | جدولان: `proposal_change_requests`، `proposal_change_request_reviews` + activities | لا مصدر خاص | `modules/Tracking/Actions/Proposals/ReviewProposalChange.php`، `ApproveProposalChange.php`، `modules/Tracking/Models/ProposalChangeRequest.php` |
| 9 | تقرير (Report) | Draft / Reviewed / Approved | `reports.approve`، ليس الكاتب | خ واحدة بعد Reviewed | نعم: `ReturnReportToDraft` بلا سبب | لا | **لا عمود للموافق ولا سبب**. فقط `activities` | لا | `modules/Tracking/Actions/Reports/ApproveReport.php`، `ReturnReportToDraft.php` |
| 10 | تقرير شهري: تحقق | Draft / Submitted / Validated | من يمرّ بـ `validatableBy` | خ واحدة | نعم (`returned_by`) | لا | أعمدة `validated_by`، `validated_at` + activity | Needs you (validate) | `modules/Tracking/Actions/MonthlyReports/ValidateMonthlyReport.php`، `ReturnMonthlyReport.php` |
| 11 | تقرير شهري: إعادة فتح | pending / granted / denied | `reports.reopen` + شاغل slot مدير MEAL، ليس الطالب ولا المُعدّ | خ واحدة | لا | لا | جدول `monthly_report_reopen_requests` (`decided_by`) | لا | `modules/Tracking/Actions/MonthlyReports/DecideMonthlyReportReopen.php`، `modules/Tracking/Policies/MonthlyReportPolicy.php` |
| 12 | خطة سنوية | Draft / InReview / Approved | مدير MEAL | خ واحدة | نعم: sendBack مع سبب إلزامي | لا | أعمدة `approved_by`، `sent_back_by`، `sent_back_reason` | لا | `modules/Tracking/Actions/AnnualPlans/ReviewActivityPlan.php`، `modules/Tracking/Policies/ActivityPlanPolicy.php` |
| 13 | تحديث الخطة | Draft / Sent / OfficerApproved / Reviewed / Seen / Returned | سلسلة ثلاثية: موظف MEAL ← مدير MEAL ← مدير البرنامج | 3 خطوات | نعم: إرجاع لمن (`returned_to`) مع سبب + أسئلة | لا | أعمدة لكل مستوى + جدول `plan_update_questions` | لا | `modules/Tracking/Actions/AnnualPlans/ReviewPlanUpdate.php` |
| 14 | خطة استراتيجية | Draft / Submitted / Approved | مدير البرنامج (`programs.manager_id`) أو `strategies.approve-unmanaged` | خ واحدة | نعم: sendBack مع سبب إلزامي | جزئي: صلاحية عند غياب المدير | أعمدة `approved_by`، `sent_back_by`، `sent_back_reason` | لا | `modules/Tracking/Actions/StrategicPlans/ReviewStrategicPlan.php` |
| 15 | اعتماد مانح | pending / approved (والرفض بحذف/رفض) | `donors.approve` (المدير العام) | خ واحدة | لا | لا | أعمدة `approved_by`، `approved_at` + activity `donor.approved` | لا | `modules/Tracking/Actions/Donors/ApproveDonor.php`، `DeclineDonor.php` |
| 16 | مراجعة/قبول مهمة | UnderReview → Done، أو Delivered → قبول | منشئ المهمة | خ واحدة | نعم: إرجاع مهمة القسم | لا | حالة المهمة + activities | Needs you (approve / accept) | `modules/Tracking/Policies/TaskPolicy.php`، `modules/Tracking/Actions/Tasks/ReturnDepartmentTask.php` |
| مخطّط | إتلاف/بيع أصل (المخزون I13) | Pending / Approved / Rejected | `inventory.approve-disposal` (المدير العام افتراضياً). الطالب لا يقرّر طلبه | خ واحدة | لا | لا | أعمدة `requested_by`، `approved_by`، `approved_at` + activity | مخطّط في 6.1 كـ work item | `docs/inventory/contracts.md`، `docs/inventory/prompts/I13.md`، `docs/superpowers/plans/2026-11-20-inventory-module.md` |

ملاحظة: يوجد أيضاً `decided_by` في `modules/Hr/Models/AttendanceDayDecision.php` و `modules/Hr/Models/RenewalJourney.php`. شكلهما مثل الصف 4. لم ندرسهما بعمق.

---

## 3. المشترك فعلاً

- قفل الصف ثم التحقق من الحالة المتوقعة. هذا يمنع القرار المزدوج.
- Gate أو Policy قبل القرار.
- تسجيل من قرّر ومتى.
- سبب إلزامي عند الرفض أو الإرجاع في أغلب الحالات.
- منع القرار على الطلب الذاتي في أغلب الحالات (إجازة، إعادة فتح، تقرير، إتلاف).
- إشعار بعد الـ commit.
- الظهور في "بانتظاري" عبر `WorkItemSource` (`app/Support/Modules/Contracts/WorkItemSource.php`).

هذا المشترك صغير. كله أنماط كود، وليس بيانات مشتركة.

## 4. المختلف فعلاً

- **أساس من يقرّر.** خمسة أنواع مختلفة:
  - منصب في سلسلة HR (إجازة، توظيف).
  - شاغل slot (MEAL، مالية، CEO).
  - صلاحية فقط (مانح، تقرير، تغيير مقترح).
  - علاقة بالسجل (مدير البرنامج، منشئ المهمة، مالك المشروع).
  - قرار خارجي يُسجَّل فقط (مانح خارجي للمقترح، ورقة عقد موقّعة).
- **الوزن.** بعض المراجعات استشارية ولا تمنع شيئاً (مراجعو المقترح). بعضها مانع.
- **عدد الخطوات.** أغلبها خطوة واحدة. اثنان فقط متعددا الخطوات (التوظيف، تحديث الخطة).
- **الأثر الجانبي للقرار.** يختلف بالكامل:
  - خصم رصيد الإجازة.
  - apply handlers في التوظيف داخل transaction ثانية.
  - تطبيق تعديل العقد في تاريخه.
  - تغيير حالة الأصل.
- **مكان الأثر.** أعمدة على السجل، أو جدول خاص، أو `activities`، أو `proposal_status_notes`.
- **التفويض.** موجود في الإجازة فقط (`on_behalf`). وجزئياً في الخطة الاستراتيجية.

الخلاصة: الاختلاف في القواعد، لا في الشكل. والقواعد هي ما سيطلب المحرّك العام أن نصفه كإعدادات.

## 5. فجوات حالية (ليست سبباً لمحرّك)

- `ApproveReport` و `ReturnReportToDraft` لا يحفظان من وافق ولا سبب الإرجاع. يعتمدان على `activities` فقط.
- `activities` لا تحمل حقولاً موحّدة للقرار. كل workflow يكتب `properties` بشكله.
- تعديل العقد لا يحمل إلا `approved_by`. القرار الحقيقي على ورق خارج النظام.
- مصادر "بانتظاري" ثلاثة فقط (`needs`، `hr-inbox`، `reviews`). بعض القرارات لا تظهر فيها: تغيير المقترح، إعادة فتح التقرير الشهري، الخطة السنوية، تحديث الخطة، الخطة الاستراتيجية، المانح.

هذه الفجوات تُحل بالخيار A. لا تحتاج محرّكاً.

---

## 6. الخيارات

### A — كل workflow منفصل + شكل أثر موحّد فقط

- **ماذا:** نتفق على مفاتيح ثابتة لأثر القرار داخل `activities`: الفاعل، الوقت، القرار، السبب، الخطوة، و"نيابةً عن". كل workflow يكتبها عبر helper واحد في `RecordsActivity`. كل workflow يسجّل `WorkItemSource` إذا لم يكن ظاهراً.
- **الكلفة:** منخفضة. helper واحد. ومهمة صغيرة لكل workflow يُلمس.
- **الخطر:** منخفض. لا تغيير على أي قاعدة أو حالة.
- **أثر الترحيل:** لا migration. السجلات القديمة تبقى بشكلها. القارئ يتعامل مع الشكلين.
- **حدّه:** لا يعطي "كل الموافقات في مكان واحد" كاستعلام SQL. يعطيها عبر `activities`.

### B — نواة رفيعة مشتركة

- **ماذا:** جدول قرارات مشترك واحد + contract صغير. كل workflow يبقي حالاته وقواعده ومن يقرّر. النواة تسجّل فقط "طُلب قرار" و"اتُّخذ قرار".
- **الكلفة:** متوسطة. جدول، وموديل، و contract، واختبارات. ثم ربط كل workflow جديد.
- **الخطر:** متوسط. خطر ازدواج الحقيقة: حالة السجل في عموده، وحالة القرار في الجدول المشترك. يجب أن يُكتبا في نفس الـ transaction دائماً.
- **أثر الترحيل:** لا ترحيل إجباري للقديم. الـ 16 workflow تبقى كما هي. فقط الجديد (المشتريات، الإتلاف) يستخدم النواة. ترحيل القديم اختياري ولاحق.
- **فائدته:** "بانتظاري" يقرأ من مصدر واحد للـ workflows الجديدة. تقرير موحّد للتدقيق.

### C — محرّك كامل قابل للإعداد

- **ماذا:** تعريف الخطوات والموافقين والشروط كبيانات، مثل محرّك التوظيف لكن عام لكل الوحدات.
- **الكلفة:** عالية جداً. محرّك التوظيف وحده: حوالي 8 خدمات، و 6 موديلات، و 5 enums، وست فحوص قبل كل قرار.
- **الخطر:** عالٍ.
  - أساس "من يقرّر" له خمسة أنواع (القسم 4). وصفها كإعدادات = لغة قواعد صغيرة.
  - الأثر الجانبي (خصم رصيد، apply) يبقى كوداً على أي حال.
  - كل خطأ في الإعداد = موافقة من شخص خطأ.
- **أثر الترحيل:** ثقيل. نقل 16 workflow يعني لمس حالات وأعمدة واختبارات مستقرّة. أو ترك عالمين متوازيين، وهذا أسوأ.
- **الحكم:** لا يوجد مستهلك يحتاج الإعداد بلا كود. المنظمة صغيرة، والقواعد تتغيّر نادراً، وتتغيّر بقرار مكتوب. **مرفوض.**

---

## 7. ماذا تحتاج المشتريات والمخزون

### المخزون (الإتلاف والبيع، I13)

- خطوة واحدة. موافق واحد بصلاحية (`inventory.approve-disposal`).
- منع القرار على الطلب الذاتي. منع القرار المزدوج.
- هذا **ليس** مستهلكاً ثالثاً بالمعنى الذي قصده D9. شكله مثل اعتماد المانح (الصف 15) تماماً.
- القرار: يُبنى كـ Action عادي (`RequestDisposal` / `DecideDisposal`) كما في `docs/inventory/contracts.md`. يكتب الأثر بالشكل الموحّد (A). لا ينتظر أي نواة.
- سؤال مفتوح في `docs/inventory/decisions.md`: هل المدير المالي يوافق أيضاً؟ إذا صار الجواب "الاثنان معاً"، تصبح خطوتين. ما زال لا يحتاج محرّكاً.

### المشتريات (الوحدة القادمة)

لم تُكتب لها خطة بعد. من طبيعة المشتريات في منظمة مثل هذه، المتوقّع:

- طلب شراء، ثم موافقة مدير مباشر، ثم موافقة مالية، وربما المدير العام فوق حد مبلغ.
- **عتبات مبلغ** تحدد عدد الخطوات. هذا غير موجود في أي workflow حالي.
- ربط بمنحة/خط موازنة، فيدخل مدير البرنامج أو المالية حسب المصدر.
- إرجاع للتعديل.
- تفويض عند الغياب، مثل الإجازة.

هذا أقرب workflow إلى التوظيف. لذلك المرشّح الطبيعي هو **استخراج نمط التوظيف** (instance + steps + events + resolver) كنواة B. ليس بناء C.

---

## 8. التوصية

**الآن (قبل المشتريات):** الخيار A.

- مفاتيح موحّدة لأثر القرار في `activities`.
- سدّ فجوة `ApproveReport` / `ReturnReportToDraft` (الفاعل والسبب) عند أول لمس لهما.
- كل قرار ينتظر شخصاً يظهر في "بانتظاري" عبر `WorkItemSource` موجود أو جديد.
- الإتلاف في المخزون يُبنى كـ Action عادي.

**عند المشتريات:** الخيار B، **فقط إذا تحقق شرطان على الأقل من هذه:**

1. المشتريات تحتاج **أكثر من خطوة** موافقة متتالية.
2. عدد الخطوات أو الموافقين **يتغيّر حسب المبلغ** أو مصدر التمويل.
3. المالك يريد **تقرير تدقيق واحد** لكل الموافقات عبر الوحدات.
4. نحتاج **تفويضاً** عاماً عند الغياب في أكثر من workflow واحد.

إذا لم يتحقق شرطان: المشتريات تُبنى مثل التوظيف داخل وحدتها، وتكتب الأثر بالشكل الموحّد. ويؤجَّل B مرة أخرى.

**C:** لا. لا يُعاد النظر فيه إلا إذا طلب المالك صراحة أن يغيّر غير المطوّرين مسارات الموافقة بلا نشر كود.

### رسم أدنى للـ contract (أسماء فقط، للخيار B إذا تفعّل)

جداول:

- `approval_requests` — طلب قرار مرتبط بسجل (polymorphic)، مع اسم الـ workflow والحالة.
- `approval_steps` — خطوة واحدة بموافق واحد محسوب وقت الدخول.
- `approval_decisions` — الأثر: من، متى، القرار، السبب، "نيابةً عن".

واجهات:

- `ApprovalSubject` — السجل الذي يُطلب عليه القرار.
- `ApproverResolver` — كل workflow يكتب واحداً خاصاً به. يفشل مغلقاً، مثل `RecruitmentApproverResolver`.
- `DecisionHandler` — الأثر الجانبي للقرار. كود في الوحدة، لا إعداد.
- `ApprovalWorkItems` — يطبّق `WorkItemSource` ويقرأ الخطوات المفتوحة للمستخدم.

Enum: `ApprovalOutcome` (Approve، Reject، RequestChanges). يُعاد استخدام `ApprovalDecision` من التوظيف إذا نُقل إلى Core.

الحدود: النواة لا تعرف الأدوار ولا المناصب ولا المبالغ. كل ذلك في الـ resolver الخاص بالوحدة.

---

## 9. الأسئلة الستة

| السؤال | الجواب |
| --- | --- |
| لماذا | 16 workflow بقواعد مختلفة في "من يقرّر". المشترك أنماط كود فقط. المحرّك العام الآن = تعقيد بلا مستهلك يحتاجه. المخزون ليس مستهلكاً ثالثاً حقيقياً. |
| ما الذي يتأثر | الآن: لا يتأثر أي workflow في حالاته أو قواعده. فقط شكل `properties` في `activities` لقرارات جديدة، وفجوة التقارير عند لمسها. عند تفعيل B: المشتريات فقط، وربما الإتلاف لاحقاً. |
| من يعتمد عليه | صفحة "بانتظاري" (D7). الخط الزمني (D6). المخزون (I13). المشتريات لاحقاً. |
| كيف | helper واحد لكتابة أثر القرار بمفاتيح ثابتة. `WorkItemSource` لكل قرار لا يظهر. عند الشروط: استخراج نمط التوظيف إلى نواة B في Core. |
| كيف يُفحص | اختبار لكل قرار يتأكد أن الأثر مكتوب بالمفاتيح الموحّدة. اختبار أن القرار يظهر في "بانتظاري" للموافق الصحيح فقط. اختبار القرار المزدوج والقرار على الطلب الذاتي. |
| التراجع | A: لا يحتاج تراجعاً. المفاتيح إضافية فقط. B: الجداول جديدة ولا تلمس القديم. التراجع = حذفها وإعادة الـ workflow الجديد لأعمدته. |

---

## 10. ما يجب ألا يُفعل

- لا محرّك عام قابل للإعداد (C).
- لا ترحيل للـ 16 workflow الحالية إلى أي نواة "لأجل التوحيد".
- لا جدول قرارات مشترك قبل تحقق شروط القسم 8.
- لا جعل مراجعات المقترح الاستشارية مانعة بحجة التوحيد. هي غير مانعة بقرار.
- لا تحويل "approved" في المقترح إلى موافقة داخلية. هي قرار المانح الخارجي.
- لا نقل أساس "من يقرّر" إلى صلاحيات فقط. المنصب والـ slot والعلاقة بالسجل قرارات عمل حقيقية.
- لا كتابة الأثر الجانبي (خصم رصيد، apply، تغيير حالة أصل) كإعداد. يبقى كوداً في وحدته.
- لا تكرار قواعد "من يقرّر" داخل `WorkItemSource`. المصدر يقرأ نفس الحساب الذي تقرأه صفحته.
- لا تغيير محرّك التوظيف الآن. هو المرجع إذا تفعّل B.

---

## 11. أسئلة مفتوحة للمالك

1. هل المشتريات تحتاج عتبات مبلغ تغيّر عدد الموافقين؟
2. هل تريد تقرير تدقيق واحداً لكل الموافقات عبر الوحدات؟
3. من يوافق على الإتلاف: المدير العام وحده، أم مع المدير المالي؟
4. هل التفويض عند الغياب مطلوب خارج الإجازة؟
