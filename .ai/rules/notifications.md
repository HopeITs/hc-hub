---
paths:
  - 'resources/views/pages/notifications/**'
---

# Notifications

## Inbox headline numbers are three toggles; secondary filters sit behind More filters
Review-page pass (2026-11-09): the inbox rail shows at most three headline numbers (Unread, Critical, Snoozed). Each is a button that filters the list and clears when pressed again (`toggleState('unread'|'snoozed')`, `showCritical`). The old Alerts figure was removed; alerts stay reachable through the Type filter. Status stays visible in the rail; Priority, Type and Modules sit behind a "More filters" toggle (`data-more-filters`) that starts open only when one of them is active.

## Notification settings live at /notifications-settings, not under /settings
User decision (2026-09-23): the /settings area is not used. Notification settings are the standalone page pages/notifications/⚡settings.blade.php at GET /notifications-settings (route name notification-settings.edit, in routes/web.php), with its own x-ui.page-header, no settings layout or settings nav entry. It is reached from the bell panel's gear icon. Add future notification preferences (module mutes) to this page.
