# Recruitment authorization

Recruitment access always has two layers:

1. `User::hasActivePermission()` checks the permission of the user's current role.
2. A policy calls `RecruitmentScopeResolver` to check the specific record or workflow step.

Neither layer is sufficient by itself. Route middleware protects page entry, while policies and scoped queries prevent IDOR/BOLA when a caller changes an ID or invokes an action directly.

## Scoped reads

All recruitment lists, exports, counters, and selectors must start from a user-scoped query:

```php
$requests = RecruitmentRequest::query()
    ->visibleTo($user)
    ->latest()
    ->paginate();
```

Equivalent `visibleTo($user)` scopes exist on `Vacancy`, `HiringCampaign`, `JobPost`, `ApplicationForm`, `PublicRecruitmentLink`, and `RecruitmentRequestEvent`.

Never load an unrestricted record by an ID supplied by the browser and then rely on a hidden button. Authorize the resolved model:

```php
Gate::authorize('view', $recruitmentRequest);
Gate::authorize('update', $recruitmentRequest);
```

## Request creation

Opening the create screen may call the policy without a city. Saving must pass the submitted scope city so regional and structural scope is checked on the backend:

```php
Gate::authorize('create', [RecruitmentRequest::class, $scopeCityId]);
```

## Workflow decisions

Approval, rejection, and requesting changes are authorized against the resolved workflow instance step—not only against the request:

```php
Gate::authorize('approve', $workflowInstanceStep);
Gate::authorize('reject', $workflowInstanceStep);
Gate::authorize('requestChanges', $workflowInstanceStep);
```

The check requires all of the following at action time:

- the corresponding active-role permission;
- an undecided step;
- the acting user is the stored assignee;
- the active role still matches the assigned role;
- the user's current organizational assignment still matches the stored scope.

Missing or unknown scope data fails closed. Historical events remain interpretable through their stored actor, role, and scope fields even after a user's current role changes.
