{{--
    The Overview view of the funding agreements page: the per-currency
    collection ledger, then the stage chips and the cash-flow calendar.
    Included by ⚡index.blade.php. Each part defines its own formatting
    variables, so they read nothing from the page except $this and $stage.
--}}
@include('tracking::pages.funding-agreements._overview-ledger')
@include('tracking::pages.funding-agreements._overview-calendar')
