{{-- The one "On hold" chip (W-06, D-58), drawn through x-ui.chip (plan task 2.4).

     A hold is a flag on the status, never a status of its own, so it is drawn
     beside the status rather than instead of it: on the board card and on the
     current stop of the lifecycle stepper (W-25). Text and an icon, never
     colour alone; the tone is StatusTone::Warn, as contracts.md §1 requires.
     The full label "Submitted (On hold)" is x-ui.enum-badge's job, through
     Proposal::statusLabel(). --}}
<x-ui.chip tone="warn" icon="pause-circle" :attributes="$attributes->merge(['data-test' => 'on-hold-chip'])">{{ __('proposals.on_hold_chip') }}</x-ui.chip>
