<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Hr\Actions\Recruitment\RecruitmentHiringConversionService as Conversions;
use Modules\Hr\Enums\Gender;
use Modules\Hr\Enums\MaritalStatus;
use Modules\Hr\Enums\Nationality;
use Modules\Hr\Enums\SalaryPer;
use Modules\Hr\Enums\SessionDuration;
use Modules\Hr\Livewire\Concerns\Recruitment\AuthorizesRecruitment;
use Modules\Hr\Livewire\Concerns\Recruitment\ResolvesJobOffers;
use Modules\Hr\Models\RecruitmentHiringConversion;
use Modules\Hr\Support\Employees\EnumLabel;

/*
 * Hire the candidate whose offer was accepted: create the employee (or link
 * the one they already are) and the contract, from the accepted version of
 * the offer, in one transaction (RecruitmentHiringConversionService).
 *
 * The contract terms are shown read-only: they are the accepted snapshot.
 * What HR fills in is who the employee is and, when the pay is per session,
 * the session length the offer does not carry. What would stop the hire is
 * listed before anything is typed; the service checks it again on the
 * locked rows. Once done, the page links to the hub's employee and contract.
 */
new #[Layout('layouts::desk'), Title('hr_recruitment.hub.titles.offer_convert')] class extends Component {
    use AuthorizesRecruitment;
    use ResolvesJobOffers;

    #[Locked]
    public int $offerId;

    /** 'create' | 'link' */
    public string $mode = '';

    public string $employeeId = '';

    /** @var array<string, mixed> the new employee; see Conversions::prefill() */
    public array $employee = [];

    public bool $confirmNotDuplicate = false;

    public string $sessionDuration = '';

    public bool $showConfirm = false;

    public string $confirmText = '';

    public function mount(int $offerId, Conversions $conversions): void
    {
        $this->requirePermission('recruitment-applicants.convert-hires');
        $this->offerId = $offerId;
        $conversion = $this->resolveConversion();

        if ($conversion->isConverted()) {
            return;
        }

        Gate::authorize('convert', $conversion);

        $this->employee = $conversions->prefill($conversion);

        // A member of staff who signed in to apply is that employee: linking
        // is preselected. No other match is ever preselected.
        $internal = $conversions->matches($conversion)
            ->first(fn (array $match): bool => in_array(Conversions::MATCH_INTERNAL, $match['reasons'], true));

        $this->mode = $internal ? Conversions::MODE_LINK : Conversions::MODE_CREATE;
        $this->employeeId = $internal ? (string) $internal['employee']->getKey() : '';
    }

    #[Computed]
    public function conversion(): RecruitmentHiringConversion
    {
        return $this->resolveConversion()->load([
            'jobOffer:id,application_id,vacancy_id,position_id,status',
            'jobOffer.application:id,applicant_id',
            'jobOffer.application.applicant:id,full_name',
            'jobOffer.position:id,title_en,title_ar,exist_emp,max_emp',
            'jobOffer.vacancy:id,needed_count,filled_count',
            'jobOfferVersion.contractType:id,name_en,name_ar',
            'employee',
            'employee.account:id,employee_id',
            'convertedBy:id,email,employee_id',
        ]);
    }

    /** @return array<string, mixed> */
    #[Computed]
    public function state(): array
    {
        $conversion = $this->conversion;
        $converted = $conversion->isConverted();
        $conversions = app(Conversions::class);
        $offer = $conversion->jobOffer;

        return [
            'converted' => $converted,
            'canSeeSalary' => $offer !== null && Gate::allows('viewSalary', [$offer, $conversion->jobOfferVersion]),
            'blockers' => $converted ? [] : $conversions->blockers($conversion),
            'matches' => $converted ? collect() : $conversions->matches($conversion, $this->employee['nationalNumber'] ?? null),
            'needsSessionDuration' => (int) $conversion->jobOfferVersion?->salary_per === SalaryPer::Session->value,
        ];
    }

    /**
     * Asks before hiring; the confirm button runs save(). Nothing is checked
     * or written here: save() does all of it, on the locked rows.
     */
    public function confirmHire(Conversions $conversions): void
    {
        $conversion = $this->conversion;
        Gate::authorize('convert', $conversion);
        $this->resetErrorBag();

        // A name only for an employee the candidate may be linked to: an id
        // typed into the request names nobody.
        $linked = $this->mode === Conversions::MODE_LINK
            ? $conversions->matches($conversion, $this->employee['nationalNumber'] ?? null)->first(
                fn (array $match): bool => $match['kind'] === Conversions::MATCH_LINKABLE && (string) $match['employee']->getKey() === $this->employeeId,
            )
            : null;
        $position = (string) $conversion->jobOffer?->position?->title;

        $this->confirmText = $linked
            ? __('hr_recruitment.applicant_ui.conversions.confirm_text_link', ['employee' => $linked['employee']->name, 'position' => $position])
            : __('hr_recruitment.applicant_ui.conversions.confirm_text_create', ['position' => $position]);
        $this->showConfirm = true;
    }

    public function save(Conversions $conversions): void
    {
        $this->requirePermission('recruitment-applicants.convert-hires');
        $conversion = $this->resolveConversion();
        Gate::authorize('convert', $conversion);
        $this->resetErrorBag();
        $this->showConfirm = false;

        try {
            $conversions->convert($this->viewer(), $conversion, [
                'mode' => $this->mode,
                'employeeId' => $this->employeeId,
                'employee' => $this->employee,
                'confirmNotDuplicate' => $this->confirmNotDuplicate,
                'sessionDuration' => $this->sessionDuration,
            ]);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }

            unset($this->conversion, $this->state);

            return;
        } catch (AuthorizationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            // Already rolled back and recorded on the conversion as a failed
            // attempt; HR gets a plain message rather than an error page.
            report($exception);
            $this->addError('conversion', __('hr_recruitment.applicant_ui.conversions.errors.unexpected'));
            unset($this->conversion, $this->state);

            return;
        }

        // Stay here — the page now shows the hire — and say what comes next.
        unset($this->conversion, $this->state);
        $conversion = $this->conversion;

        $this->toast(__('hr_recruitment.applicant_ui.conversions.converted').'. '
            .__('hr_recruitment.applicant_ui.conversions.converted_next', ['name' => (string) $conversion->employee?->name, 'contract' => $conversion->contract_id])
            .' '.($conversion->employee?->account
                ? __('hr_recruitment.applicant_ui.conversions.has_account')
                : __('hr_recruitment.applicant_ui.conversions.no_account_hub')));
    }

    /** Out of reach is a 404, like everywhere else on these pages. */
    private function resolveConversion(): RecruitmentHiringConversion
    {
        $offer = $this->resolveOffer($this->offerId);

        return RecruitmentHiringConversion::query()->where('job_offer_id', $offer->getKey())->firstOrFail();
    }
};
?>

@php
    $conversion = $this->conversion;
    $state = $this->state;
    $offer = $conversion->jobOffer;
    $version = $conversion->jobOfferVersion;
    $applicant = $offer?->application?->applicant;
    $vacancy = $offer?->vacancy;
    $position = $offer?->position;
    $matches = $state['matches'];
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $alert = 'rounded-md border border-primary bg-primary/5 p-3 text-sm text-primary';

    // The identity rule (Conversions::matches()): only the same person can be linked.
    $linkable = $matches->where('kind', Conversions::MATCH_LINKABLE);
    $warnings = $matches->where('kind', Conversions::MATCH_WARNING);
    $conflicts = $matches->where('kind', Conversions::MATCH_ID_CONFLICT);
    $nameFields = [
        'firstName' => 'first_name', 'lastName' => 'last_name', 'fatherName' => 'father_name', 'motherName' => 'mother_name',
    ];
@endphp

<div class="space-y-6" data-conversion="{{ $conversion->id }}">
    <x-ui.detail-header
        :back="\App\Support\BackUrl::for(route('hr.recruitment.offers.show', ['offerId' => $offerId]))"
        :breadcrumbs="[['label' => __('hr_recruitment.hub.section')], ['label' => __('hr_recruitment.hub.titles.offers'), 'href' => route('hr.recruitment.offers.index')], ['label' => __('hr_recruitment.hub.titles.offer'), 'href' => route('hr.recruitment.offers.show', ['offerId' => $offerId])]]"
        :title="__('hr_recruitment.applicant_ui.conversions.title')"
    >
        <x-slot:meta>
            <span class="font-medium"><bdi>{{ $applicant?->full_name }}</bdi></span>
            <span dir="auto">{{ $position?->title }}</span>
            <span>{{ __('hr_recruitment.applicant_ui.offers.document_title') }} <span class="tabular-nums">v{{ $version?->version_no }}</span></span>
        </x-slot:meta>
    </x-ui.detail-header>

    @error('conversion')
        <p role="alert" class="{{ $alert }}">{{ $message }}</p>
    @enderror

    @if ($state['converted'])
        {{-- Done: what was created, and by whom. --}}
        <x-ui.panel :title="__('hr_recruitment.applicant_ui.conversions.statuses.converted')">
            <div class="space-y-3 p-4 text-sm">
                <p>{{ __('hr_recruitment.applicant_ui.conversions.converted_by', ['name' => $conversion->convertedBy?->name ?? '—', 'date' => $conversion->converted_at?->translatedFormat(__('formats.date').' H:i')]) }}</p>
                <div class="flex flex-wrap gap-2">
                    @if ($conversion->employee?->username)
                        <x-ui.button size="sm" tone="secondary" icon="user" :href="route('hr.employees.show', ['employee' => $conversion->employee->username])" wire:navigate>{{ __('hr_recruitment.applicant_ui.conversions.open_employee') }}: {{ $conversion->employee->name }}</x-ui.button>
                    @endif
                    @if ($conversion->contract_id)
                        <x-ui.button size="sm" tone="secondary" icon="document-text" :href="route('hr.contracts.show', ['contract' => $conversion->contract_id])" wire:navigate>{{ __('hr_recruitment.applicant_ui.conversions.open_contract') }} #{{ $conversion->contract_id }}</x-ui.button>
                    @endif
                    @if ($conversion->employee && $this->may('employees.update'))
                        <x-ui.button size="sm" tone="ghost" icon="pencil-square" :href="route('hr.employees.edit', ['employee' => $conversion->employee->id])" wire:navigate>{{ __('hr_recruitment.applicant_ui.conversions.open_employee_edit') }}</x-ui.button>
                    @endif
                </div>
                @if ($conversion->employee && ! $conversion->employee->account)
                    <p class="{{ $muted }}">{{ __('hr_recruitment.applicant_ui.conversions.no_account_hub') }}</p>
                @endif
            </div>
        </x-ui.panel>
    @else
        <p class="text-sm {{ $muted }}">{{ __('hr_recruitment.applicant_ui.conversions.hint') }}</p>

        {{-- What stops the hire right now; checked again on the locked rows when HR confirms. --}}
        @if ($state['blockers'] !== [])
            <section role="alert" class="space-y-2 {{ $alert }}">
                <p class="font-semibold">{{ __('hr_recruitment.applicant_ui.conversions.blockers_title') }}</p>
                <ul class="list-disc space-y-1 ps-5">
                    @foreach ($state['blockers'] as $code)
                        <li>{{ __('hr_recruitment.applicant_ui.conversions.errors.'.$code) }}</li>
                    @endforeach
                </ul>
                <p class="text-secondary-dark dark:text-ink">{{ __('hr_recruitment.applicant_ui.conversions.blockers_hint') }}</p>
            </section>
        @endif

        @if ($conversion->status === RecruitmentHiringConversion::STATUS_FAILED && $conversion->failure_reason)
            <p class="rounded-md bg-surface-2 p-3 text-sm">{{ __('hr_recruitment.applicant_ui.conversions.last_failure', ['count' => $conversion->attempt_count, 'reason' => __('hr_recruitment.applicant_ui.conversions.errors.'.$conversion->failure_reason)]) }}</p>
        @endif

        {{-- The accepted snapshot: the contract takes these, unchanged. --}}
        <x-ui.panel :title="__('hr_recruitment.applicant_ui.conversions.accepted_terms')">
            <div class="space-y-4 p-4">
                @if ($version)
                    <x-hr::recruitment.offer-terms :version="$version" :can-see-salary="$state['canSeeSalary']" />
                @endif
                <p class="text-xs {{ $muted }}">
                    {{ __('hr_recruitment.applicant_ui.conversions.seat', [
                        'filled' => (int) $vacancy?->filled_count, 'needed' => (int) $vacancy?->needed_count,
                        'exist' => (int) $position?->exist_emp, 'max' => (int) $position?->max_emp,
                    ]) }}
                    · {{ __('hr_recruitment.applicant_ui.conversions.schedule_hint') }}
                </p>
            </div>
        </x-ui.panel>

        <form wire:submit="confirmHire" class="space-y-6">
            {{-- Who the employee is. --}}
            <x-ui.panel :title="__('hr_recruitment.applicant_ui.conversions.employee_section')" :clip="false">
                <div class="space-y-5 p-4">
                    {{-- The national number is on file under another name: the hire stops. --}}
                    @foreach ($conflicts as $match)
                        <div role="alert" class="{{ $alert }}" wire:key="conflict-{{ $match['employee']->id }}">
                            <p class="font-semibold">{{ __('hr_recruitment.applicant_ui.conversions.id_conflict_title') }}</p>
                            <p>{{ __('hr_recruitment.applicant_ui.conversions.id_conflict', ['number' => $employee['nationalNumber'] ?? '', 'name' => $match['employee']->name]) }}</p>
                        </div>
                    @endforeach

                    <div class="space-y-2">
                        <h3 class="text-sm font-semibold">{{ __('hr_recruitment.applicant_ui.conversions.matches_title') }}</h3>
                        <p class="text-xs {{ $muted }}">{{ __('hr_recruitment.applicant_ui.conversions.matches_hint') }}</p>
                        @forelse ($linkable as $match)
                            <label class="flex items-start gap-3 rounded-md border border-panel-border p-3 text-sm" wire:key="match-{{ $match['employee']->id }}">
                                <input type="radio" wire:model.live="employeeId" value="{{ $match['employee']->id }}" @disabled($mode !== Conversions::MODE_LINK) class="mt-1">
                                <span>
                                    <span class="font-medium">{{ $match['employee']->name }}</span>
                                    <span class="{{ $muted }}">· {{ $match['employee']->city?->name }} · <bdi>{{ $match['employee']->username }}</bdi>
                                        @unless ($match['employee']->is_active) · {{ __('hr_recruitment.applicant_ui.conversions.inactive_employee') }} @endunless
                                    </span>
                                    <span class="mt-1 flex flex-wrap gap-1">
                                        @foreach ($match['reasons'] as $reason)
                                            <span class="rounded-md bg-surface-2 px-2 py-0.5 text-xs">{{ __('hr_recruitment.applicant_ui.conversions.match_reasons.'.$reason) }}</span>
                                        @endforeach
                                    </span>
                                </span>
                            </label>
                        @empty
                            <p class="text-sm {{ $muted }}">{{ $matches->isEmpty() ? __('hr_recruitment.applicant_ui.conversions.no_matches') : __('hr_recruitment.applicant_ui.conversions.no_linkable') }}</p>
                        @endforelse
                        @error('employeeId')
                            <p role="alert" class="text-sm text-primary">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- An email or phone on someone else's record: said plainly, never linked. --}}
                    @if ($warnings->isNotEmpty())
                        <div role="status" class="space-y-1 rounded-md border border-panel-border bg-surface-2 p-3 text-sm">
                            @foreach ($warnings as $match)
                                @foreach (array_intersect([Conversions::MATCH_PHONE, Conversions::MATCH_EMAIL], $match['reasons']) as $reason)
                                    <p class="font-medium" wire:key="warning-{{ $match['employee']->id }}-{{ $reason }}">{{ __('hr_recruitment.applicant_ui.conversions.warnings.'.$reason, ['name' => $match['employee']->name]) }}</p>
                                @endforeach
                            @endforeach
                            <p class="{{ $muted }}">{{ __('hr_recruitment.applicant_ui.conversions.warning_hint') }}</p>
                        </div>
                    @endif

                    <fieldset class="flex flex-wrap gap-4 text-sm">
                        <legend class="sr-only">{{ __('hr_recruitment.applicant_ui.conversions.employee_section') }}</legend>
                        <label class="flex items-center gap-2"><input type="radio" wire:model.live="mode" value="{{ Conversions::MODE_CREATE }}"> {{ __('hr_recruitment.applicant_ui.conversions.mode_create') }}</label>
                        <label class="flex items-center gap-2"><input type="radio" wire:model.live="mode" value="{{ Conversions::MODE_LINK }}" @disabled($linkable->isEmpty())> {{ __('hr_recruitment.applicant_ui.conversions.mode_link') }}</label>
                    </fieldset>
                    @error('mode')
                        <p role="alert" class="text-sm text-primary">{{ $message }}</p>
                    @enderror

                    @if ($mode === Conversions::MODE_CREATE)
                        <div class="grid gap-5 sm:grid-cols-2">
                            @foreach ($nameFields as $name => $field)
                                @foreach (['En' => 'en', 'Ar' => 'ar'] as $suffix => $locale)
                                    <x-ui.form-input wire:key="field-{{ $name.$suffix }}" wire:model="employee.{{ $name.$suffix }}" :label="__('hr_employees.fields.'.$field.'_'.$locale)" :dir="$locale === 'ar' ? 'rtl' : 'ltr'" required />
                                @endforeach
                            @endforeach

                            <x-ui.form-select wire:model.live="employee.nationality" :label="__('hr_employees.fields.nationality')" required>
                                @foreach (EnumLabel::options(Nationality::class) as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-ui.form-select>
                            <x-ui.form-input wire:model.live.blur="employee.nationalNumber" :label="__('hr_employees.fields.national_number')" inputmode="numeric" dir="ltr" required />
                            <x-ui.form-select wire:model="employee.gender" :label="__('hr_employees.fields.gender')" :placeholder="__('hr_recruitment.choose')" required>
                                @foreach (EnumLabel::options(Gender::class) as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-ui.form-select>
                            <x-ui.form-select wire:model="employee.maritalStatus" :label="__('hr_employees.fields.marital_status')" :placeholder="__('hr_recruitment.choose')" required>
                                @foreach (EnumLabel::options(MaritalStatus::class) as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-ui.form-select>
                            <x-ui.form-input wire:model="employee.dob" :label="__('hr_employees.fields.dob')" type="date" />
                            <x-ui.form-input wire:model="employee.username" :label="__('hr_employees.fields.username')" :help="__('hr_recruitment.applicant_ui.conversions.username_hint')" dir="ltr" required />
                            <x-ui.form-input wire:model="employee.personalPhone" :label="__('hr_employees.fields.personal_phone')" type="tel" dir="ltr" />
                            <x-ui.form-input wire:model="employee.personalEmail" :label="__('hr_employees.fields.personal_email')" type="email" dir="ltr" />
                        </div>

                        @if ($linkable->isNotEmpty())
                            <div>
                                <flux:checkbox wire:model="confirmNotDuplicate" :label="__('hr_recruitment.applicant_ui.conversions.confirm_not_duplicate')" />
                                @error('confirmNotDuplicate')
                                    <p role="alert" class="mt-1 text-sm text-primary">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif
                    @endif
                </div>
            </x-ui.panel>

            @if ($state['needsSessionDuration'])
                <x-ui.panel :title="__('hr_recruitment.applicant_ui.conversions.contract_section')" :clip="false">
                    <div class="max-w-xs p-4">
                        <x-ui.form-select wire:model="sessionDuration" :label="__('hr_recruitment.applicant_ui.conversions.session_duration')" :placeholder="__('hr_recruitment.choose')" required>
                            @foreach (EnumLabel::options(SessionDuration::class) as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-ui.form-select>
                    </div>
                </x-ui.panel>
            @endif

            <div class="flex flex-wrap items-center gap-3">
                {{-- Disabled only while the hire itself runs. --}}
                <x-ui.button type="submit" tone="primary" icon="user-plus" wire:loading.attr="disabled" wire:target="confirmHire,save">{{ __('hr_recruitment.applicant_ui.conversions.confirm') }}</x-ui.button>
                <p class="text-xs {{ $muted }}">{{ __('hr_recruitment.applicant_ui.conversions.confirm_hint') }}</p>
            </div>
        </form>

        <flux:modal wire:model="showConfirm" class="max-w-lg">
            <div class="space-y-4">
                <flux:heading size="lg">{{ __('hr_recruitment.applicant_ui.conversions.confirm_title', ['name' => (string) $applicant?->full_name]) }}</flux:heading>
                <p class="text-sm">{{ $confirmText }}</p>
                <div class="flex justify-end gap-2">
                    <x-ui.button tone="ghost" wire:click="$set('showConfirm', false)">{{ __('hr_recruitment.applicant_ui.cancel') }}</x-ui.button>
                    <x-ui.button tone="primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">{{ __('hr_recruitment.applicant_ui.conversions.confirm') }}</x-ui.button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
