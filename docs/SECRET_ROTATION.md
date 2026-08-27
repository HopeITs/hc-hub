# Exposed SMTP credential — rotation checklist

## What happened

`.env.example` is tracked in Git and contained a live Mailtrap SMTP username and
password. The values were introduced in commit `876ee832` ("add flowbite version
with auth") and remained readable to anyone with repository access — including
every clone and fork — until they were replaced with blank placeholders.

Exposed values (now revoked/to be revoked):

- `MAIL_HOST=sandbox.smtp.mailtrap.io`
- `MAIL_USERNAME=049a22cd84dffa`
- `MAIL_PASSWORD=e069…` (full value is in the Git history below)

## Actions that must be performed by a human

Deleting the value from the current file is **not** sufficient — the credential
is still present in every historical commit and in every existing clone.

1. **Revoke first, then replace.** In the Mailtrap dashboard, delete the inbox
   credential pair and issue a new one. Do not reuse the username.
2. **Store the new value only in `.env`** (untracked) or the deployment secret
   manager. Never in `.env.example`.
3. **Review provider access logs** for the whole period since `876ee832` for
   sends that the application did not originate, unfamiliar source IPs, or
   volume spikes. Mailtrap sandbox inboxes do not deliver externally, so the
   blast radius is limited to inbox content disclosure — but confirm the same
   credential was never reused on a production sending domain.
4. **Confirm no other secret file is tracked:**

```bash
git ls-files | grep -Ei '(^|/)\.env($|\.)|secret|credential'
```

   Only `.env.example` should appear.

## Removing the value from history

Rewriting history is destructive and invalidates every existing clone and open
pull request, so it needs an explicit maintenance window and coordination with
everyone who has cloned the repository. Rotation above is mandatory; the rewrite
is cleanup.

```bash
git clone --mirror <remote-url> hc-hr-mirror
cd hc-hr-mirror
printf '049a22cd84dffa==>REDACTED\ne06971c8087473==>REDACTED\n' > replacements.txt
git filter-repo --replace-text replacements.txt
git push --force --mirror
```

Afterwards every collaborator must re-clone. Ask the hosting provider (GitHub /
GitLab) to expire cached views of the old objects, since force-pushing does not
immediately remove them from the provider's cache or from any fork.

## Preventing recurrence

- `.github/workflows/security.yml` runs **gitleaks** on every push and pull
  request; `.gitleaks.toml` adds a rule that fails the build when any credential
  key in `.env.example` has a non-empty value.
- Enable the hosting provider's own push protection / secret scanning as a second
  layer, so a secret is blocked before it reaches the remote rather than being
  reported after.
