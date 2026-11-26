@props(['case'])

{{-- Old name kept for the task card (plan task 2.4): a task status as the
     shared status chip. The markup lives in FluxMarkup::statusPill(), the same
     `ui-chip` shape and StatusTone colours as x-ui.chip, so list rows can emit
     it without a component render each. --}}
{{ \App\Support\FluxMarkup::statusPill($case, (string) $attributes->get('class')) }}
