# Security production runbook

Use this checklist for every staging and production release. Application
controls cannot compensate for an invalid TLS certificate, an exposed database,
or a misconfigured reverse proxy.

## Required production environment

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://hr.example.org

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

SECURITY_HEADERS_HSTS=true
CSP_REPORT_ONLY=true

LOG_LEVEL=warning
QUEUE_CONNECTION=database
```

- Generate `APP_KEY` once with `php artisan key:generate`; store it in the
  deployment secret manager and back it up. Never rotate it without a data and
  session migration plan.
- Use a dedicated least-privilege database account. Do not expose MySQL to the
  public internet.
- Configure SMTP with TLS. Employee onboarding sends a password-reset link;
  there is no shared initial password.
- Set a valid certificate whose SAN contains the exact production hostname.
  Redirect port 80 to HTTPS before PHP handles the request.
- If a reverse proxy terminates TLS, only trust that proxy's IP addresses and
  have the web server set `HTTPS=on`. Never trust forwarded headers from the
  public internet.

## Release procedure

```bash
composer install --no-dev --classmap-authoritative
npm ci
npm run build
php artisan migrate --force
php artisan security:migrate-employee-media
php artisan security:encrypt-pii
php artisan security:purge-activity-log
php artisan security:scan-uploads --days=30
php artisan optimize
php artisan queue:restart
```

`security:encrypt-pii` must run **after** the new code is live — see "Encrypted
PII" below for why the order matters and how to reverse it.

The media migration copies each sensitive employee document **and every
employee photograph** to the private disk, verifies file sizes, updates the
media record, and then removes the old public directory. Use `--keep-source`
only for a supervised dry migration, then remove those public copies after
verification.

`security:purge-activity-log` redacts sensitive values (salaries, national
numbers, addresses, phone numbers, note bodies) from activity-log rows written
before those fields were removed from the models' log options. Run it once with
`--dry-run` first. It is idempotent.

`security:scan-uploads` sweeps publicly served storage for executable
extensions, double extensions, and files whose leading bytes are PHP. Run it on
every environment that ever served the vulnerable program-logo upload, and
check the web-server access log for hits on anything it reports **before**
deleting the file.

## Uploads

All user-supplied images go through `App\Services\SecureImageUploadService`,
which decodes and re-encodes them server-side, discards the client's filename
and extension, and enforces byte, pixel, and aspect limits (`config/uploads.php`).
Nothing else may call `addMedia()` with a user upload.

Two web-server layers back this up. Under Apache both are active:
`public/.htaccess` blocks script extensions under `/storage` by URL, and
`storage/app/public/.htaccess` denies them by path. **Nginx does not read
`.htaccess`** — the equivalent must be configured explicitly:

```nginx
location ^~ /storage/ {
    location ~* \.(php[0-9]?|phtml|phps|phar|pht|cgi|pl|py|rb|sh)$ { deny all; }
    location ~* \.(php[0-9]?|phtml|phar|pht)\.       { deny all; }
    autoindex off;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Content-Security-Policy "default-src 'none'; sandbox" always;
}
```

## Employee photographs

Photos are stored on the private disk and served only through
`EmployeeAvatarController`:

- `employees.avatar` — authenticated, authorized by `EmployeePolicy::view`, so
  the city scope applies to the image itself and not merely to the page that
  embeds it.
- `digital-id.avatar` — a short-lived signed URL (`uploads.digital_id_avatar_ttl`,
  default 10 minutes) minted by `ShowDigitalId` only after the bearer token has
  resolved to an active employee with an enabled card.

Views must use `$employee->avatar_url`, never `getFirstMediaUrl('avatar')`.

## Digital ID cards

`App\Services\DigitalIdManager` provides enable / disable / rotate. Rotating
issues a new token and immediately invalidates every printed card — this is the
response to a lost, cloned, or confiscated card. Deactivating an employee
disables their card automatically. Token values are never written to the audit
log.

## Offboarding

Setting `employees.is_active = 0` (from the edit form, `deactivate:employee`, or
directly) fires `EmployeeObserver`, which revokes the digital ID and calls
`AccessRevoker`: database sessions deleted, `remember_token` cleared, all role
assignments stood down, event recorded under the `security` log. Independently,
`EnsureAccountIsActive` re-reads the active flag on every authenticated request,
so a deactivation applied straight in the database also takes effect on the next
request.

Run before switching traffic:

```bash
composer audit
npm audit --omit=dev
php artisan test
php artisan route:list --except-vendor
```

Ensure the scheduler and queue worker are managed by Supervisor, systemd, or
the hosting platform. Restrict write permissions to `storage/` and
`bootstrap/cache/`; the web-server account must not be able to modify
application source.

## CSP rollout

Inline script tags now receive the per-request CSP nonce. Start with
`CSP_REPORT_ONLY=true`, exercise every workflow in staging, and collect browser
CSP violations. Move any remaining incompatible third-party or inline behavior
into the Vite bundle. Set `CSP_REPORT_ONLY=false` only after the report is clean;
enforcing early can break Livewire/Alpine interactions.

## Adding a Livewire action

Every public method on a Livewire component is an HTTP endpoint that the browser
can call with arbitrary arguments, whether or not a button is rendered for it.
`LivewireMutationCoverageTest` fails the build until a new action is classified
in `config/access-control.php` — either under `livewire` with a permission, or
under `livewire_exempt` with a comment stating why it is safe.

The middleware permission map is coarse. It is not a substitute for the real
check: ownership and city scope belong **inside** the method, as a `Gate` call
or a query scoped through the authorizing relationship. The rule of thumb is
that an id arriving from the browser must be used to *filter* a query, never to
look a record up and then decide about it afterwards.

## Rendering rule for tables

The table library escapes column values by default. `asHtml()` turns that off
for the whole column, so a column marked `asHtml()` may only compose
server-generated markup: literal HTML, translated strings, and integer primary
keys. Any value that originates in the database or from a user must be passed
through `e()` first — see `ActivityLogTable::summarise()` for the pattern, and
`ActivityLogRenderingTest` for the regression coverage.

## Encrypted PII

These employee columns are encrypted at rest via the `EncryptedPii` cast:
national number, civil registry number and office, both address fields, personal
and work phone, personal email, WhatsApp and Telegram handles.

Uniqueness and exact lookup of the national number are preserved by a **blind
index** (`national_number_index`) — HMAC-SHA256 of the normalized value under a
key derived from `APP_KEY`. Use `Employee::whereNationalNumber($value)` for
lookups; the plaintext column can no longer be queried directly. Partial
matching (`LIKE '%123%'`) on these columns is not possible by design.

Deploy order is **strict**:

1. `php artisan migrate` — widens the columns and adds the index
2. deploy the application code
3. `php artisan security:encrypt-pii --dry-run` then without the flag

The command is resumable, batched, verifies every row it writes, and reverses
with `--decrypt` if the release has to be rolled back. Running steps out of
order will not corrupt data — the cast reads plaintext and ciphertext alike
during the transition — but leaves PII readable until step 3 completes.

## Known gaps

These remain open and are **not** closed by code:

- **Notification of password changes and account lockouts.**
- **Platform controls**: WAF, centralized monitoring and alerting, encrypted
  backups with a *tested* restore, restricted database and filesystem service
  accounts, routine patching.
- **An authenticated penetration test.** Roughly two thirds of the application
  has not been reviewed line by line — the leave workflow, badges, contract
  creation, the Excel import/export paths, and archive sharing in particular.
  A live privilege escalation was found in `AccessManagement\Index` *after* that
  file had already been hardened once, which is the best available evidence for
  what an independent test is worth here.

## Key management

`APP_KEY` now protects two separate things: session encryption and all
encrypted PII. It must live in the deployment secret
manager, be backed up separately from the database, and never be rotated without
a planned re-encryption pass (`security:encrypt-pii --decrypt`, rotate, re-run).
A database backup and a key backup stored together defeat the entire scheme.

## Operational controls

- Restrict the HR application to the organizational VPN or an identity-aware
  proxy where possible.
- Centralize logs, alert on repeated authentication failures and authorization
  denials, and retain audit logs according to HR/legal policy.
- Back up the database and `storage/app/private`, encrypt backups, and perform
  restore drills.
- Rotate privileged credentials, review active roles quarterly, and immediately
  deactivate accounts when employment ends.
- Run authenticated DAST and an external penetration test before launch and
  after material authentication, authorization, or upload changes.
