---
paths:
  - '{modules/Tracking/resources/views/pages/transfers/**,modules/Tracking/resources/views/pages/funding-agreements/**,modules/Tracking/resources/views/pages/donors/**,modules/Tracking/resources/views/pages/proposals/**,modules/Tracking/resources/views/pages/admin/⚡accounts.blade.php}'
---

# Admin

## Funding pages open on the table
Funding pages (indexes, agreement/donor detail, proposal Funding tab) open on the table view via SwitchesFundingView. The old visuals live in `_overview` / `_show-overview` partials (or an @if on the proposal page) behind `view=overview`. Tests that assert those visuals call `->set('view', 'overview')`; never delete them.

## Accounts page: totals say when money landed in another currency (2026-11-09)
Owner chose "fixes only" for admin/accounts: Table and Overview stay. A currency's totals line is by account currency, so money that landed in a different currency than its account (e.g. USD into a EUR account) shows 0.00 there; the line now carries a note ("N tranches landed in another currency", counted, never converted) from `other_currency` in `AccountMoney::totalsSelect()`. The close dialog names the account (`closingAccountName`, lang `accounts.close_account_message`). Left alone on purpose: the 5-row default page size (shared via FiltersFinanceRegister) and the Overview "Funds received" card, which ignores filters.

