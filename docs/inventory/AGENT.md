# Inventory · session addendum to `docs/lifecycle/AGENT.md`

`/inventory INN [slice]` loads `docs/lifecycle/AGENT.md` first, then this file, then `docs/inventory/contracts.md`,
`docs/lifecycle/quality-bar.md`, the prompt. Everything in `AGENT.md` still applies; this file only adds or replaces.

## 1. Sources of truth for this module
1. `docs/inventory/contracts.md` (frozen; replaces `docs/lifecycle/contracts.md` for this module).
2. `docs/inventory/decisions.md` (D1…D9).
3. The task prompt `docs/inventory/prompts/INN.md`.
4. Upstream handoffs `docs/inventory/handoffs/INN.md` and the code on `main`.
5. `CLAUDE.md`, `.ai/rules/index.md` and every rule file whose glob matches a path you touch. Read them before plan mode.
6. The reference module is `modules/Hr`. When unsure how something is wired, copy how Hr does it.

## 2. Where code lives (replaces `app/Actions/<Module>` in the quality bar)
| Thing | Path |
|---|---|
| Provider | `modules/Inventory/InventoryServiceProvider.php` |
| Models, Enums, Policies, Observers | `modules/Inventory/{Models,Enums,Policies,Observers}` |
| Actions | `modules/Inventory/Actions/<Area>/*` — `final` class, one public `handle()`, `Gate::authorize` on its first line |
| Support (generators, queries, importers) | `modules/Inventory/Support/**` |
| Commands | `modules/Inventory/Console/Commands` |
| Migrations | `modules/Inventory/database/migrations` (`php artisan make:migration … --path=modules/Inventory/database/migrations --no-interaction`) |
| Factories / seeders | `database/factories/Inventory`, `database/seeders/Inventory` (follow how Hr models resolve their factories) |
| Pages (Livewire single-file) | `modules/Inventory/resources/views/pages/<area>/⚡<name>.blade.php`, namespace `inventory::` |
| Components | `modules/Inventory/resources/views/components/**` (`<x-inventory::…>`) |
| Routes | `modules/Inventory/routes/<area>.php`, prefix `/inventory`, names `inventory.<area>.<page>` |
| Lang | `lang/{en,ar}/inventory.php`; enum labels in `lang/{en,ar}/enums.php` |
| Tests | `tests/Feature/Inventory/**` (Pest) |

HR and Core code is read-only for this module, except where a prompt names the exact file (I09, I11).

## 3. Branches, commits, ClickUp
- Branch from `main`: `inventory/INN-<slug>`. When started by the orchestrator you are already in an isolated worktree: stay in it.
- Never commit, push or change ClickUp. The developer does all three. Put the ClickUp task URL in the handoff header.

## 4. Running tests on this machine
- Never run the whole suite (it exhausts memory on Windows). Run only the files the prompt names:
  `php -d memory_limit=1536M vendor/bin/pest --compact <files>`.
- Tests use MySQL `hc_hub_test`, not sqlite. When another agent may be running, use the database name the orchestrator
  gave you (`DB_DATABASE=hc_hub_test_<n>`); never share a test database between two running sessions.
- After PHP changes: `vendor/bin/pint --dirty --format agent`.

## 5. Non-negotiables added for this module
- **No secrets.** No device, e-mail or DVR password is stored, imported, logged, seeded or written into a test, a doc or a
  ClickUp task (D3). The one encrypted value is `inventory_licenses.license_key` (I16).
- **The register and the history never disagree.** `inventory_assets.status`, holder columns, `city_id` and `center_id`
  are written only by the Actions in `contracts.md §5`. Pages, importers and commands call those Actions; none of them
  updates these columns directly.
- **Scope is server-side.** Every list and every single-record load goes through `Asset::visibleTo($user)` (or the
  matching scope for SIM lines and licences). A user outside the scope gets 403/404 — a test proves it for every new page (BOLA).
- **No new dependency.** No spreadsheet, PDF, barcode or QR package. CSV in, CSV out, print through Blade views.
- **People are never copied.** Store `employee_id`; show names through the Employee relation. The only free-text holder
  is `holder_label` on imported rows that could not be matched.
- **Every page is bilingual** (en + ar, RTL) and built from existing `x-ui.*` / Flux components. Check for an existing
  component before writing one.

## 6. Finish
`AGENT.md §9`, with the handoff written to `docs/inventory/handoffs/INN.md` from `docs/inventory/handoffs/_TEMPLATE.md`.
The final chat report is at most 10 lines: files changed, test summary line, deviations, open risks.
