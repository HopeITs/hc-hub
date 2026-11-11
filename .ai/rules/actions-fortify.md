---
paths:
  - app/Actions/Fortify/AuthenticateUser.php
---

# Actions Fortify

## Login errors are field-specific: unknown email → email, wrong password → password
By explicit user request (matching a reference login design), a failed login no longer always attaches to the `email` field the way vanilla Fortify does. `AuthenticateUser::__invoke()` looks the user up by `users.email` FIRST: if no row exists (or `User::hasLostAccess()`), it returns null (Fortify's own pipeline then attaches `auth.failed` to `email`, unchanged). If the user exists but the password doesn't `Hash::check()`, it throws `ValidationException::withMessages(['password' => [trans('auth.failed')]])` itself — this bypasses Fortify's `AttemptToAuthenticate::throwFailedAuthenticationException()` entirely (a callback-thrown exception propagates out of `handleUsingCustomCallback()` before Fortify's own `if (! $user)` check runs), so the action ALSO calls `app(LoginRateLimiter::class)->increment($request)` itself before throwing — matching Fortify's own behavior — otherwise a wrong-password guess against a real account would silently skip the 5-attempts-per-minute throttle that `EnsureLoginIsNotThrottled` enforces on the next request.

Trade-off worth knowing: this reveals to a caller whether a given email has an account at all (email-not-found vs password-wrong are now visibly different responses), which vanilla Fortify deliberately avoids for user-enumeration reasons. Accepted knowingly for this internal HR tool, not something to silently carry over if this pattern gets copied elsewhere.

`confirmsPassword()` (used by the password-confirmation feature, not login) is untouched — it still checks email+password together via the private `accountFor()` helper and returns a single bool, since that flow only has one password field to attach an error to.
