# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) and other coding agents when working in this repository.

## What HC-Hub is

HC-Hub is the single Laravel app for "Christian Hope Center" (multi-city Syrian NGO). It merges three apps into **one codebase and one database (`hc_hub`, no table prefix)**:

| Module | Origin | Status |
|---|---|---|
| **HR** (core: employees, contracts, leaves, positions, centers, cities, programs, news, archive) | `hc-hr` — this repo started as its clone | live here, in `modules/Hr` |
| **Tracking** (projects, MEAL, annual plans, proposals, donors, tasks, reports) | `hc-tracking` | being ported (phase 2, lane A) → `modules/Tracking` |
| **Media requests** | `media-request` | being ported (phase 2, lane B) → `modules/Media` |

HR owns the master data (employees, positions, contracts, job levels; it also administers the shared organisation models cities, centers, programs, departments, which live in `app/Models` because every module uses them); the other modules use those models directly, never copies. The full merge plan is `C:\laragon\www\HC-Hub-Plan.md` (section 2 holds the binding decisions). The original apps in `C:\laragon\www\hc-hr`, `hc-tracking` and `media-request` are read-only references.

Stack: PHP 8.5, Laravel 13, Livewire 4 (+ Volt + Flux), Tailwind CSS 4, Vite 8, MySQL. Bilingual EN/AR with RTL.

## Role

Act as an expert senior TALL stack (Tailwind, Alpine.js, Laravel, Livewire) developer on this codebase: idiomatic Livewire/Volt/Flux patterns, clean Laravel architecture, and Tailwind-first styling consistent with the conventions documented below.

## Commands

```bash
composer dev          # run server + queue:listen + vite concurrently (primary dev loop)
npm run dev           # vite only
npm run build         # production asset build
composer test         # config:clear, then php artisan test --parallel
composer analyse      # Larastan (PHPStan) level 7 over app/ and modules/*/app
php artisan test --parallel          # same as composer test (Pest + paratest)
vendor/bin/pest --filter=SomeTest    # single test / file
vendor/bin/pest tests/Feature/Hub    # one directory
./vendor/bin/pint     # format / lint (Laravel Pint)
php artisan migrate:fresh --seed     # rebuild the local hc_hub database
php artisan storage:link             # REQUIRED — public/storage must be a symlink, not a real dir
```

Custom artisan commands (HR's in `modules/Hr/app/Console/Commands`, platform ones in `app/Console/Commands`):
- `php artisan leaves:backfill` — generate deserved leave balances for Excel-imported contracts
- `php artisan employees:import-images <path> [--force]` — import avatars into the Spatie `avatar` media collection
- contract / employee deactivation commands

### Tests and static analysis
- **Pest 4** runs everything. HR's existing tests are PHPUnit classes and run unchanged; **write new tests in Pest function style** (`test()` / `it()` + `expect()`), which is also what the tracking suite uses. `tests/Pest.php` binds `Tests\TestCase` (plus `RefreshDatabase` under `tests/Feature`) for function-style files.
- Tests run on SQLite `:memory:` (see `phpunit.xml`); `Tests\TestCase` refuses to run against anything else. Dev/prod use MySQL.
- Parallel runs (`--parallel`, paratest) are the default; tests must not share state through files, ports or a real database.
- Tests live in `tests/` (platform and hub-wide) and in each module's `modules/<Name>/tests/{Feature,Unit}` (namespace `Modules\<Name>\Tests\…`); `phpunit.xml` and `tests/Pest.php` pick both up. Shared fixtures stay in `tests/Concerns`. Tests that scan source files ("every view…", "every model…") must go through `Tests\Support\HubPaths` so they cover the modules too.
- **Larastan level 7** (`phpstan.neon`, paths `app/` plus each `modules/<Name>/app/`, and every module's migrations folder in `databaseMigrationsPath` so Larastan knows its columns). Existing HR errors are grandfathered in `phpstan-baseline.neon`; **new code must pass level 7 without new baseline entries**. Fix the code instead of regenerating the baseline. Fixed errors drop out automatically (`reportUnmatchedIgnoredErrors`).

### Environment gotchas (Laragon on Windows)
- The app requires **PHP 8.5** (`C:/laragon/bin/php/php-8.5`), for both the web server (Laragon → PHP menu) and the CLI. Older PHP installs are still in `C:/laragon/bin/php`, and each version has its own `php.ini`, so a change that affects uploads/config must go into 8.5's. `upload_tmp_dir` is `C:/laragon/tmp`.
- Livewire 4 tests: a `ModelNotFoundException` inside a component call becomes a 404 response instead of being thrown. Assert it with `->assertNotFound()`, not `expectException`.
- Vite 8 bundles with Rolldown: `manualChunks` must be a function, not an object.

## Module layout conventions

**Modular layout (decided 2026-10-30).** Every module, HR included, lives in `modules/<Name>/` with namespace `Modules\<Name>\`; `app/` keeps only hub-wide platform code.

| | Platform (`app/`) | HR (`modules/Hr`) | Tracking (`modules/Tracking`, to port) | Media requests (`modules/Media`, to port) |
|---|---|---|---|---|
| Namespace | `App\` | `Modules\Hr\` | `Modules\Tracking\` | `Modules\Media\` |
| Owns | users/auth, roles/permissions + access management, activity log, notifications (centre, counter, delivery ledger), layout shell (sidebar, header, bottom nav, global search, breadcrumbs), dashboard shell, settings/profile password, feedback, task telemetry, morph map, shared organisation models (`City`, `Center`, `Program`, `Department`), shared traits/concerns/middleware | employees, contracts, positions, job levels, leaves, holidays, badges, checklists, archive folders, news, inbox, team calendar, statistics, digital ID, HR reference data screens (incl. the city/center/program/department admin UIs and `CenterPolicy`), HR dashboard cards, pay-figure encryption, contract PDF | projects, MEAL, annual plans, proposals, donors, tasks, reports | media requests |
| Models | `app/Models` | `modules/Hr/app/Models` | `modules/Tracking/app/Models` | `modules/Media/app/Models` |
| Livewire | `app/Livewire/<Domain>` | `modules/Hr/app/Livewire/<Domain>` | same pattern | same pattern |
| Views | `resources/views` | `modules/Hr/resources/views` | `modules/Tracking/resources/views` | `modules/Media/resources/views` |
| Translations | `resources/lang/{en,ar}` | `modules/Hr/lang/{en,ar}` | `modules/Tracking/lang/{en,ar}` | `modules/Media/lang/{en,ar}` |
| Routes (URL / name) | `routes/web.php`, `auth.php`, `console.php` | `modules/Hr/routes/web.php` (+ `basic-project.php`, `projects-management.php`, `employees-management.php`) and `public.php`; existing HR URLs/names | `/tracking/*` / `tracking.*` | `/media-requests/*` / `media-requests.*` |
| Migrations / factories / seeders | `database/…` (incl. the frozen mixed base schema `basic_tables` and hub data migrations) | `modules/Hr/database/{migrations,factories,seeders}` (`Modules\Hr\Database\…`) | same pattern | same pattern |
| Tests | `tests/` | `modules/Hr/tests/{Feature,Unit}` (`Modules\Hr\Tests\…`) | same pattern | same pattern |
| Permissions | `hub.*` | `hr.*` | `tracking.*` | `media.*` |

A platform file may use a module's classes (the dashboard shell, global search and sidebar read HR models); a module never depends on another module except HR's master data.

**How a module registers** — everything goes through `Modules\<Name>\Providers\<Name>ServiceProvider`, listed in `bootstrap/providers.php` after `AppServiceProvider` (see `HrServiceProvider` for the template):
- **Autoload**: `composer.json` maps `Modules\<Name>\` → `modules/<Name>/app/`, `Modules\<Name>\Database\Factories\` / `…\Seeders\` → its `database/` folders, and (autoload-dev) `Modules\<Name>\Tests\` → its `tests/`. Run `composer dump-autoload` after adding one.
- **Views**: `View::addLocation()` on the module's `resources/views`, so its views keep plain names (`view('livewire.contract.index')`, `<x-…>` stays platform), plus `loadViewsFrom(…, '<name>')` for an explicit `hr::…` name. The platform folder is searched first, so a module view must not reuse a platform view name.
- **Translations**: the module's `lang/` is added to the translation loader's paths (`callAfterResolving('translation.loader', …addPath(…))`), so groups keep plain keys (`__('leaves.status')`). Group names (file names) must be unique across the hub — prefix new module groups (`tracking_*.php`, `media_*.php`).
- **Livewire**: `Livewire::addLocation(classNamespace: 'Modules\\<Name>\\Livewire')`, so components keep their plain names (`contract.contracts-table`, the keys of `config('access-control.livewire')`). **Every module component must have a `render()`** naming its view: Livewire's implicit view lookup only searches `resources/views/livewire`.
- **Routes**: the module's `routes/web.php` is `require`d from `routes/web.php` **inside the localized route group and the `auth` group** (one `/en` `/ar` prefix for the whole hub); unauthenticated, unlocalized routes go in its `routes/public.php`, required at the top of `routes/web.php`.
- **Migrations**: `loadMigrationsFrom()` on its `database/migrations`; Laravel sorts all folders together by file name, so timestamps order across modules. Add the folder to `databaseMigrationsPath` in `phpstan.neon` too.
- **Factories**: models declare `#[UseFactory(XFactory::class)]` (Laravel's name guess only covers `App\Models`). Seeders are called from `Database\Seeders\DatabaseSeeder`.
- **Console commands**: the provider registers its `app/Console/Commands` classes (`$this->commands(...)`); Laravel auto-discovers only `app/Console/Commands`. Scheduling stays in `routes/console.php`.
- **Policies, gates, observers, singletons**: registered explicitly in the provider's `boot()`/`register()`, not in `AppServiceProvider`.
- **Tests / static analysis**: `phpunit.xml`, `tests/Pest.php` and `Tests\Support\HubPaths` already glob `modules/*/…`; add the module's `app/` to `phpstan.neon` `paths`. `tests/Feature/Hub/ModuleLayoutTest.php` fails on an unregistered provider, a view or lang group shadowed by the platform, or a module component without `render()`.

- **Never copy HR master data into a module.** Tracking and media code use `Modules\Hr\Models\Employee`, `Position`, `Contract`, `JobLevel` and `App\Models\City`, `Center`, `Program`, `Department`, `User` directly (tracking's old `Hr*` read-only models are not ported).
- A module table name must not collide with an HR table (see the collision map in the plan; e.g. tracking's file store becomes `tracking_files`).
- **Permission names are prefixed by module**: `{module}.{group}.{action}` — `hr.*`, `tracking.*`, `media.*`, plus `hub.*` for hub-wide platform administration. The rule: **`hub.` only for the platform groups `access-management` (roles/permissions/user access) and `activity-log`; every other existing group is HR's and uses `hr.`** (`hr.employees.view`, `hr.contracts.export`, `hr.leaves.decide`, `hr.statistics.view`, `hr.news.create`, `hr.project-basics.update`, …). Groups in `config/access-control.php` are keyed by their prefixed name (`'hr.contracts' => [...]`); the Access Management label drops the prefix (`access.groups.contracts`). Regional `HR-*` roles get exactly the `hr.*` permissions of the catalogue (never `hub.*`, `tracking.*` or `media.*`). The rename of the old unprefixed rows is the data migration `2026_10_29_000002_prefix_permission_names_by_module` (frozen mapping, idempotent, reversible); `tests/Feature/Hub/PermissionNamesTest.php` fails on a permission name in code that is unprefixed or missing from the catalogue.

## Polymorphic types: the global morph map (mandatory)

`AppServiceProvider` calls `Relation::enforceMorphMap(App\Support\MorphMap::MAP)`. Every `*_type` column (`model_has_roles`, `model_has_permissions`, `media`, `activity_log.subject_type`/`causer_type`, `notifications.notifiable_type`, `news.newsable_type`, and any new one) stores a **short snake_case alias** (`user`, `employee`, `leave_request`), never a class name.
- **Every new model must get an alias in `App\Support\MorphMap`** — the rule is "every Eloquent model", not only the ones polymorphic today, **module models included** (the one map lives in `app/` and imports `Modules\<Name>\Models\…`; a module never calls `enforceMorphMap`/`morphMap` itself). An unmapped model throws `ClassMorphViolationException` the moment it is notified, logged, given media, assigned a role or used as a morph target. `tests/Feature/Hub/MorphMapTest.php` fails if a class in `app/Models` or any `modules/*/app/Models` has no alias, and pins the existing aliases.
- **Moving a model between namespaces never changes its alias** (HR's models moved from `App\Models` to `Modules\Hr\Models` with the same aliases). Only the class side of the map changes.
- Aliases are the snake_case class basename, the same style tracking already stores (`annual_plan`, `project`, `program`, `department`, `user`, `role`). On a collision with an existing alias, prefix with the module (`media_folder`).
- **Aliases are stored data.** Never rename or remove one without a data migration (see `2026_10_29_000001_use_morph_aliases_in_polymorphic_columns`, which converted HR's old `App\Models\X` strings; its class strings are the stored legacy names and must stay `App\Models\X` even though the classes now live in `Modules\Hr\Models`). Likewise `notifications.type` rows written before the move keep `App\Notifications\X`; nothing reads that column back as a class.
- **Never compare or query a `*_type` column against `X::class` or an `'App\\Models\\...'` string.** Use `(new X)->getMorphClass()` or `Relation::getMorphAlias(X::class)`, and `Relation::getMorphedModel($alias)` to go back. To display a type, map the alias back to a class first (see `ActivityLogTable`).

## Architecture

### Laravel 13 layout
No `app/Http/Kernel.php`. Middleware aliases, routing, and exceptions are configured in `bootstrap/app.php`. Platform wiring (morph map, Blade directives, query macros, toasts) lives in `app/Providers/AppServiceProvider.php`; each module wires its own policies, observers, commands, views, lang and migrations in its `Modules\<Name>\Providers\<Name>ServiceProvider`.

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
`ContractObserver` (registered in `HrServiceProvider`) keeps each `Position.exist_emp` counter in sync using `lockForUpdate`, and enforces capacity via `Position::canAssign()`. It **skips the capacity check when `runningInConsole()`** so bulk Excel/CLI imports don't fail.

### Pay figures (security-critical)
`contracts.salary` and `contract_pay_tiers.amount` are **encrypted at rest under their own key**, not under `APP_KEY` — a database copy usually travels with a developer `.env`, and `APP_KEY` with it. The key is `SALARY_ENCRYPTION_KEY` (`php artisan salary:key:generate`), set on production only; `config/salary.php` falls back to `APP_KEY` so local databases and the suite keep working.
- `Modules\Hr\Services\SalaryCipher` returns **null instead of throwing** for a value it cannot decrypt — a copy without the key must stay usable. `Modules\Hr\Casts\EncryptedSalary` then **refuses to overwrite a figure it could not read**, so working on such a copy cannot destroy the originals. Its `compare()` decrypts before diffing; without it every save would look like a pay change (fresh IV each time).
- Decryption failure is silent by design, so a misconfigured host looks exactly like a working one. `php artisan salary:key:check` is what tells them apart — run it after any deploy or key change.
- The migration widens the columns to `text` **except on SQLite**, where `change()` rebuilds the table and dropping `contracts` fires `contract_pay_tiers`' `ON DELETE CASCADE`, wiping every flexible contract's pay schedule. SQLite type affinity keeps the envelope verbatim anyway.
- **`User::canViewSalary()` is the single source of truth for who may see a figure**, and `super-admin` is refused by name in `config('salary.blind_roles')` — it holds every permission automatically, so a `salaries.view` permission would be granted to the one role that must not have it. Callers add their own city scope, the same way they already do for every other HR-only view.
- Roles that may not see figures get them **withheld on load and preserved on save** (`ContractForm::preserveHiddenPay`, `BadgeForm::save`) — never disabled inputs, because the values are never sent to that browser at all. The PDF export is refused outright: the document *is* the pay clause.

### Media / avatars
`Employee implements HasMedia` (Spatie MediaLibrary, table `media`, shared by HR and media requests). Avatars live in the singleFile `avatar` collection with a fallback to `public/images/default-avatar.png`. Read with `getFirstMediaUrl('avatar')`.
- **Never re-encode stored PNGs in place** (e.g. GD `imagesavealpha` on images without an alpha channel) — this turned photo backgrounds black. If thumbnails are ever needed, generate conversions from the source without touching the stored original.
- A custom media path generator (media requests) must be scoped to its own model, never set globally.

### Contract PDF export
`ContractPdfRenderer` (in `modules/Hr/app/Services/`) renders `modules/Hr/resources/views/pdf/contract.blade.php` through Browsershot; the controller only authorizes and sets headers. Host-specific settings: `BROWSERSHOT_CHROME_PATH`, `BROWSERSHOT_NODE_BINARY`, `BROWSERSHOT_TIMEOUT`, and the optional `BROWSERSHOT_WS_ENDPOINT`.
- **Production renders through browserless.io** (`BROWSERSHOT_WS_ENDPOINT`, decided 2026-10-20): the shared host has no usable Chromium, so contracts — pay clause included — are rendered by that service. The token lives only in the server's `.env`; with the endpoint set, `chrome_path` is ignored.
- **On a Linux server point `BROWSERSHOT_CHROME_PATH` at the distribution's own Chromium** (`/usr/bin/chromium`). A Chrome unpacked by hand into a home directory brings none of the system integration it expects and dies during startup with nothing on stderr but D-Bus and PCI complaints.
- **Nothing large may travel on node's stdout.** Browsershot parses that stdout as JSON and treats a parse failure as *success* — `$output['result'] ?? ''`, base64-decoded, is an empty string returned with exit code 0. `savePdf()` (not `pdf()`) plus `disableCaptureURLS()` keeps stdout small; with `pdf()` and URL capture it was ~635 KB per contract and whatever truncated it on the server surfaced as a 0-byte "PDF".
- `php artisan contracts:export-doctor [contract]` runs the same pipeline on a host and reports Chrome, node, puppeteer resolution, directory permissions and the raw stdout/stderr/exit code, printing Chrome's own output in full when a launch fails. Run it on the server before changing anything.

### Livewire conventions
- Components in `app/Livewire/<Domain>/` (platform) or `modules/<Name>/app/Livewire/<Domain>/` (module, with a `render()`); reusable form objects in the matching `Livewire/Forms/` (Livewire `Form` classes). Extracted domain logic lives in `Services/` next to them (e.g. `Modules\Hr\Services\LeaveBalanceService`); shared concerns (`app/Livewire/Concerns`) and traits (`app/Traits`) stay in the platform.
- Data tables extend `ramonrietdijk/livewire-tables` `LivewireTable` — define `query()` and `columns()`. Eager-load relations in `query()` to avoid N+1 (see `EmployeeTable`).
- `AppServiceProvider` registers query Builder macros (`whereLikeAny`, `search`) and the `@glyph` Blade directive (Arabic glyph shaping).

### Brand / theming
One brand for every module. **`media-request` is the single source of truth for everything brand-related** (its `public/css/app.css`, components, fonts, logos); the hub copies it, never the other way round, and `DESIGN.md` lists every token with its media-request equivalent. Brand red is **`#BE1F24`, flat** (`--color-primary`, Tailwind 4 `@theme` in `resources/css/app.css`); hover dims the same red (`--color-primary-hover` = 90% via `color-mix`), never a second, darker red. Neutrals are the Hope Center greys (`#414042` text, `#6D6E71`, `#939598`, `#BCBEC0`, `#E6E7E8`, `#F3F3F4`, `#FFFFFF`). Status is icon + words on neutrals; no warning/info colours, no tints of the red (`bg-primary/10` reads as pink). **Dark mode** follows the system, overridden by `<html data-theme="light|dark">` (head script + `<x-theme-toggle>` in the header, stored as `hopehr-theme`); `app.css` flips the Tailwind colour variables to media-request's dark palette, so light utilities (`bg-white`, `text-neutral-800`, `border-gray-200`) work in both themes. New work uses the semantic utilities (`bg-card`, `text-ink`, `text-ink-2`, `border-line-soft`, `bg-hover` …); in CSS use the `theme-dark:` variant — never `dark:`, which is Flux's `.dark` class and is never set. `resources/css/tokens.css` also defines media-request's own variables (`--brand`, `--line-soft`, `--text-2` …) with the same dark palette, for the ported media-requests views. Font: Somar Sans (`'Somar Sans'`, alias `'SomarSans'`), preloaded in `resources/views/partials/head.blade.php`. Module UIs reuse these tokens and the existing `x-*` components rather than bringing their own palette.
- **Web fonts live in `resources/fonts/`** and are referenced from `app.css` by relative path, so Vite fingerprints them (cache-busting) and the preload uses `Vite::asset()` to get the same URL. Weights 400–800 are media-request's own hinted TTFs (same files); 100/300/900 are WOFF2 extras. Never point `@font-face` at `/fonts/...` again — that is what produced the "didn't resolve at build time" warnings.
- `public/fonts/` holds only the `.ttf` files the contract PDF inlines through `public_path()` (`_styles.blade.php`). Those two sets are separate on purpose. Required form fields are marked with a red star via the `required` prop on `<x-forms.input-label>`.

### Routes
`routes/web.php` is the locale-wrapped entry point: it holds the platform routes (dashboard, settings, notifications, activity log, access management, feedback), `require`s `auth.php`, and requires each module's `modules/<Name>/routes/web.php` inside the locale + `auth` group and its `routes/public.php` (HR: the public digital-ID card) outside it. HR's `web.php` in turn requires `basic-project.php`, `projects-management.php` and `employees-management.php`. Route names and URLs did not change when HR moved; compare `php artisan route:list` before and after any such move.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Follow existing application Enum naming conventions.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd at `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs. Never run commands to serve the site. It is always available.
- Use the `herd` CLI to manage services, PHP versions, and sites (e.g. `herd sites`, `herd services:start <service>`, `herd php:list`). Run `herd list` to discover all available commands.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== livewire/core rules ===

# Livewire

- Livewire allows you to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== volt/core rules ===

# Livewire Volt

- Single-file Livewire components: PHP logic and Blade templates in one file.
- Always check existing Volt components to determine functional vs class-based style.
- IMPORTANT: Always use `search-docs` tool for version-specific Volt documentation and updated code examples.
- IMPORTANT: Activate `volt-development` every time you're working with a Volt or single-file component-related task.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `php artisan test --compact`.

</laravel-boost-guidelines>
