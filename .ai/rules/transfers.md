---
paths:
  - 'resources/views/pages/transfers/**'
---

# Transfers

## Never net overages against shortfalls in the transfers ledger
The transfers ledger reports the shortfall as SUM of each tranche's own loss, computed per row in SQL — never as (total sent − total landed). A first version used the totals difference and announced "Landed in full" over 24,785 USD that genuinely never arrived, because one tranche had arrived 30,000 over on a favourable rate and cancelled four that arrived short. An overage is real but it does not put the missing money back, so it is reported as its own figure ("Arrived over") beside the shortfall. TransferTrackingTest has a regression test named "a tranche that arrived over never cancels one that arrived short".

Only "settled" tranches feed the shortfall: ar_status = received AND net_amount_received IS NOT NULL AND amount_received_currency = amount_transfered_currency. Money still in transit has not lost anything yet, and a tranche that landed in a different currency is never subtracted from what was sent — it is disclosed as a converted-tranche count instead.

The ledger aggregate deliberately ignores the stage filter (filteredQuery(withStage: false)) so the figures keep describing the whole filtered set while a stage narrows only the ladder. Assert stage/sort behaviour on the ladder rows (wire:key="transfer-{id}") — asserting on formatted amounts passes for the wrong reason, because the same number also appears in the ledger figures above.

## The register leads with three states, and the row is five columns (2026-11-09)
/transfers opens on **Needs attention / On its way / Landed** (`focus`, `toggleFocus()`, `focusCounts()`), disjoint and exhaustive, each a filter, so they always add up to the page count. They are built from `focusSql()` over the existing `stageSql()` conditions — never a second definition of "late". Attention = overdue ∪ failed ∪ short ∪ unrecorded; landed = received minus the last two. Mirrors funding-agreements/index and reports/index.

The row keeps status, scheduled, amount sent (currency inline), net received and the difference. Account, sent and received dates, currency, fees, exchange rate and gross moved out: thirteen columns forced `register-xl` to 1,517px in a 1,070px container, so the edit button sat past a sideways scroll. `x-funding.tranche-head`, `-row` and `-group` take `compact` (default false) — donors/show, proposals/show and funding-agreements/show still render every column. Search and donor sit in the register bar and filter live; a page holds 25 rows.

## The fees ARE the shortfall: never print them as two losses
Fixed 2026-11-09. The ledger showed "Fees 13,201.78" and "Shortfall −13,201.78" as siblings at the same size, so they read as 26,404 gone when nothing was unaccounted for. One figure now carries what was lost, with a note naming its parts ("fees :fees · unexplained :gap", `transfers.shortfall_parts`); the parts sum to the figure exactly. `exchangeGap` is the number worth chasing and must stay visible.

`landedShortSql()` tested `net < payment`, which every transfer that paid a bank fee meets — the status was true for every settled tranche and distinguished nothing. It now subtracts fees recorded in the sending currency, matching ReadsTransferLedger's own `$rowFees` arithmetic, and is labelled "Lost more than its fees". "Landed short" and "Net not recorded" moved from the Flags row into the status row: they write the same `stage` property as the five statuses, so under a separate heading they looked combinable while silently replacing the chosen status.

## One word per idea on the transfers page (2026-11-09, phases 3–4)
The ledger captioned the net figure "Landed" while the totals row directly above it called the same number "Received (net)" (`finance_tables.received_net`). **Received** is the noun now: `transfers.landed` → "Received" / «المستلم», and `transfers.not_recorded_yet` matches `finance_tables.flag_unrecorded` ("Net not recorded" / «الصافي غير مسجّل»). The headline state is **"Fully received"** / «مستلمة بالكامل», deliberately *not* the word the raw `received` status uses — they select different tranches (the headline excludes short and unrecorded), so they must not share a word. TransferRegisterTest pins all three pairs in both locales.

"Landed" survives as a verb only — "has not landed yet", "Landed in 5 days", "Landed in full" — where it means *arrived*, the distinction between gross sent and net arrived.

Polish in the same pass: the row's edit button is a 44px target under `sm` and is named for the tranche it edits (`common.edit_name` with donor · date) instead of six identical "Edit transfer" announcements. The Overview ladder prints the year on any date outside the current one — `formats.date_short` drops it, and a ledger spanning 2025–2027 showed "30 December" next to 2027 rows.
