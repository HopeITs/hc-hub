<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Hr\Actions\Recruitment\HiringCampaignStateMachine;
use Modules\Hr\Actions\Recruitment\RecruitmentJobOfferService;
use Modules\Hr\Enums\HiringCampaignStatus;
use Modules\Hr\Enums\RecruitmentJobOfferStatus as Status;
use Modules\Hr\Livewire\Concerns\Recruitment\AuthorizesRecruitment;
use Modules\Hr\Livewire\Concerns\Recruitment\ResolvesJobOffers;
use Modules\Hr\Models\HiringCampaign;
use Modules\Hr\Models\RecruitmentHiringConversion;
use Modules\Hr\Models\RecruitmentJobOffer;
use Modules\Hr\Models\Vacancy;
use Modules\Hr\Support\Recruitment\RecruitmentConfig;

/*
 * One job offer: the current version's terms, every version as it was
 * offered, and what happened to it. The salary is rendered only for those
 * who may see pay figures (viewSalary) and never kept on the component, so
 * it never reaches the browser in a Livewire payload.
 *
 * HR with manage-offers sends the draft, records the candidate's answer
 * (with the response email or document as evidence), cancels, and starts a
 * revision or edits the draft. Each action locks the version the user was
 * looking at; the service refuses if the offer moved on since.
 */
new #[Layout('layouts::desk'), Title('hr_recruitment.hub.titles.offer')] class extends Component {
    use AuthorizesRecruitment;
    use ResolvesJobOffers;
    use WithFileUploads;

    #[Locked]
    public int $offerId;

    /** 'send' | 'response' | 'cancel' | '' */
    #[Locked]
    public string $panel = '';

    #[Locked]
    public ?int $expectedVersionId = null;

    public bool $panelOpen = false;

    public string $expiresOn = '';

    /** The expiry date the send dialog opened with: what its discard guard compares against. */
    #[Locked]
    public string $suggestedExpiresOn = '';

    public string $response = '';

    /** Internal: the candidate's reasons, as HR recorded them. */
    public string $responseNote = '';

    public mixed $responseEvidence = null;

    public string $cancelReason = '';

    public string $cancelNote = '';

    public function mount(int $offerId): void
    {
        $this->offerId = $offerId;
        $this->resolveOffer($offerId);

        if ($message = session()->pull('recruitment_toast')) {
            $this->toast((string) $message);
        }
    }

    #[Computed]
    public function offer(): RecruitmentJobOffer
    {
        return $this->resolveOffer($this->offerId)->load([
            'application:id,applicant_id,campaign_id,status',
            'application.applicant:id,full_name',
            'position:id,title_en,title_ar',
            'vacancy:id,status,needed_count,filled_count',
            'currentVersion.contractType:id,name_en,name_ar',
            'versions' => fn ($versions) => $versions->orderByDesc('version_no'),
            'versions.contractType:id,name_en,name_ar',
            'versions.creator:id,email,employee_id',
            'creator:id,email,employee_id',
            'responseRecordedBy:id,email,employee_id',
            'cancelledBy:id,email,employee_id',
            'hiringConversion.employee',
            'hiringConversion.convertedBy:id,email,employee_id',
        ]);
    }

    /** @return array<string, mixed> what the page shows, read once per render */
    #[Computed]
    public function state(): array
    {
        $offer = $this->offer;
        $offers = app(RecruitmentJobOfferService::class);
        $conversion = $offer->hiringConversion;
        $canManage = Gate::allows('manage', $offer);

        return [
            'conversion' => $conversion,
            'canConvert' => $conversion !== null && ! $conversion->isConverted()
                && $this->may('recruitment-applicants.convert-hires') && Gate::allows('convert', $conversion),
            'campaignsStillOpen' => $conversion?->isConverted() && $offer->vacancy?->status === Vacancy::FILLED
                ? $this->campaignsStillOpen($offer->vacancy)
                : [],
            'canManage' => $canManage,
            'canSeeApplicant' => Gate::allows('viewApplicant', $offer),
            'seatsLeft' => $offer->vacancy ? $offers->seatsLeft($offer->vacancy, $offer) : 0,
            'canRevise' => $canManage && $offers->canRevise($offer),
            'cancellationReasons' => $offers->cancellationReasons(),
            'evidence' => $canManage ? $offer->getMedia(RecruitmentJobOffer::RESPONSE_EVIDENCE) : collect(),
        ];
    }

    public function beginSend(): void
    {
        $this->openPanel('send');
        $this->expiresOn = today()->addDays((int) RecruitmentConfig::get('recruitment.offers.validity_days', 7))->toDateString();
        $this->suggestedExpiresOn = $this->expiresOn;
    }

    public function send(RecruitmentJobOfferService $offers): void
    {
        abort_unless($this->panel === 'send', 404);
        $offer = $this->authorizedOffer();

        $this->run(fn () => $offers->send($this->viewer(), $offer, (int) $this->expectedVersionId, $this->expiresOn), 'sent');
    }

    public function beginResponse(string $response): void
    {
        abort_unless(in_array($response, [Status::Accepted, Status::Rejected], true), 403);
        $this->openPanel('response');
        $this->response = $response;
    }

    public function recordResponse(RecruitmentJobOfferService $offers): void
    {
        abort_unless($this->panel === 'response', 404);
        $offer = $this->authorizedOffer();

        if ($this->responseEvidence !== null) {
            $this->validateResponseEvidence();
        }

        $this->run(function () use ($offers, $offer): void {
            $recorded = $offers->recordResponse($this->viewer(), $offer, (int) $this->expectedVersionId, $this->response, $this->responseNote);

            if ($this->responseEvidence !== null) {
                $this->storeResponseEvidence($recorded);
            }
        }, 'response_recorded');
    }

    public function uploadResponseEvidence(): void
    {
        $offer = $this->authorizedOffer();
        abort_unless(in_array($offer->status, [Status::Accepted, Status::Rejected], true), 404);

        $this->validateResponseEvidence();
        $this->storeResponseEvidence($offer);
        $this->reset('responseEvidence');
        unset($this->state);
        $this->toast(__('hr_recruitment.applicant_ui.offers.response_evidence.uploaded'));
    }

    public function discardResponseEvidence(int $mediaId): void
    {
        $offer = $this->authorizedOffer();
        $media = $offer->getMedia(RecruitmentJobOffer::RESPONSE_EVIDENCE)->firstWhere('id', $mediaId);
        abort_if($media === null, 404);

        $media->deleteWithFile();
        unset($this->state);
        $this->toast(__('hr_recruitment.applicant_ui.offers.response_evidence.removed'));
    }

    public function beginCancel(): void
    {
        $this->openPanel('cancel');
    }

    public function cancelOffer(RecruitmentJobOfferService $offers): void
    {
        abort_unless($this->panel === 'cancel', 404);
        $offer = $this->authorizedOffer();

        $this->run(fn () => $offers->cancel($this->viewer(), $offer, (int) $this->expectedVersionId, $this->cancelReason, $this->cancelNote), 'cancelled');
    }

    public function closePanel(): void
    {
        $this->reset('panel', 'panelOpen', 'expectedVersionId', 'expiresOn', 'suggestedExpiresOn', 'response', 'responseNote', 'responseEvidence', 'cancelReason', 'cancelNote');
        $this->resetErrorBag();
    }

    /**
     * Campaigns on a filled vacancy that still take — or may yet take —
     * applications, which the hire did not close for HR.
     *
     * @return list<int>
     */
    private function campaignsStillOpen(Vacancy $vacancy): array
    {
        $states = app(HiringCampaignStateMachine::class);
        $live = [HiringCampaignStatus::Open->value, HiringCampaignStatus::Published->value];

        return HiringCampaign::query()
            ->where('vacancy_id', $vacancy->getKey())
            ->whereIn('status', $live)
            ->get(['id', 'status', 'starts_at', 'ends_at'])
            ->filter(fn (HiringCampaign $campaign): bool => in_array($states->effective($campaign), $live, true))
            ->map(fn (HiringCampaign $campaign): int => (int) $campaign->getKey())
            ->values()
            ->all();
    }

    private function openPanel(string $panel): void
    {
        $offer = $this->authorizedOffer();

        $this->closePanel();
        $this->panel = $panel;
        $this->panelOpen = true;
        $this->expectedVersionId = (int) $offer->current_version_id;
    }

    private function run(callable $action, string $messageKey): void
    {
        $this->resetErrorBag();

        try {
            $action();
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }

            return;
        }

        $this->closePanel();
        unset($this->offer, $this->state);
        $this->toast(__('hr_recruitment.applicant_ui.offers.'.$messageKey));
    }

    private function authorizedOffer(): RecruitmentJobOffer
    {
        $this->requirePermission('recruitment-applicants.manage-offers');
        $offer = $this->resolveOffer($this->offerId);
        Gate::authorize('manage', $offer);

        return $offer;
    }

    private function validateResponseEvidence(): void
    {
        $this->validate([
            'responseEvidence' => [
                'required', 'file', 'max:12288',
                'extensions:eml,msg,pdf,jpg,jpeg,png,txt',
                'mimetypes:message/rfc822,application/vnd.ms-outlook,application/pdf,image/jpeg,image/png,text/plain,application/octet-stream',
            ],
        ], [
            'responseEvidence.required' => __('hr_recruitment.applicant_ui.offers.response_evidence.required'),
            'responseEvidence.file' => __('hr_recruitment.applicant_ui.offers.response_evidence.invalid'),
            'responseEvidence.max' => __('hr_recruitment.applicant_ui.offers.response_evidence.too_large'),
            'responseEvidence.extensions' => __('hr_recruitment.applicant_ui.offers.response_evidence.invalid'),
            'responseEvidence.mimetypes' => __('hr_recruitment.applicant_ui.offers.response_evidence.invalid'),
        ]);
    }

    private function storeResponseEvidence(RecruitmentJobOffer $offer): void
    {
        $originalName = basename($this->responseEvidence->getClientOriginalName());
        $extension = strtolower((string) $this->responseEvidence->getClientOriginalExtension());

        $offer
            ->addMedia($this->responseEvidence->getRealPath())
            ->usingName(Str::limit(pathinfo($originalName, PATHINFO_FILENAME), 120, ''))
            ->usingFileName(Str::uuid().($extension !== '' ? '.'.$extension : ''))
            ->withCustomProperties([
                'original_name' => $originalName,
                'uploaded_by_user_id' => $this->viewer()->getKey(),
            ])
            ->toMediaCollection(RecruitmentJobOffer::RESPONSE_EVIDENCE);
    }
};
?>

@php
    $offer = $this->offer;
    $state = $this->state;
    $current = $offer->currentVersion;
    $application = $offer->application;
    $conversion = $state['conversion'];
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $note = 'rounded-md bg-surface-2 p-4 text-sm dark:bg-surface-2';
    $date = fn ($value) => $value?->translatedFormat(__('formats.date'));
    $dateTime = fn ($value) => $value?->translatedFormat(__('formats.date').' H:i');
    $name = fn ($user) => $user?->name ?? '—';
    $title = $application?->applicant?->full_name ?? __('hr_recruitment.applicant_ui.offers.document_title');
@endphp

<div class="space-y-6" data-offer="{{ $offer->id }}">
    <x-ui.detail-header
        :back="\App\Support\BackUrl::for(route('hr.recruitment.offers.index'))"
        :breadcrumbs="[['label' => __('hr_recruitment.hub.section')], ['label' => __('hr_recruitment.hub.titles.offers'), 'href' => route('hr.recruitment.offers.index')]]"
        :title="$title"
    >
        <x-slot:meta>
            <x-ui.enum-badge :case="$offer->status" :label="__('hr_recruitment.applicant_ui.offers.statuses.'.$offer->status)" :tone="\Modules\Hr\Support\Recruitment\Labels::tone($offer->status)" />
            <span>{{ __('hr_recruitment.applicant_ui.offers.document_title') }} · <span class="tabular-nums">v{{ $current?->version_no }}</span></span>
            <span dir="auto">{{ $offer->position?->title }}</span>
            @if ($state['canSeeApplicant'] && Route::has('hr.recruitment.applicants.show'))
                <a href="{{ route('hr.recruitment.applicants.show', ['applicantId' => $application->applicant_id]) }}" wire:navigate class="underline-offset-4 hover:underline">{{ __('hr_recruitment.applicant_ui.interviews.open_applicant') }}</a>
            @endif
        </x-slot:meta>
        @if ($state['canManage'])
            <x-slot:actions>
                @if ($offer->isEditable())
                    <x-ui.button tone="secondary" icon="pencil-square" :href="route('hr.recruitment.offers.edit', ['offerId' => $offer->id])" wire:navigate>{{ __('hr_recruitment.applicant_ui.offers.actions.edit') }}</x-ui.button>
                    <x-ui.button tone="primary" icon="paper-airplane" wire:click="beginSend">{{ __('hr_recruitment.applicant_ui.offers.actions.send') }}</x-ui.button>
                @endif
                @if ($offer->status === Status::Sent)
                    <x-ui.button tone="primary" icon="check" wire:click="beginResponse('{{ Status::Accepted }}')">{{ __('hr_recruitment.applicant_ui.offers.actions.accepted') }}</x-ui.button>
                    <x-ui.button tone="secondary" icon="x-mark" wire:click="beginResponse('{{ Status::Rejected }}')">{{ __('hr_recruitment.applicant_ui.offers.actions.rejected') }}</x-ui.button>
                @endif
                @if ($state['canRevise'])
                    <x-ui.button tone="secondary" icon="arrow-path" :href="route('hr.recruitment.offers.revise', ['offerId' => $offer->id])" wire:navigate>{{ __('hr_recruitment.applicant_ui.offers.actions.revise') }}</x-ui.button>
                @endif
                @if (Status::canMove($offer->status, Status::Cancelled))
                    <x-ui.button tone="ghost" wire:click="beginCancel">{{ __('hr_recruitment.applicant_ui.offers.actions.cancel') }}</x-ui.button>
                @endif
            </x-slot:actions>
        @endif
    </x-ui.detail-header>

    @error('offer')
        <p role="alert" class="rounded-md border border-primary bg-primary/5 p-3 text-sm text-primary">{{ $message }}</p>
    @enderror

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="min-w-0 space-y-6">
            {{-- The offer document: the current version's terms. --}}
            <x-ui.panel :title="__('hr_recruitment.applicant_ui.offers.document_title').' · v'.$current?->version_no">
                <div class="space-y-4 p-4">
                    @if ($current)
                        <x-hr::recruitment.offer-terms :version="$current" :can-see-salary="Gate::allows('viewSalary', [$offer, $current])" />
                    @endif

                    {{-- How it ended. --}}
                    @if (in_array($offer->status, [Status::Accepted, Status::Rejected], true))
                        <div class="{{ $note }} space-y-3">
                            <p>{{ __('hr_recruitment.applicant_ui.offers.response_on', ['response' => __('hr_recruitment.applicant_ui.offers.statuses.'.$offer->status), 'date' => $dateTime($offer->accepted_at ?? $offer->rejected_at), 'by' => $name($offer->responseRecordedBy)]) }}</p>
                            @if ($state['canManage'] && filled($offer->response_note))
                                <p class="whitespace-pre-line {{ $muted }}" dir="auto">{{ $offer->response_note }}</p>
                            @endif
                        </div>
                    @elseif ($offer->status === Status::Cancelled)
                        @php $reasonKey = 'hr_recruitment.applicant_ui.offers.cancellation_reasons.'.$offer->cancellation_reason; @endphp
                        <div class="{{ $note }}">
                            {{ __('hr_recruitment.applicant_ui.offers.cancelled_on', ['date' => $dateTime($offer->cancelled_at), 'by' => $offer->cancelledBy ? $name($offer->cancelledBy) : __('hr_recruitment.applicant_ui.interviews.system')]) }}
                            — {{ Lang::has($reasonKey) ? __($reasonKey) : $offer->cancellation_reason }}
                            @if ($state['canManage'] && filled($offer->cancellation_note))
                                <p class="mt-1 whitespace-pre-line {{ $muted }}" dir="auto">{{ $offer->cancellation_note }}</p>
                            @endif
                        </div>
                    @elseif ($offer->status === Status::Expired)
                        <p class="{{ $note }}">{{ __('hr_recruitment.applicant_ui.offers.expired_on', ['date' => $dateTime($offer->expired_at)]) }}</p>
                    @endif

                    {{-- Rule reminder: an offer never touches the vacancy by itself. --}}
                    @if ($offer->vacancy)
                        <p class="text-xs {{ $muted }}">
                            {{ trans_choice('hr_recruitment.applicant_ui.offers.seats_left', $state['seatsLeft'], ['count' => $state['seatsLeft']]) }}
                            · {{ __('hr_recruitment.applicant_ui.offers.vacancy_untouched') }}
                        </p>
                    @endif
                </div>
            </x-ui.panel>

            {{-- The hire: once the candidate accepted. --}}
            @if ($offer->status === Status::Accepted && $conversion)
                <x-ui.panel :title="__('hr_recruitment.applicant_ui.conversions.title')">
                    <div class="space-y-3 p-4 text-sm">
                        <p class="font-semibold">{{ __('hr_recruitment.applicant_ui.conversions.statuses.'.$conversion->status) }}</p>
                        @if ($conversion->isConverted())
                            <p>{{ __('hr_recruitment.applicant_ui.conversions.converted_by', ['name' => $name($conversion->convertedBy), 'date' => $dateTime($conversion->converted_at)]) }}</p>
                            <div class="flex flex-wrap gap-2">
                                @if ($conversion->employee?->username)
                                    <x-ui.button size="sm" tone="secondary" icon="user" :href="route('hr.employees.show', ['employee' => $conversion->employee->username])" wire:navigate>{{ __('hr_recruitment.applicant_ui.conversions.open_employee') }}: {{ $conversion->employee->name }}</x-ui.button>
                                @endif
                                @if ($conversion->contract_id)
                                    <x-ui.button size="sm" tone="secondary" icon="document-text" :href="route('hr.contracts.show', ['contract' => $conversion->contract_id])" wire:navigate>{{ __('hr_recruitment.applicant_ui.conversions.open_contract') }} #{{ $conversion->contract_id }}</x-ui.button>
                                @endif
                            </div>
                            @if ($state['campaignsStillOpen'] !== [])
                                <p class="{{ $muted }}">{{ __('hr_recruitment.applicant_ui.conversions.campaigns_still_open', ['ids' => implode(', ', array_map(fn ($id) => '#'.$id, $state['campaignsStillOpen']))]) }}</p>
                            @endif
                        @else
                            @if ($conversion->status === RecruitmentHiringConversion::STATUS_FAILED && $conversion->failure_reason)
                                <p>{{ __('hr_recruitment.applicant_ui.conversions.last_failure', ['count' => $conversion->attempt_count, 'reason' => __('hr_recruitment.applicant_ui.conversions.errors.'.$conversion->failure_reason)]) }}</p>
                            @endif
                            @if ($state['canConvert'])
                                <x-ui.button tone="primary" icon="user-plus" :href="route('hr.recruitment.offers.convert', ['offerId' => $offer->id])" wire:navigate>{{ __('hr_recruitment.applicant_ui.conversions.open') }}</x-ui.button>
                            @endif
                        @endif
                    </div>
                </x-ui.panel>
            @endif

            {{-- History: every version exactly as it was offered. --}}
            <x-ui.panel :title="__('hr_recruitment.applicant_ui.offers.history')" :count="$offer->versions->count()">
                <ol class="divide-y divide-panel-border">
                    @foreach ($offer->versions as $version)
                        <li class="space-y-2 px-4 py-3 text-sm" wire:key="version-{{ $version->id }}">
                            <p class="font-semibold">
                                <span class="tabular-nums">v{{ $version->version_no }}</span> · {{ __('hr_recruitment.applicant_ui.offers.version_statuses.'.$version->status) }}
                                <span class="font-normal {{ $muted }}">
                                    · {{ $name($version->creator) }} · <span class="tabular-nums">{{ $dateTime($version->created_at) }}</span>
                                    @if ($version->sent_at) · {{ __('hr_recruitment.applicant_ui.offers.sent_at') }} <span class="tabular-nums">{{ $dateTime($version->sent_at) }}</span>@endif
                                </span>
                            </p>
                            @if ($version->revision_reason && $state['canManage'])
                                <p class="{{ $muted }}" dir="auto">{{ __('hr_recruitment.applicant_ui.offers.revision_reason') }}: {{ $version->revision_reason }}</p>
                            @endif
                            <x-hr::recruitment.offer-terms :version="$version" compact :can-see-salary="Gate::allows('viewSalary', [$offer, $version])" />
                        </li>
                    @endforeach
                </ol>
            </x-ui.panel>
        </div>

        <aside class="space-y-4 xl:sticky xl:top-24">
            <x-ui.panel :title="__('hr_recruitment.hub.details')">
                <dl class="space-y-3 p-4 text-sm">
                    <div><dt class="{{ $muted }}">{{ __('hr_recruitment.applicant_ui.offers.created_by') }}</dt>
                        <dd class="font-medium" dir="auto">{{ $name($offer->creator) }} · <span class="tabular-nums">{{ $date($offer->created_at) }}</span></dd></div>
                    @if ($offer->sent_at)
                        <div><dt class="{{ $muted }}">{{ __('hr_recruitment.applicant_ui.offers.sent_at') }}</dt>
                            <dd class="font-medium tabular-nums">{{ $dateTime($offer->sent_at) }}</dd></div>
                    @endif
                    @if ($offer->expires_at && in_array($offer->status, [Status::Sent, Status::Expired], true))
                        <div><dt class="{{ $muted }}">{{ __('hr_recruitment.applicant_ui.offers.expires_at') }}</dt>
                            <dd class="font-medium tabular-nums">{{ $date($offer->expires_at) }}</dd></div>
                    @endif
                </dl>
            </x-ui.panel>

            {{-- The candidate's answer, as HR received it: private to HR who manage offers. --}}
            @if ($state['canManage'] && in_array($offer->status, [Status::Accepted, Status::Rejected], true))
                <x-ui.panel :title="__('hr_recruitment.applicant_ui.offers.response_evidence.title')" :clip="false">
                    <div class="space-y-3 p-4 text-sm">
                        @forelse ($state['evidence'] as $evidence)
                            <div class="flex items-center justify-between gap-2" wire:key="offer-evidence-{{ $evidence->id }}">
                                <a href="{{ route('hr.recruitment.offer-response-evidence.download', ['media' => $evidence->id]) }}" class="min-w-0 truncate font-medium underline-offset-4 hover:underline" dir="auto">
                                    {{ $evidence->getCustomProperty('original_name', $evidence->file_name) }}
                                </a>
                                <x-ui.button size="sm" tone="ghost" icon="trash" class="size-8!" wire:click="discardResponseEvidence({{ $evidence->id }})" wire:confirm="{{ __('hr_recruitment.applicant_ui.offers.response_evidence.remove_confirm') }}" :aria-label="__('hr_recruitment.applicant_ui.offers.response_evidence.remove')" />
                            </div>
                        @empty
                            <p class="{{ $muted }}">{{ __('hr_recruitment.applicant_ui.offers.response_evidence.empty') }}</p>
                        @endforelse

                        @unless ($panelOpen)
                            <x-ui.form-file wire:model="responseEvidence" :label="__('hr_recruitment.applicant_ui.offers.response_evidence.add')" :help="__('hr_recruitment.applicant_ui.offers.response_evidence.hint')" accept=".eml,.msg,.pdf,.jpg,.jpeg,.png,.txt" />
                            <div class="flex justify-end">
                                <x-ui.button size="sm" tone="secondary" icon="arrow-up-tray" wire:click="uploadResponseEvidence" wire:loading.attr="disabled" wire:target="responseEvidence,uploadResponseEvidence">{{ __('hr_recruitment.applicant_ui.offers.response_evidence.upload') }}</x-ui.button>
                            </div>
                        @endunless
                    </div>
                </x-ui.panel>
            @endif
        </aside>
    </div>

    @if ($state['canManage'])
        @php
            $modalTitle = match ($panel) {
                'send' => __('hr_recruitment.applicant_ui.offers.send_title'),
                'response' => __('hr_recruitment.applicant_ui.offers.response_title.'.($response ?: Status::Accepted)),
                'cancel' => __('hr_recruitment.applicant_ui.offers.cancel_title'),
                default => '',
            };
            [$action, $actionLabel] = match ($panel) {
                'send' => ['send', __('hr_recruitment.applicant_ui.offers.actions.send')],
                'response' => ['recordResponse', __('hr_recruitment.applicant_ui.offers.confirm')],
                default => ['cancelOffer', __('hr_recruitment.applicant_ui.offers.actions.cancel')],
            };
        @endphp
        {{-- Send, record the answer, cancel. Closing with something typed asks before discarding it. --}}
        <x-ui.modal
            wire:model="panelOpen"
            size="sm"
            close="closePanel"
            :title="$modalTitle"
            :submit="$action"
            :cancel-label="__('hr_recruitment.applicant_ui.cancel')"
            :discard-guard="$panelOpen ? [
                'fields' => ['expiresOn', 'responseNote', 'responseEvidence', 'cancelReason', 'cancelNote'],
                'original' => ['expiresOn' => $suggestedExpiresOn, 'responseNote' => '', 'responseEvidence' => '', 'cancelReason' => '', 'cancelNote' => ''],
            ] : null"
            data-offer-action-modal
        >
            @if ($panel !== '')
                <div class="space-y-4">
                    @error('offer')
                        <p role="alert" class="rounded-md border border-primary bg-primary/5 p-3 text-sm text-primary">{{ $message }}</p>
                    @enderror

                    @if ($panel === 'send')
                        <p class="text-sm {{ $muted }}">{{ __('hr_recruitment.applicant_ui.offers.send_hint') }}</p>
                        <x-ui.form-input wire:model="expiresOn" :label="__('hr_recruitment.applicant_ui.offers.expires_at')" type="date" required />
                        @error('startDate')
                            <p role="alert" class="text-sm text-primary">{{ $message }}</p>
                        @enderror
                    @elseif ($panel === 'response')
                        @if ($response === Status::Accepted)
                            <p class="text-sm {{ $muted }}">{{ __('hr_recruitment.applicant_ui.offers.accept_hint') }}</p>
                        @endif
                        <x-ui.form-textarea wire:model="responseNote" :label="__('hr_recruitment.applicant_ui.offers.response_note')" rows="2" dir="auto" />
                        <x-ui.form-file wire:model="responseEvidence" :label="__('hr_recruitment.applicant_ui.offers.response_evidence.label')" :help="__('hr_recruitment.applicant_ui.offers.response_evidence.hint')" accept=".eml,.msg,.pdf,.jpg,.jpeg,.png,.txt" />
                        @error('response')
                            <p role="alert" class="text-sm text-primary">{{ $message }}</p>
                        @enderror
                    @else
                        <p class="text-sm {{ $muted }}">{{ __('hr_recruitment.applicant_ui.offers.cancel_hint') }}</p>
                        <x-ui.form-select wire:model.live="cancelReason" :label="__('hr_recruitment.applicant_ui.interviews.cancel_reason')" :placeholder="__('hr_recruitment.choose')" required>
                            @foreach ($state['cancellationReasons'] as $code)
                                <option value="{{ $code }}">{{ __('hr_recruitment.applicant_ui.offers.cancellation_reasons.'.$code) }}</option>
                            @endforeach
                        </x-ui.form-select>
                        <x-ui.form-textarea wire:model="cancelNote" :label="__('hr_recruitment.applicant_ui.interviews.internal_note')" :required="$cancelReason === RecruitmentJobOfferService::REASON_OTHER" rows="2" dir="auto" />
                    @endif
                </div>
            @endif

            <x-slot:actions>
                <x-ui.button type="submit" :tone="$panel === 'cancel' ? 'neutral' : 'primary'" wire:loading.attr="disabled" wire:target="{{ $action }},responseEvidence">{{ $actionLabel }}</x-ui.button>
            </x-slot:actions>
        </x-ui.modal>
    @endif
</div>
