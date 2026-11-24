<?php

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Hr\Models\ProbationReview;
use Modules\Hr\Support\Contracts\ProbationReviews;

/*
 * The last-third review of a trial contract. The direct manager picks what
 * they ask HR for (extend, confirm or cancel) and must give the reason; the
 * request goes to HR and changes nothing on the contract. HR of the city can
 * read the request. Every action re-authorizes through ProbationReviewPolicy.
 */
new #[Layout('layouts::desk'), Title('hr_probation.title')] class extends Component {
    #[Locked]
    public int $reviewId;

    public string $decision = '';

    public string $reason = '';

    public function mount(ProbationReview $review): void
    {
        Gate::authorize('view', $review);

        $this->reviewId = $review->id;
        app(ProbationReviews::class)->markSeenByHr($review, Auth::user());
    }

    #[Computed]
    public function review(): ProbationReview
    {
        $review = ProbationReview::query()
            ->with(['employee:id,first_name_en,first_name_ar,last_name_en,last_name_ar,username,city_id', 'contract.position:id,title_en,title_ar', 'decidedBy.employee:id,first_name_en,first_name_ar,last_name_en,last_name_ar'])
            ->findOrFail($this->reviewId);

        Gate::authorize('view', $review);

        return $review;
    }

    public function send(): void
    {
        $review = ProbationReview::query()->findOrFail($this->reviewId);

        Gate::authorize('decide', $review);

        $this->validate([
            'decision' => ['required', 'in:'.implode(',', ProbationReview::DECISIONS)],
            'reason' => ['required', 'string', 'max:2000', 'regex:/\S/'],
        ], [], [
            'decision' => __('hr_probation.fields.decision'),
            'reason' => __('hr_probation.fields.reason'),
        ]);

        $sent = app(ProbationReviews::class)->request($review, $this->decision, $this->reason, Auth::user());

        unset($this->review);
        $this->reset('decision', 'reason');

        Flux::toast(variant: $sent ? 'success' : 'warning', text: $sent ? __('hr_probation.sent') : __('hr_probation.already'));
    }
};
?>

@php
    $review = $this->review;
    $employee = $review->employee;
    $contract = $review->contract;
    $reviews = app(\Modules\Hr\Support\Contracts\ProbationReviews::class);
    $canDecide = ! $review->isDecided() && $reviews->canDecide(auth()->user(), $review);
    $lastThirdStart = $reviews->lastThirdStart($contract);
    $date = fn (?\DateTimeInterface $value): string => $value?->translatedFormat(__('formats.date')) ?? '—';
@endphp

<section class="flex flex-col gap-5" data-probation-review>
    <x-ui.detail-header
        :breadcrumbs="[
            ['label' => __('hr_contracts.titles.show'), 'url' => route('hr.contracts.show', $contract->id)],
            ['label' => __('hr_probation.title')],
        ]"
        :title="__('hr_probation.title')"
        :subtitle="__('hr_probation.lead', ['name' => (string) $employee?->name])"
    />

    <x-ui.panel :title="__('hr_probation.title')">
        <dl class="grid gap-4 p-4 sm:grid-cols-2">
            <x-hr::employees.fact :label="__('hr_probation.fields.employee')" :value="$employee?->name" />
            <x-hr::employees.fact :label="__('hr_probation.fields.position')" :value="$contract->position?->title" />
            <x-hr::employees.fact :label="__('hr_probation.fields.last_third')" :value="$date($lastThirdStart)" />
            <x-hr::employees.fact :label="__('hr_probation.fields.ends')" :value="$date($contract->end_date)" />
        </dl>
    </x-ui.panel>

    @if ($review->isDecided())
        <x-ui.panel :title="__('hr_probation.status.requested')" data-probation-decided>
            <div class="grid gap-3 p-4 text-sm">
                <p><span class="font-semibold">{{ __('hr_probation.fields.decision') }}:</span> {{ __('hr_probation.decision.'.$review->decision) }}</p>
                <p><span class="font-semibold">{{ __('hr_probation.fields.reason') }}:</span> <span dir="auto">{{ $review->reason }}</span></p>
                <p class="text-secondary-medium dark:text-ink-muted">{{ __('hr_probation.by', ['name' => (string) ($review->decidedBy?->employee?->name ?? '—'), 'date' => $date($review->decided_at)]) }}</p>
                <p class="text-secondary-medium dark:text-ink-muted">{{ __('hr_probation.nothing_applied') }}</p>
            </div>
        </x-ui.panel>
    @elseif ($canDecide)
        <x-ui.panel :title="__('hr_probation.send')">
            <form wire:submit="send" class="grid gap-4 p-4" data-probation-form>
                <p class="text-sm text-secondary-medium dark:text-ink-muted">{{ __('hr_probation.form_hint') }}</p>

                <x-ui.form-select wire:model="decision" :label="__('hr_probation.fields.decision')" :placeholder="__('common.select')" required>
                    @foreach (\Modules\Hr\Models\ProbationReview::DECISIONS as $option)
                        <option value="{{ $option }}">{{ __('hr_probation.decision.'.$option) }}</option>
                    @endforeach
                </x-ui.form-select>

                <x-ui.form-textarea wire:model="reason" :label="__('hr_probation.fields.reason')" rows="4" maxlength="2000" required />

                <div class="flex justify-end">
                    <x-ui.button type="submit" tone="primary" data-test="send-probation-request">{{ __('hr_probation.send') }}</x-ui.button>
                </div>
            </form>
        </x-ui.panel>
    @else
        <x-ui.panel :title="__('hr_probation.status.waiting')" data-probation-waiting>
            <p class="p-4 text-sm text-secondary-medium dark:text-ink-muted">{{ __('hr_probation.waiting_hint') }}</p>
        </x-ui.panel>
    @endif
</section>
