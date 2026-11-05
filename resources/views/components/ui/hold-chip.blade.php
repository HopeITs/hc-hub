{{-- The one "On hold" chip (W-06, D-58).

     A hold is a flag on the status, never a status of its own, so it is drawn
     beside the status rather than instead of it: on the board card and on the
     current stop of the lifecycle stepper (W-25). Text and an icon, never
     colour alone; the tone is StatusTone::Warn, as contracts.md §1 requires.
     The full label "Submitted (On hold)" is x-ui.enum-badge's job, through
     Proposal::statusLabel(). --}}
<span
    {{ $attributes->class('inline-flex items-center gap-1 rounded-md border px-1.5 py-0.5 text-[11px] font-semibold whitespace-nowrap '.\App\Enums\StatusTone::Warn->badgeClasses()) }}
    data-tone="warn"
    data-test="on-hold-chip"
>
    <flux:icon.pause-circle variant="micro" class="size-3 shrink-0" aria-hidden="true" />
    <span>{{ __('proposals.on_hold_chip') }}</span>
</span>
