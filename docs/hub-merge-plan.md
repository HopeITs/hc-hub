# HC-Hub: دمج HR داخل Tracking (2026-11-10)

**الأساس:** نسخة hc-tracking (`C:\laragon\www\hc-hub`) وقاعدة بياناتها `hc_hub`.
**مصدر HR:** فرع `rms-main` من hc-hr (`005ef69e`)، وهو مسحوب لـ worktree للقراءة فقط.
**الهدف:** قاعدة بيانات وحدة، فيها علاقات حقيقية بين جداول الوحدتين، وكود مقسوم على وحدتين.

## الوضع الحالي (اللي شفته)
- Tracking بيقرأ HR من قاعدة تانية عن طريق اتصال `hr` (البادئة `hchr_`)، والموديلات اللي بتستخدمه 9: `HrUser` و `HrEmployee` و `HrCity` و `HrCenter` و `HrProgram` و `HrDepartment` و `HrPosition` و `HrContract` و `HrJobLevel`.
- جداول Tracking أصلاً بتخزّن أرقام HR: `employee_id` و `city_id` و `center_id` و `program_id` و `department_id`. يعني ربط هالأعمدة ما بيحتاج إعادة ترقيم.
- **الاستثناء الوحيد هو `users`:** مستخدم Tracking إلو رقم خاص، ومربوط بـ HR عن طريق `hr_user_id`، وفي 36 عمود FK بيأشروا عليه.
- `programs`: نفس الأرقام 1–15 بالوحدتين. HR فيه الاسم والكود، و Tracking فيه الاستراتيجية والمدير.
- جداول موجودة بالاتنين وبدها دمج: `users` و `programs` و `roles` و `permissions` و `model_has_*` و `role_has_permissions` و `media` و `notifications` و `migrations` و `sessions` و `cache` و `jobs`.

## الهيكل
```
app/                  منصة: auth, layout, nav, desk, permissions
modules/Core/         البيانات المشتركة (Modules\Core\): User, Program, City (محافظة), Center
modules/Tracking/     Models, Livewire, Http, views, routes, migrations  (Modules\Tracking\)
modules/Hr/           Models, Livewire, Http, views, routes, migrations  (Modules\Hr\)
```
كل وحدة إلها `<Name>ServiceProvider`، وبيسجّل الـ routes والـ views (`core::` و `tracking::` و `hr::`) والـ migrations والـ Livewire.

**Core (قرار المستخدم، 2026-11-10):**
- الجداول المشتركة هي: `users` و `programs` و `cities` (المحافظات، ومن ضمنها "Syria" كمحافظة رمزية بتغطي الكل) و `centers`.
- Tracking و HR بيعتمدوا عليها، ولا وحدة منهن بتملكها.
- **البرامج والمراكز بتنضاف من Tracking لما تنفتح proposal:** بيروح جسر `program_requests` (اللي كان بيطلب البرنامج من HR، يعني W-34b)، وبينعمل البرنامج أو المركز مباشرة بـ Core من فورم الـ proposal. HR بيقرأ بس.
- الطلب الوحيد المعلّق (`pending`) بيتحوّل لبرنامج بـ Core.
- الموظفين (`employees`) بيضلوا بـ HR، و `users.employee_id` بيأشر عليهم.

## قرارات (افتراضية، بغيّرها إذا بدك)
1. **أرقام المستخدمين:** بيضل رقم HR هو الأساس، لأن HR هو مصدر الحسابات (الإيميل والباسورد والموظف). أعمدة Tracking الإضافية (2FA و desk و nav) بتنضاف لجدول `users`، والـ 36 FK بيتعاد ترقيمهم.
2. **`programs`:** جدول واحد فيه أعمدة الوحدتين.
3. **الأدوار والصلاحيات:** دمج الأدوار والصلاحيات من الوحدتين بجداول وحدة، مع إعادة ترقيم الـ ids. `spatie/permission` بيضل ^8.
4. **الحزم:** إضافة `activitylog` و `medialibrary` و `browsershot` من HR.
5. **الاستيراد:** أمر `php artisan hub:import-hr` بينعاد تشغيله لحد الإطلاق، فبيانات HR الجديدة بتنسحب بأي وقت.
6. **السرعة:** كود HR بينقل على أنماط Tracking (eager loading والكاش ونفس الـ layout)، ومنقيس سرعة صفحات HR بعد النقل.

## المراحل
| # | المرحلة | النتيجة |
|---|---|---|
| 1 | إنشاء `modules/Core` (User, Program, City, Center)، ونقل Tracking لـ `modules/Tracking` | التطبيق بيشتغل متل قبل، والاختبارات ناجحة |
| 2 | نقل كود HR من `rms-main` لـ `modules/Hr` | الموديلات والـ CRUD والواجهات والـ routes تحت `hr::` |
| 3 | أمر `hub:import-hr` | جداول HR بدون بادئة جوا `hc_hub`، ودمج الجداول المشتركة مع إعادة الترقيم |
| 4 | شيل اتصال `hr` | `HrProgram` و `HrCity` و `HrCenter` و `HrUser` بيصيروا موديلات Core، وباقي `Hr*` بيصيروا `Modules\Hr`، ومنضيف FKs حقيقية لـ Core |
| 4b | البرامج والمراكز من الـ proposal | إنشاء مباشر بـ Core من فورم الـ proposal، وإلغاء `program_requests` |
| 5 | توحيد الدخول والقائمة والصلاحيات | تسجيل دخول واحد، وقائمة فيها الوحدتين حسب الدور، و `/en` و `/ar` |
| 6 | تحقق | الاختبارات، ومطابقة عدد السجلات، وفحص بالمتصفح للوحدتين، وقياس السرعة |
