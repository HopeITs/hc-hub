# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) and other coding agents when working in this repository.

## What HC-Hub is

HC-Hub is the single Laravel app for "Christian Hope Center" (multi-city Syrian NGO). It merges three apps into **one codebase and one database (`hc_hub`, no table prefix)**:

| Module | Origin | Status |
|---|---|---|
| **HR** (core: employees, contracts, leaves, positions, centers, cities, programs, news, archive) | `hc-hr` — this repo started as its clone | live here |
| **Tracking** (projects, MEAL, annual plans, proposals, donors, tasks, reports) | `hc-tracking` | being ported (phase 2, lane A) |
| **Media requests** | `media-request` | being ported (phase 2, lane B) |

HR owns the master data (users, employees, cities, centers, programs, departments, positions, contracts); the other modules use the HR models directly, never copies. The full merge plan is `C:\laragon\www\HC-Hub-Plan.md` (section 2 holds the binding decisions). The original apps in `C:\laragon\www\hc-hr`, `hc-tracking` and `media-request` are read-only references.

Stack: PHP 8.5, Laravel 13, Livewire 4 (+ Volt + Flux), Tailwind CSS 4, Vite 8, MySQL. Bilingual EN/AR with RTL.

## Role

Act as an expert senior TALL stack (Tailwind, Alpine.js, Laravel, Livewire) developer on this codebase: idiomatic Livewire/Volt/Flux patterns, clean Laravel architecture, and Tailwind-first styling consistent with the conventions documented below.

## Commands

```bash
composer dev          # run server + queue:listen + vite concurrently (primary dev loop)
npm run dev           # vite only
npm run build         # production asset build
composer test         # config:clear, then php artisan test --parallel
composer analyse      # Larastan (PHPStan) level 7 over app/
php artisan test --parallel          # same as composer test (Pest + paratest)
vendor/bin/pest --filter=SomeTest    # single test / file
vendor/bin/pest tests/Feature/Hub    # one directory
./vendor/bin/pint     # format / lint (Laravel Pint)
php artisan migrate:fresh --seed     # rebuild the local hc_hub database
php artisan storage:link             # REQUIRED — public/storage must be a symlink, not a real dir
```

Custom artisan commands (in `app/Console/Commands`):
- `php artisan leaves:backfill` — generate deserved leave balances for Excel-imported contracts
- `php artisan employees:import-images <path> [--force]` — import avatars into the Spatie `avatar` media collection
- contract / employee deactivation commands

### Tests and static analysis
- **Pest 4** runs everything. HR's existing tests are PHPUnit classes and run unchanged; **write new tests in Pest function style** (`test()` / `it()` + `expect()`), which is also what the tracking suite uses. `tests/Pest.php` binds `Tests\TestCase` (plus `RefreshDatabase` under `tests/Feature`) for function-style files.
- Tests run on SQLite `:memory:` (see `phpunit.xml`); `Tests\TestCase` refuses to run against anything else. Dev/prod use MySQL.
- Parallel runs (`--parallel`, paratest) are the default; tests must not share state through files, ports or a real database.
- **Larastan level 7** (`phpstan.neon`, paths `app/`). Existing HR errors are grandfathered in `phpstan-baseline.neon`; **new code must pass level 7 without new baseline entries**. Fix the code instead of regenerating the baseline. Fixed errors drop out automatically (`reportUnmatchedIgnoredErrors`).

### Environment gotchas (Laragon on Windows)
- The app requires **PHP 8.5** (`C:/laragon/bin/php/php-8.5`), for both the web server (Laragon → PHP menu) and the CLI. Older PHP installs are still in `C:/laragon/bin/php`, and each version has its own `php.ini`, so a change that affects uploads/config must go into 8.5's. `upload_tmp_dir` is `C:/laragon/tmp`.
- Livewire 4 tests: a `ModelNotFoundException` inside a component call becomes a 404 response instead of being thrown. Assert it with `->assertNotFound()`, not `expectException`.
- Vite 8 bundles with Rolldown: `manualChunks` must be a function, not an object.

## Module layout conventions

HR stays exactly where it is (it is the core). Every other module lives side by side in its own namespace:

| | HR (core) | Tracking | Media requests |
|---|---|---|---|
| Models | `App\Models\*` | `App\Models\Tracking\*` | `App\Models\Media\*` |
| Livewire | `App\Livewire\<Domain>\*` | `App\Livewire\Tracking\*` | `App\Livewire\Media\*` |
| Views | `resources/views/livewire/...`, `pages/...` | `resources/views/pages/tracking/*` | `resources/views/pages/media/*` |
| Routes (URL / name) | existing HR routes | `/tracking/*` / `tracking.*` | `/media-requests/*` / `media-requests.*` |
| Permissions | `hr.*` | `tracking.*` | `media.*` |
| Translations | `resources/lang/{en,ar}/*.php` | `resources/lang/{en,ar}/tracking*.php` | `resources/lang/{en,ar}/media*.php` |

- **Never copy HR master data into a module.** Tracking and media code use `App\Models\Employee`, `City`, `Center`, `Program`, `Department`, `Position`, `Contract`, `JobLevel`, `User` directly (tracking's old `Hr*` read-only models are not ported).
- Every module route goes **inside the localized route group** in `routes/web.php` (one `/en` `/ar` prefix for the whole hub), in its own route file `require`d from there.
- A module table name must not collide with an HR table (see the collision map in the plan; e.g. tracking's file store becomes `tracking_files`).
- **Permission names are prefixed by module** (`hr.*`, `tracking.*`, `media.*`). The rename of the existing HR permissions to `hr.*` is task 0.3 — until it lands, HR permissions keep their current unprefixed names (`employees.view`, `leaves.view-all`, …). New module permissions are prefixed from day one.

## Polymorphic types: the global morph map (mandatory)

`AppServiceProvider` calls `Relation::enforceMorphMap(App\Support\MorphMap::MAP)`. Every `*_type` column (`model_has_roles`, `model_has_permissions`, `media`, `activity_log.subject_type`/`causer_type`, `notifications.notifiable_type`, `news.newsable_type`, and any new one) stores a **short snake_case alias** (`user`, `employee`, `leave_request`), never a class name.
- **Every new model must get an alias in `App\Support\MorphMap`** — the rule is "every Eloquent model", not only the ones polymorphic today. An unmapped model throws `ClassMorphViolationException` the moment it is notified, logged, given media, assigned a role or used as a morph target. `tests/Feature/Hub/MorphMapTest.php` fails if an `app/Models` class has no alias (extend it to cover module model folders as they are added).
- Aliases are the snake_case class basename, the same style tracking already stores (`annual_plan`, `project`, `program`, `department`, `user`, `role`). On a collision with an existing alias, prefix with the module (`media_folder`).
- **Aliases are stored data.** Never rename or remove one without a data migration (see `2026_10_29_000001_use_morph_aliases_in_polymorphic_columns`, which converted HR's old `App\Models\X` strings).
- **Never compare or query a `*_type` column against `X::class` or an `'App\\Models\\...'` string.** Use `(new X)->getMorphClass()` or `Relation::getMorphAlias(X::class)`, and `Relation::getMorphedModel($alias)` to go back. To display a type, map the alias back to a class first (see `ActivityLogTable`).

## Architecture

### Laravel 13 layout
No `app/Http/Kernel.php`. Middleware aliases, routing, and exceptions are configured in `bootstrap/app.php`. Service wiring (morph map, observer registration, Blade directives, query macros) lives in `app/Providers/AppServiceProvider.php`.

### Localization & translatable columns
- URLs are locale-prefixed (`/en/...`, `/ar/...`) via `mcamara/laravel-localization`. All web routes are wrapped in the locale group in `routes/web.php`; `AppServiceProvider` re-registers the Livewire update route under the locale prefix.
- **Translatable model fields use the `{attribute}_{locale}` column convention** (e.g. `first_name_en` / `first_name_ar`, `title_en` / `title_ar`). The `HasTranslation` trait + accessors expose a single virtual attribute that resolves to the current locale. In queries/columns, build the physical column name with the `transColumn('first_name')` helper.
- Every user-facing string goes through translations in **both** `resources/lang/en` and `resources/lang/ar`; no hard-coded UI text.
- Helpers are autoloaded from `app/Support/helpers.php`: `transColumn`, `ar_digits`/`en_digits`, `formatDuration`, `esc`.

### Roles, permissions, cities & scoping (security-critical)
- Spatie Permission, one catalogue in `config/access-control.php` for the whole hub. Roles are city-scoped for HR: `HR-SYR`, `HR-ALP`, `HR-HMS`, etc. The `City` table's `slug` holds the scope code (SYR/ALP/HMS/DMS/LAT/TAR); `code` holds the numeric id (000/001/...).
- **The active role is the only role that counts.** A user may hold several roles and switches between them; the active one is the `model_has_roles` pivot row with `is_active = true` (one mechanism for the whole hub). `User::myRole()` returns it (falling back to the first). **Check a permission name with `User::hasActivePermission()`**, never Spatie's `hasPermissionTo()` / `can('permission.name')` on the user, which combine every assigned role. (`can('ability', $model)` for a policy is fine; policies themselves call `hasActivePermission()`.) `User::activeRoleIs()` and the `@activerole` / `@notactiverole` Blade directives normalize any `HR-*` to `HR`.
- `App\Http\Middleware\RoleMiddleware` (alias `role`) expands `role:HR` to **all** `HR-%` roles. Use `role:HR|super-admin|...` in route definitions.
- **`Position::scopeInCity($cityId)` is the single source of truth for city scoping** (`city_id` OR `center.city_id`). Any contract create/edit dropdown filter AND its matching validation must both go through this scope — divergence here was the cause of the "invalid position in Syria" bug.

### Contracts & position capacity
`ContractObserver` (registered in `AppServiceProvider`) keeps each `Position.exist_emp` counter in sync using `lockForUpdate`, and enforces capacity via `Position::canAssign()`. It **skips the capacity check when `runningInConsole()`** so bulk Excel/CLI imports don't fail.

### Pay figures (security-critical)
`contracts.salary` and `contract_pay_tiers.amount` are **encrypted at rest under their own key**, not under `APP_KEY` — a database copy usually travels with a developer `.env`, and `APP_KEY` with it. The key is `SALARY_ENCRYPTION_KEY` (`php artisan salary:key:generate`), set on production only; `config/salary.php` falls back to `APP_KEY` so local databases and the suite keep working.
- `App\Services\SalaryCipher` returns **null instead of throwing** for a value it cannot decrypt — a copy without the key must stay usable. `App\Casts\EncryptedSalary` then **refuses to overwrite a figure it could not read**, so working on such a copy cannot destroy the originals. Its `compare()` decrypts before diffing; without it every save would look like a pay change (fresh IV each time).
- Decryption failure is silent by design, so a misconfigured host looks exactly like a working one. `php artisan salary:key:check` is what tells them apart — run it after any deploy or key change.
- The migration widens the columns to `text` **except on SQLite**, where `change()` rebuilds the table and dropping `contracts` fires `contract_pay_tiers`' `ON DELETE CASCADE`, wiping every flexible contract's pay schedule. SQLite type affinity keeps the envelope verbatim anyway.
- **`User::canViewSalary()` is the single source of truth for who may see a figure**, and `super-admin` is refused by name in `config('salary.blind_roles')` — it holds every permission automatically, so a `salaries.view` permission would be granted to the one role that must not have it. Callers add their own city scope, the same way they already do for every other HR-only view.
- Roles that may not see figures get them **withheld on load and preserved on save** (`ContractForm::preserveHiddenPay`, `BadgeForm::save`) — never disabled inputs, because the values are never sent to that browser at all. The PDF export is refused outright: the document *is* the pay clause.

### Media / avatars
`Employee implements HasMedia` (Spatie MediaLibrary, table `media`, shared by HR and media requests). Avatars live in the singleFile `avatar` collection with a fallback to `public/images/default-avatar.png`. Read with `getFirstMediaUrl('avatar')`.
- **Never re-encode stored PNGs in place** (e.g. GD `imagesavealpha` on images without an alpha channel) — this turned photo backgrounds black. If thumbnails are ever needed, generate conversions from the source without touching the stored original.
- A custom media path generator (media requests) must be scoped to its own model, never set globally.

### Contract PDF export
`ContractPdfRenderer` (in `app/Services/`) renders `resources/views/pdf/contract.blade.php` through Browsershot; the controller only authorizes and sets headers. Host-specific settings: `BROWSERSHOT_CHROME_PATH`, `BROWSERSHOT_NODE_BINARY`, `BROWSERSHOT_TIMEOUT`, and the optional `BROWSERSHOT_WS_ENDPOINT`.
- **Production renders through browserless.io** (`BROWSERSHOT_WS_ENDPOINT`, decided 2026-10-20): the shared host has no usable Chromium, so contracts — pay clause included — are rendered by that service. The token lives only in the server's `.env`; with the endpoint set, `chrome_path` is ignored.
- **On a Linux server point `BROWSERSHOT_CHROME_PATH` at the distribution's own Chromium** (`/usr/bin/chromium`). A Chrome unpacked by hand into a home directory brings none of the system integration it expects and dies during startup with nothing on stderr but D-Bus and PCI complaints.
- **Nothing large may travel on node's stdout.** Browsershot parses that stdout as JSON and treats a parse failure as *success* — `$output['result'] ?? ''`, base64-decoded, is an empty string returned with exit code 0. `savePdf()` (not `pdf()`) plus `disableCaptureURLS()` keeps stdout small; with `pdf()` and URL capture it was ~635 KB per contract and whatever truncated it on the server surfaced as a 0-byte "PDF".
- `php artisan contracts:export-doctor [contract]` runs the same pipeline on a host and reports Chrome, node, puppeteer resolution, directory permissions and the raw stdout/stderr/exit code, printing Chrome's own output in full when a launch fails. Run it on the server before changing anything.

### Livewire conventions
- Components in `app/Livewire/<Domain>/` (module components under `app/Livewire/Tracking/`, `app/Livewire/Media/`); reusable form objects in `app/Livewire/Forms/` (Livewire `Form` classes). Extracted domain logic lives in `app/Services/` (e.g. `LeaveBalanceService`).
- Data tables extend `ramonrietdijk/livewire-tables` `LivewireTable` — define `query()` and `columns()`. Eager-load relations in `query()` to avoid N+1 (see `EmployeeTable`).
- `AppServiceProvider` registers query Builder macros (`whereLikeAny`, `search`) and the `@glyph` Blade directive (Arabic glyph shaping).

### Brand / theming
One brand for every module (Hope Center red, Somar Sans). Brand primary red is `#c2151c`, defined as `--color-primary` plus a full ramp in `resources/css/app.css` (Tailwind 4 `@theme`). Fonts: SomarSans (with Lato fallback), preloaded in `resources/views/partials/head.blade.php`. Module UIs reuse these tokens and the existing `x-*` components rather than bringing their own palette.
- **Web fonts live in `resources/fonts/` as WOFF2** and are referenced from `app.css` by relative path, so Vite fingerprints them (cache-busting) and the preload uses `Vite::asset()` to get the same URL. Never point `@font-face` at `/fonts/...` again — that is what produced the "didn't resolve at build time" warnings.
- `public/fonts/` holds only the `.ttf` files the contract PDF inlines through `public_path()` (`_styles.blade.php`). Those two sets are separate on purpose. Required form fields are marked with a red star via the `required` prop on `<x-forms.input-label>`.

### Routes
`routes/web.php` is the locale-wrapped entry point and `require`s the domain route files: `basic-project.php`, `projects-management.php`, `employees-management.php`, and `auth.php`. Module route files (`tracking.php`, `media-requests.php`) are added the same way, inside the locale group.
