{{-- Internal only. Nothing in this panel can change an application's status. --}}
<section class="rounded-surface border border-neutral-200 bg-white p-6">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h2 class="text-h4 font-semibold">{{ __('recruitment-applicant.notes.title') }}</h2>
        <p class="text-sm text-secondary-medium">{{ __('recruitment-applicant.notes.internal_hint') }}</p>
    </div>

    @if ($mayAdd && ($applications->isNotEmpty() || $mayAddUnattached))
        <form wire:submit="addNote" class="mt-4 space-y-3">
            <div>
                <x-forms.input-label for="noteApplication" :required="! $mayAddUnattached">{{ __('recruitment-applicant.notes.about') }}</x-forms.input-label>
                <select id="noteApplication" wire:model="applicationId" class="mt-1 w-full rounded-control border-neutral-300">
                    @if ($mayAddUnattached)
                        <option value="">{{ __('recruitment-applicant.notes.about_applicant') }}</option>
                    @else
                        <option value="">—</option>
                    @endif
                    @foreach ($applications as $application)
                        <option value="{{ $application->id }}">
                            {{ $application->campaign?->vacancy?->position?->title ?? __('recruitment-applicant.no_vacancy') }}
                            &middot; {{ $application->submitted_at?->format('Y-m-d') }}
                        </option>
                    @endforeach
                </select>
                @error('applicationId') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <x-forms.input-label for="noteBody" required>{{ __('recruitment-applicant.notes.body') }}</x-forms.input-label>
                <textarea id="noteBody" wire:model="body" rows="3" maxlength="{{ \App\Services\RecruitmentApplicantNoteService::MAX_BODY }}"
                    class="mt-1 w-full rounded-control border-neutral-300"></textarea>
                @error('body') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="sensitive" class="rounded border-neutral-300">
                {{ __('recruitment-applicant.notes.sensitive') }}
            </label>
            <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="addNote">
                {{ __('recruitment-applicant.notes.add') }}
            </x-primary-button>
        </form>
    @endif

    <ul class="mt-6 space-y-4">
        @forelse ($notes as $note)
            <li wire:key="note-{{ $note->id }}" class="rounded-control border border-neutral-200 p-4">
                <div class="flex flex-wrap items-start justify-between gap-2 text-sm">
                    <p class="text-secondary-medium">
                        <span class="font-semibold text-secondary-dark">{{ $note->actor?->profile?->name ?? $note->actor?->name ?? '—' }}</span>
                        &middot; {{ $note->created_at?->format('Y-m-d H:i') }}
                        &middot; {{ $note->application?->campaign?->vacancy?->position?->title ?? __('recruitment-applicant.notes.about_applicant') }}
                    </p>
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($note->note_type === \App\Models\RecruitmentApplicantNote::TYPE_CV_ARCHIVE)
                            <span class="rounded-control bg-neutral-100 px-2 py-0.5 text-xs font-semibold text-secondary-medium">{{ __('recruitment-applicant.notes.type_cv_archive') }}</span>
                        @endif
                        @if ($note->is_sensitive)
                            <span class="rounded-control bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-800">{{ __('recruitment-applicant.notes.sensitive_badge') }}</span>
                        @endif
                        @can('delete', $note)
                            <button type="button" wire:click="confirmDelete({{ $note->id }})" class="cursor-pointer text-xs font-semibold text-red-700 hover:underline">
                                {{ __('recruitment-applicant.notes.delete') }}
                            </button>
                        @endcan
                    </div>
                </div>
                <p class="mt-2 whitespace-pre-line">{{ $note->body }}</p>

                @if ($deletingNote?->id === $note->id)
                    <div class="mt-3 rounded-control border border-red-200 bg-red-50 p-3">
                        <p class="text-sm text-red-800">{{ __('recruitment-applicant.notes.delete_hint') }}</p>
                        <x-forms.input-label for="deletionReason" class="mt-2" :required="$note->is_sensitive">{{ __('recruitment-applicant.notes.deletion_reason') }}</x-forms.input-label>
                        <textarea id="deletionReason" wire:model="deletionReason" rows="2" class="mt-1 w-full rounded-control border-neutral-300"></textarea>
                        @error('deletionReason') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                        <div class="mt-3 flex flex-wrap gap-3">
                            <button type="button" wire:click="deleteNote" wire:loading.attr="disabled" wire:target="deleteNote"
                                class="cursor-pointer rounded-control bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800">
                                {{ __('recruitment-applicant.notes.delete_confirm') }}
                            </button>
                            <button type="button" wire:click="cancelDelete" class="cursor-pointer rounded-control border border-neutral-300 px-4 py-2 text-sm font-semibold hover:bg-neutral-50">
                                {{ __('recruitment-applicant.cancel') }}
                            </button>
                        </div>
                    </div>
                @endif
            </li>
        @empty
            <li class="text-sm text-secondary-medium">{{ __('recruitment-applicant.notes.empty') }}</li>
        @endforelse
    </ul>
</section>
