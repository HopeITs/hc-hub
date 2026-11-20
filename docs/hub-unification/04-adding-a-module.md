# HC-Hub — كيف تضيف وحدة (module)

هذا الدليل يصف ما **يفعله الكود اليوم** (2026-11-20)، وليس نيّة التصميم فقط. كل بند مذكور معه الملف الذي تحققنا منه.
المرجع في التصميم: [02-target-design.md](02-target-design.md) (§1) و [decisions.md](decisions.md) (D10، D12). المهمة: 5.1 في [03-roadmap.md](03-roadmap.md).

في الأمثلة، `Foo` هو اسم الوحدة الجديدة، و `foo` اسمها الصغير.

## 1. الهيكل

الوحدات الموجودة: `modules/Core`، `modules/Hr`، `modules/Tracking`. الوحدة الجديدة تُبنى على نمط `modules/Hr` (الأحدث).

```
modules/Foo/
  FooServiceProvider.php        الجذر: namespace Modules\Foo
  Models/  Policies/  Actions/  Support/  Console/  Enums/  Observers/
  Livewire/Concerns/            (اختياري)
  Http/Controllers/             (للملفات والتصدير)
  database/migrations/
  resources/views/pages/        صفحات Livewire (SFC)
  resources/views/components/
  routes/                       ملف لكل منطقة (area)
```

| الخطوة | ما يحدث فعلاً | المصدر |
| --- | --- | --- |
| تحميل الأصناف | لا شيء تفعله: `composer.json` يربط `Modules\` بالمجلد `modules/` (PSR-4). مجلد جديد يُحمَّل مباشرة | `composer.json` (autoload) |
| تسجيل الـ provider | **يدوي** في `bootstrap/providers.php` (لا اكتشاف تلقائي). أضف `Modules\Foo\FooServiceProvider::class` | `bootstrap/providers.php` |
| Migrations | `$this->loadMigrationsFrom(__DIR__.'/database/migrations')` في `boot()` (Hr يفعل هذا). Tracking لا يفعل: migrations الخاصة به في `database/migrations/` العامة. للوحدة الجديدة استخدم مجلد الوحدة | `HrServiceProvider::boot` |
| Views | `$this->loadViewsFrom(__DIR__.'/resources/views', 'foo')` ← `view('foo::...')` | `HrServiceProvider`، `TrackingServiceProvider` |
| صفحات Livewire | `Livewire::addNamespace('foo', viewPath: __DIR__.'/resources/views/pages')` ثم `Route::livewire('...', 'foo::employees.index')`. مكوّنات SFC بأسمائها القصيرة: `Livewire::addLocation(viewPath: ...)` (Tracking فقط) | الملفان أعلاه |
| الترجمة (lang) | **لا تحمّل الوحدات ترجمتها.** لا يوجد `loadTranslationsFrom` في الكود. ملفات Hr موجودة في `lang/{en,ar}/hr_*.php` في الجذر، وتُستعمل بمفاتيح مثل `hr_nav.employees` | `lang/en/hr_nav.php`؛ لا نتيجة لـ `loadTranslationsFrom` في `app/` و `modules/` |
| Factories | في `database/factories/` العامة (لا مجلد factories داخل الوحدة) | `AppServiceProvider::configureFactories` |
| أوامر artisan | `$this->commands([...])` في `boot()` | `HrServiceProvider::boot` |
| علاقات على موديلات وحدة أخرى | `Model::resolveRelationUsing(...)` من provider **صاحب العلاقة** (مثال: Hr يضيف علاقات على `User` و `Program` من Core؛ Tracking يضيف علاقات على `Employee`) | `registerCoreBridge`, `registerEmployeeRelations` |

### الـ provider: القالب

```php
class FooServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'foo');
        Livewire::addNamespace('foo', viewPath: __DIR__.'/resources/views/pages');

        $modules = $this->app->make(ModuleRegistry::class);
        // ... انظر §2

        if (! $this->app->routesAreCached()) {
            $this->loadAreaRoutes(); // نفس دالة Hr
        }
    }
}
```

`ModuleRegistry` يُسجَّل singleton في `AppServiceProvider::register()` (سطر 112)، فهو جاهز قبل أن يعمل `boot()` لأي وحدة.

### السياسات (Policies)
`Modules\Foo\Policies\{Model}Policy` تُكتشف بتخمين Laravel نفسه (كما في Hr و Tracking). لا تسجيل يدوي إلا لموديل يعيش في وحدة أخرى (مثال: `Gate::policy(Program::class, ProgramPolicy::class)` في Tracking).

## 2. الـ registry: ما تسجّله الوحدة

الملف: `app/Support/Modules/ModuleRegistry.php`. يحمل **أسماء أصناف فقط**، ويُنشئ المصدر من الـ container عند القراءة. كل المصادر تُسجَّل من `boot()` الخاص بالوحدة. لا ترتيب مضمون إلا ترتيب التسجيل (وترتيب الـ providers في `bootstrap/providers.php`: Tracking ثم Hr).

العقود في `app/Support/Modules/Contracts/`. هناك **11 عقداً**:

| العقد | لماذا | دالة التسجيل | مثال حقيقي |
| --- | --- | --- | --- |
| `DestinationSource` | صفحات القائمة الجانبية، شريط Ask، أزرار Desk، Quick access: `pages()` تعيد route, label, icon, hero, group, allows, bindsAdministrators, under | `addDestinations()` | `Modules\Hr\Support\Desk\HrDestinations` |
| `DeskPanelSource` | بطاقات Desk: `owns()`, `component()`, `kind()`, `definitions()`, `routePanels()`, `roleLayouts()` | `addDeskPanels()` | `Modules\Hr\Support\Desk\HrPanels` |
| `DeskAgendaSource` | صفوف "Coming up" في Desk: `between($user, $from, $to)` | `addDeskAgenda()` | `Modules\Hr\Support\Inbox\DeskAgenda` |
| `SubjectLinkSource` | إلى أين يقود سجل الوحدة من إشعار أو صف نشاط: `handles($morphType)`, `routeFor($type, $id)` | `addSubjectLinks()` | `Modules\Hr\Support\Inbox\HrSubjectLinks` |
| `EmployeeTabSource` | تبويب يضيفه المصدر إلى ملف الموظف (`?tab=`): `key`, `label`, `visibleTo`, `component` | `addEmployeeTabs()` | `Modules\Tracking\Support\EmployeeProjectsTab` |
| `CalendarLayerSource` | طبقة في التقويم: `key`, `label`, `icon`, `leads`, `allows`, `between` | `addCalendarLayer()` | `HolidayCalendarLayer`، `LeaveCalendarLayer` (Hr) |
| `TimelineSource` | وقائع مؤرَّخة في الخط الزمني للموظف. يقرأ الجداول الدائمة وليس سجل النشاط (يُحذف قديمه) | `addTimeline()` | `ContractsTimelineSource`، `EquipmentTimelineSource`، `JourneysTimelineSource` (Hr)؛ `ProjectMembersTimelineSource` (Tracking) |
| `SearchSource` | سجلات يجدها شريط Ask بالاسم. المصدر يقرر الرؤية ولا يعيد صفاً لا يفتحه المستخدم؛ يربط الإدخال (`%` و `_` حرفيان: `App\Support\Like`) | `addSearch()` | `Modules\Hr\Support\Search\EmployeeSearchSource` |
| `WorkItemSource` | ما ينتظر شخصاً (صفحة "بانتظاري" `waiting.index`): `allows`, `href`, `count`, `items`. يقرأ نفس حساب صفحته، لا يكرر القواعد | `addWorkItems()` | `InboxWorkItems` (Hr)؛ `ReviewWorkItems` (Tracking) |
| `MetricSource` | أرقام المنظمة: `metrics()` تعيد key => [label, resolve(cities)]. المفتاح فريد عبر كل الوحدات. تُقرأ فقط عبر `App\Support\Metrics\OrgMetrics`. التعريف يعدّ ولا يقرر من يرى | `addMetrics()` | `Modules\Hr\Support\Metrics\HrMetrics`، `TrackingMetrics` |
| `ScheduleSource` | المهام المجدولة: `schedule(Schedule $schedule)`. `AppServiceProvider` يطبّق كل المصادر على الـ scheduler. لا تجدول في مكان آخر | `addSchedule()` | `Modules\Hr\Console\HrSchedule`، `TrackingSchedule` |

أمثلة تسجيل حقيقية: `HrServiceProvider::registerDesk()` (التسجيلات كلها) و `TrackingServiceProvider::boot()` (التبويب، الخط الزمني، المقاييس، الجدولة، بنود العمل).

ملاحظات من الكود:
- **روابط القائمة** هي `DestinationSource` (لا يوجد عقد "nav" منفصل). `Destinations::Groups` قائمة ثابتة: `overview, workspace, strategy_planning, projects_delivery, monitoring_evaluation, funding_finance, people, recruitment, administration, you`. مجموعة `group` في `pages()` يجب أن تكون واحدة منها (D11: القائمة حسب العمل).
- المصدر نفسه هو من يتحقق أن الـ route موجود: `HrDestinations` يتجاوز الصفحة إن كان `Route::has($route)` كاذباً. السجل لا يفعل ذلك.
- ملصق الصفحة (`label`) مفتاح ترجمة، مثل `hr_nav.employees`.
- مصادر البحث والخط الزمني وبنود العمل وبطاقات Desk تقرر الرؤية بنفسها (`allows`)؛ الصفحات التي تقرؤها لا تعرض مصدراً رفض.
- تعريفات مؤشرات الأداء (`PlanIndicator` definitions)، وهي "نقطة التسجيل التاسعة" المذكورة في decisions.md، **لا وجود لها** في الـ registry اليوم. الموجود: `addMetrics` (العدّادات).

## 3. الصلاحيات

- **الكتالوج**: `config/access-control.php`. مصفوفة `$groups` اسم الوحدة ← الإجراءات؛ كل إدخال يصبح صلاحية `{module}.{action}`. مثال: `'foo' => ['view', 'create', 'update', 'delete']`.
- **الإضافة**: عدّل الملف ثم شغّل `php artisan permissions:sync` (`--dry-run` لرؤية ما سيُضاف). الأمر **يضيف فقط** (صلاحيات، أدوار، منح لدور وفق baseline)؛ لا يسحب شيئاً من دور ولا يلغي تعديلات واجهة المستخدم. `RolesAndPermissionsSeeder` يعيد ضبط كل الأدوار، لقاعدة جديدة فقط.
- **انتبه إلى القاعدة الضمنية**: كل صلاحية تنتهي بـ `.view` تُمنح لكل الأدوار تلقائياً (`$viewEverything`) إلا ما في `$restrictedViews` أو `$hrPermissions`. وحدة جديدة حساسة (مال، رواتب) يجب أن تضع `foo.view` في إحدى هاتين القائمتين، وإلا رآها الجميع.
- **السياسات تسأل عن صلاحيات لا عن أسماء أدوار** (هدف 4.3): `$user->can('foo.update')`، `->can('foo.view')` في الـ routes، `$user->canAny([...])` في القائمة (انظر `HrDestinations`). ما زال بعض سياسات Tracking يستدعي `hasRole/hasAnyRole` (`MonthlyReportPolicy`، `PlanUpdatePolicy`، `TaskPolicy`، `ProjectPolicy`)؛ لا تقلّده. استثناءات مقصودة في الـ config: `administrator_roles` (Super Admin يملك كل شيء)، `observer_roles`، `meal_manager_only`.
- **المنح المحدودة (`access_grants`)**: جدول يمنح دوراً أو صلاحية لمستخدم على "نطاق" لفترة (`App\Models\AccessGrant`, `App\Support\Access\AccessScope`, `GrantScopedAccess`/`RevokeScopedAccess`). **مطفأ افتراضياً** (`ACCESS_GRANTS_ENABLED=false`) والقارئ الوحيد اليوم `ProjectPolicy`. أنواع النطاق: `project, program, city, center, department` (`access-control.access_grants.scope_types`). استعمله فقط إن احتاجت وحدتك صلاحية على سجل بعينه.
- **أسماء الأدوار المرادفة**: `role_aliases` في نفس الملف (يربط أدوار HR بأدوار المنصة)، ولا يمنح شيئاً.

## 4. سجل النشاط

- أضف `use App\Concerns\RecordsActivity;` للموديل ونفّذ `App\Notifications\TracksActivity`:
  - `activityModule(): string`: اسم مجموعة الصلاحيات (مثل `'foo'`). هو نفس مفتاح `config('notifications.modules')` و `{module}.view`.
  - `activityProgramId(): ?int`، `activityLabel(): string`، `activityEmployeeFields(): array`، و`activityEvents()` (الحقول المهمة مرتبة).
  - `activityNotifies(): false` للسجلات التي تُسجَّل ولا تُرسل إشعارات (هكذا تفعل سجلات HR) فلا تُرسل `FanOutActivity`.
- **morph map إجباري**: `Relation::enforceMorphMap` عام. أي موديل يُسجَّل عليه نشاط أو يظهر في علاقة polymorphic (ملفات، ملاحظات، إشعارات) **يجب** أن يُضاف إلى `AppServiceProvider::configureMorphMap()` بمفتاح قصير (الأعمدة VARCHAR(50)): `'foo_item' => FooItem::class`. وإلا يرمي `ClassMorphViolationException`. هذا ملف في `app/` (انظر الثغرات).
- **`activities.employee_id`** (عمود "عمّن هذا السجل"): يُملأ تلقائياً في `ActivityRecorder::aboutEmployee()`: إن كان الموضوع `employee` فهو نفسه، وإلا من `properties['employee_id']` الذي يمرّره الموديل في `activityProperties()` (يُتحقق من وجود الموظف). مرّر `employee_id` إن كان السجل يخص موظفاً حتى يظهر في صفحته.
- لا تقرأ سجل النشاط كمصدر لحقائق دائمة: يُحذف بعد `notifications.retention.activity_years`. للخط الزمني استعمل `TimelineSource` يقرأ الجداول.
- `hr_activity_log` القديم قراءة فقط (D6).
- لرابط الصف: سجّل `SubjectLinkSource` (§2) ليعرف `App\Notifications\SubjectLinks` أين يذهب النقر.

## 5. الإشعارات

مسار واحد: `ActivityRecorder` ← `RecipientResolver` ← `RecipientFilter`.

1. أنشئ صنفاً يرث `App\Notifications\Recipients\ModuleRecipients` (مثال: `Modules\Hr\Support\Notifications\NoticeRecipients`، أو الأصناف في `modules/Tracking/Notifications/Recipients/`). يحدد المستلمين المباشرين وأدوار المراقبة.
2. سجّله في `config/notifications.php` تحت `modules`: `'foo' => FooRecipients::class`. المفتاح = `activityModule()`.
3. `RecipientFilter` يزيل: الفاعل، من لا يملك `foo.view`، ومن كتم الوحدة (إلا المستلم المباشر). صلاحية مختلفة؟ `view_permissions` في نفس الملف.
4. وحدة **مفتوحة** (المُرسل يسمّي الأشخاص من السجل نفسه: طالب إجازة، مديره...) تُضاف إلى `open_modules` فلا يُطبَّق عليها فحص الصلاحية (مثال اليوم: `leaves`، `recruitment`).
5. أيقونة اللافتة: `module_icons` في نفس الملف.
6. أحداث لها أهمية/إيقاف من الإدارة: `App\Notifications\EventCatalog`.
7. المنبّهات المجدولة (deadlines، digests): `scanners` في `config/notifications.php` + `App\Notifications\Alerts\Scanner`.
- البريد لمرشحين خارجيين (توظيف) مسار خاص بـ HR (D7). لا تفتح مساراً ثانياً لمستخدمي النظام.

## 6. الملفات

- مخزن واحد: جدول **`media`** المشفّر (`App\Models\Media`، `mediable_type` + `mediable_id`، `collection`)، لا `hr_media`. التشفير والكتابة عبر `App\Support\StoredFiles::save()`؛ المفتاح من `FileKeyring`. جدول `hr_media` يُنقل بأمر `hub:move-hr-media` (نسخ ← تحقق ← بلا حذف).
- `StoredFiles::save()` يقبل اليوم `Media|MonthlyReportFile|ReportFile|TaskAttachment`. وحدة جديدة تستعمل `Media` فقط.
- `mediable_type` يمرّ عبر morph map: سجّل موديل المالك (§4).
- `collection` قيمة من `App\Enums\MediaCollection` (enum في `app/`): للوحدة الجديدة أضف case (انظر الثغرات). امتداد الملف من `App\Enums\FileType`.
- `StoredFiles::FileTables` قائمة الجداول التي فيها ملفات (`EncryptExistingFiles` و `DecryptStoredFile` و `ReencryptSecrets` تقرؤها): لا تنشئ جدول ملفات خاصاً بك دون إضافته.
- التنزيل والمعاينة: controller في الوحدة بعد فحص `Policy` (انظر `Modules\Tracking\Http\Controllers\ProjectFileDownloadController`، `Modules\Hr\Http\Controllers\Employees\EmployeeFileController`).

## 7. الروابط والـ routes

- **D12**: الوحدات الجديدة تأخذ **بادئة URL** واسم route بنفس البادئة. Hr يفعل ذلك: `Route::prefix('hr')->name('hr.')` (وللمناطق الكبيرة `prefix('hr/attendance')->name('hr.attendance.')`). Tracking يبقى **بلا بادئة** ولا يُغيَّر.
- تحميل routes في Hr: `loadAreaRoutes()` يقرأ `routes/*.php` (ملف لكل منطقة) داخل `['web', 'auth', EnsureTwoFactorIsEnabled::class]`، وملفات `routes/public/*.php` تحت `web` فقط لصفحات الضيوف. Tracking يحمّل `routes/web.php` تحت `web` ويضع الوسيط داخله بنفسه. **اتبع نمط Hr** (auth + 2FA من المجموعة، لا تكرره في كل ملف).
- لا تحمّل routes عند `routesAreCached()` (الشرط في القالب أعلاه).
- كل route محمي بـ `->can('foo.view')` أو بـ Policy، وإلا فالصفحة مفتوحة لكل من سجّل دخوله.
- أسماء الـ routes التي ستظهر في القائمة تمرّ عبر `DestinationSource` (§2)، وبدونه الصفحة تعمل لكنها لا تظهر في القائمة ولا في Ask.
- روابط قديمة: لا نكسرها؛ إن غيّرت رابطاً أضف redirect واختبار smoke (03-roadmap، خطر الروابط).

## 8. اتجاه الاعتماد

| من | مسموح | ممنوع |
| --- | --- | --- |
| `Modules\Core` | `app/` | `Modules\Hr`، `Modules\Tracking` |
| `Modules\Hr` | `Core`، `app/` | `Modules\Tracking` |
| `Modules\Tracking` | `Core`، `app/`، (Hr: baseline ينقص ولا يزيد) | Hr في كود جديد |
| وحدة جديدة | `Core`، `app/` | أي وحدة أخرى مباشرة |
| `app/` | عقود `ModuleRegistry` | موديلات الوحدات (baseline ينقص ولا يزيد) |

- ما تحتاجه من وحدة أخرى: اطلبه عبر عقد من العقود أعلاه، أو أضف عقداً جديداً في `app/Support/Modules/Contracts/` مع دالة `add...`/`...Sources()` في `ModuleRegistry`. وإن احتجت علاقة على موديل غيرك فاستعمل `resolveRelationUsing` من **provider الوحدة الجديدة** (الوحدة الجديدة تعرف الأقدم، لا العكس).
- **الاختبار المعماري**: `tests/Arch/ModuleBoundariesTest.php` يقرأ القواعد من `tests/Arch/module-boundaries-allowlist.php`. كل قاعدة: `source` لا تستعمل `forbidden`، مع قائمة `allowed` **تنقص فقط** و `max` مثبّت. اليوم القواعد الست: Core×Hr، Core×Tracking، Hr×Tracking (كلها `max = 0`)، Tracking×Hr (`max = 52`)، App×Hr (`max = 11`)، App×Tracking (`max = 47`).
  - لا تضف صنفاً للقائمة أبداً. صنف جديد يكسر القاعدة يُصلَح.
  - **وحدتك الجديدة غير مغطاة** حتى تضيف لها قواعد (انظر الثغرات): أضف إدخالات `max = 0`: `Modules\Foo` لا تستعمل `Modules\Hr` ولا `Modules\Tracking`، و`Modules\Core` لا تستعمل `Modules\Foo`.

## 9. الاختبارات

- المكان: `tests/Feature/` (الوحدات لا تحمل مجلد tests: لا `modules/Hr/tests`). اختبارات Hr في `tests/Feature/Hr/`؛ اختبارات Tracking في جذر `tests/Feature/`. للوحدة الجديدة: **`tests/Feature/Foo/`**.
- suites في `phpunit.xml`: `Unit`، `Feature`، `Arch`.
- الأوامر (Pest):

```
php artisan test --compact tests/Feature/Foo
php artisan test --compact --testsuite=Arch
php artisan test --compact tests/Feature/ScheduleTest.php
vendor/bin/pint --dirty --format agent
```

- `tests/Feature/ScheduleTest.php` **يثبّت قائمة المهام المجدولة كاملة**: إضافة `ScheduleSource` تعني تعديل هذا الاختبار.
- `tests/Feature/AccessControlCatalogTest.php` يثبّت أفعال وحدات في الكتالوج؛ شغّله بعد تعديل `config/access-control.php`.
- الموديلات في الاختبارات تُنشأ بـ factories (مجلد `database/factories/`).
- PHPStan يغطي `modules/` (`phpstan.neon`).
- لكل `ModuleRecipients`/مصدر registry: اختبر أنه لا يعيد سجلاً لا يراه المستخدم (الرؤية في المصدر).

## 10. ثغرات معروفة

أشياء **لا تزال تُلزمك بتعديل `app/` أو `config/` أو وحدة أخرى** عند إضافة وحدة اليوم. لا أخفي شيئاً منها:

| # | الثغرة | المكان | الأثر |
| --- | --- | --- | --- |
| G1 | تسجيل الـ provider يدوي | `bootstrap/providers.php` | سطر واحد (مقبول) |
| G2 | **morph map مركزي**: كل موديل polymorphic يُضاف في `AppServiceProvider::configureMorphMap()`. يستورد موديلات Tracking و Hr بالاسم | `app/Providers/AppServiceProvider.php` (~سطر 436) | تعديل `app/` إجباري. لا نقطة تسجيل في الـ registry |
| G3 | **الكتالوج مركزي**: صلاحيات الوحدة في `config/access-control.php` (`$groups`) | `config/access-control.php` | تعديل ملف مشترك، وفحص `*.view` الضمني |
| G4 | **الإشعارات**: `config/notifications.php` (`modules`، `module_icons`، `open_modules`، `view_permissions`) مركزي | `config/notifications.php` | تعديل ملف مشترك + قد تلزم لافتة PNG (`notifications:banners`) |
| G5 | **الترجمة**: لا تحميل لـ lang من الوحدة، الملفات في `lang/` الجذر | `lang/{en,ar}/` | ملفات خارج الوحدة (بادئة `foo_`) |
| G6 | **Factories** في `database/factories/`، و `configureFactories()` يقرأ قائمة namespaces ثابتة (`App`, `Core`, `Tracking`, `Hr`) | `AppServiceProvider::configureFactories` | وحدة جديدة تحتاج إضافة namespace هنا وإلا يرمي `LogicException` |
| G7 | **`MediaCollection` و `FileType`** enums في `app/Enums/` | `app/Enums/MediaCollection.php` | case جديد لكل نوع ملف جديد |
| G8 | **مجموعات القائمة ثابتة** (`Destinations::Groups`) وتسميتها في `lang/*/navigation.php` | `app/Support/Desk/Destinations.php` | وحدة بقسم جديد في القائمة تعدّل `app/` |
| G9 | **`DestinationSource` بلا حقل `module`**: صفحات الوحدات لا تُظهر عدّاد إشعارات غير المقروء في القائمة (صفحات Tracking تفعل لأنها مكتوبة في `Destinations::table()`) | `Destinations::modulePages()` (سطر 368) | لا شارة عدّاد لوحدة جديدة حتى يُضاف الحقل |
| G10 | **Tracking نفسه** لا يستعمل `DestinationSource`: صفحاته داخل `app/Support/Desk/Destinations.php` | نفس الملف | تعديلاته لا تخص وحدة جديدة، لكن الدليل يجب ألا يُنسَخ منه |
| G11 | **القواعد المعمارية لا تغطي الوحدة الجديدة** تلقائياً: كل زوج (وحدة، ممنوع) إدخال يدوي في `module-boundaries-allowlist.php`. و`App must not use Modules\Foo` غير موجودة | `tests/Arch/module-boundaries-allowlist.php` | وحدتك لا تُحمى حتى تضيف القواعد |
| G12 | **`access_grants.scope_types`** قائمة ثابتة في الـ config، والقارئ الوحيد `ProjectPolicy` | `config/access-control.php` | نطاق جديد = تعديل الـ config وكود `AccessScope` |
| G13 | **المقاييس والمهام المجدولة** مثبّتة باختبار (`ScheduleTest`)، والمقاييس تطلب مفتاحاً فريداً عبر الوحدات | `tests/Feature/ScheduleTest.php` | تعديل اختبار موجود |
| G14 | **مؤشرات الأداء كنقطة تسجيل**: مذكورة في decisions.md، غير منفَّذة في الـ registry | `ModuleRegistry` | لا شيء تفعله اليوم؛ لا تبحث عنها |
| G15 | **Hr و Tracking يتشابكان**: Tracking يستورد Hr (52 صنفاً مسموحاً). وحدة جديدة تحتاج `Employee` تصطدم بالقاعدة: اطلبه من `app/Support/EmployeeLookup` (عقد في `app/`، يسجّله Hr كـ `HrEmployeeLookup`) | `app/Support/EmployeeLookup.php`، `HrServiceProvider::registerCoreBridge` | لا يوجد عقد "الموظفون" عام بعد (جدول `people` مؤجَّل، D1). تحقق مما يوفره `EmployeeLookup` قبل أن تقرر |

ما سبق يعني أن الوحدة الجديدة **لا تعمل من مجلدها وحده**: تحتاج على الأقل تعديل G2 و G3 و G5 (وغالباً G4 و G6 و G7 و G11). الهدف (D10) أن يكون الباقي كله في الـ provider.

## 11. قائمة تحقق

الهيكل
- [ ] `modules/Foo/FooServiceProvider.php` (namespace `Modules\Foo`) وأُضيف إلى `bootstrap/providers.php`.
- [ ] `loadMigrationsFrom`، `loadViewsFrom(... 'foo')`، `Livewire::addNamespace('foo', ...)`.
- [ ] routes في `modules/Foo/routes/*.php` تحت بادئة `foo` واسم `foo.`، داخل `web + auth + EnsureTwoFactorIsEnabled`، لا تحميل عند `routesAreCached()`.
- [ ] كل route له `->can(...)` أو Policy.

الصلاحيات
- [ ] أُضيف `foo` إلى `$groups` في `config/access-control.php`.
- [ ] صلاحية `foo.view` الحساسة وُضعت في `$restrictedViews`/قائمة خاصة (وإلا رآها كل الأدوار).
- [ ] `php artisan permissions:sync --dry-run` ثم بدونها.
- [ ] السياسات تسأل `can('foo.*')`، لا `hasRole`.

الـ registry (سجّل ما تحتاجه فقط)
- [ ] `addDestinations` (الصفحات، مجموعة من `Destinations::Groups`، مفاتيح ترجمة موجودة في `lang/`).
- [ ] `addDeskPanels` / `addDeskAgenda` / `addWorkItems` إن كان عندك عمل ينتظر شخصاً.
- [ ] `addSubjectLinks` (لكل morph type لوحدتك).
- [ ] `addSearch` / `addTimeline` / `addEmployeeTabs` / `addCalendarLayer` / `addMetrics` عند الحاجة، وكل مصدر يطبّق `allows` بنفسه.
- [ ] `addSchedule` + تعديل `tests/Feature/ScheduleTest.php`.

النشاط والإشعارات
- [ ] موديلات الوحدة تستعمل `RecordsActivity` وتطبّق `TracksActivity` (`activityModule()` = `'foo'`).
- [ ] كل موديل اتخذ morph key في `AppServiceProvider::configureMorphMap()`.
- [ ] `employee_id` يُمرَّر في `activityProperties()` للسجلات الخاصة بموظف.
- [ ] `FooRecipients extends ModuleRecipients` + `config/notifications.php` (`modules`، `module_icons`، وعند الحاجة `open_modules`/`view_permissions`).

الملفات
- [ ] الرفع عبر `StoredFiles::save()` إلى `media` (لا جدول ملفات جديد، لا `hr_media`)، `MediaCollection` له case، والتنزيل خلف Policy.

الاعتماد والاختبار
- [ ] لا استيراد من `Modules\Hr` أو `Modules\Tracking` داخل `Modules\Foo`.
- [ ] أُضيفت قواعد `Modules\Foo` (و `Modules\Core` لا تستعمل Foo) في `tests/Arch/module-boundaries-allowlist.php` بـ `max = 0`.
- [ ] factory في `database/factories/` وnamespace الوحدة في `configureFactories()`.
- [ ] ترجمة en و ar في `lang/` (بادئة `foo_`).
- [ ] اختبارات في `tests/Feature/Foo/` تغطي: الرؤية، رفض غير المصرَّح، والفشل المهم.
- [ ] `php artisan test --compact tests/Feature/Foo` و `--testsuite=Arch` و `vendor/bin/pint --dirty --format agent` نظيفة.
