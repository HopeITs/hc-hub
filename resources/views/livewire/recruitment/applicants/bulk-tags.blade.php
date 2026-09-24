{{-- Opened from the table's bulk action. Every id is re-scoped before anything is written. --}}
<div>
    @if ($result)
        <p role="status" class="rounded-control border border-green-200 bg-green-50 p-4 text-green-800">
            {{ $result }}
            <button type="button" wire:click="cancel" class="ms-2 cursor-pointer text-sm font-semibold underline">{{ __('recruitment-applicant.tags.dismiss') }}</button>
        </p>
    @endif

    @if ($open)
        <section class="rounded-surface border border-primary/30 bg-primary/5 p-6">
            <h2 class="text-h4 font-semibold">{{ __('recruitment-applicant.tags.bulk_title') }}</h2>
            <p class="mt-1 text-sm text-secondary-medium">
                {{ trans_choice('recruitment-applicant.tags.bulk_selected', count($applicationIds), ['count' => count($applicationIds)]) }}
                &middot; {{ __('recruitment-applicant.tags.bulk_hint', ['max' => $limit]) }}
            </p>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <fieldset>
                    <legend class="text-sm font-medium">{{ __('recruitment-applicant.tags.bulk_mode') }}</legend>
                    <div class="mt-2 flex gap-4 text-sm">
                        <label class="flex items-center gap-2">
                            <input type="radio" wire:model="mode" value="{{ \App\Livewire\Recruitment\Applicants\BulkTags::ATTACH }}" class="border-neutral-300">
                            {{ __('recruitment-applicant.tags.bulk_attach') }}
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" wire:model="mode" value="{{ \App\Livewire\Recruitment\Applicants\BulkTags::DETACH }}" class="border-neutral-300">
                            {{ __('recruitment-applicant.tags.bulk_detach') }}
                        </label>
                    </div>
                    @error('mode') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </fieldset>

                <div>
                    <x-forms.input-label for="bulkTagId" required>{{ __('recruitment-applicant.tags.tag') }}</x-forms.input-label>
                    <select id="bulkTagId" wire:model="tagId" class="mt-1 w-full rounded-control border-neutral-300">
                        <option value="">—</option>
                        @foreach ($tags as $tag)
                            <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                        @endforeach
                    </select>
                    @error('tagId') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
            </div>
            @error('applicationIds') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror

            <div class="mt-4 flex flex-wrap gap-3">
                <x-primary-button type="button" wire:click="apply" wire:loading.attr="disabled" wire:target="apply">
                    {{ __('recruitment-applicant.tags.bulk_apply') }}
                </x-primary-button>
                <button type="button" wire:click="cancel" class="cursor-pointer rounded-control border border-neutral-300 px-4 py-2 text-sm font-semibold hover:bg-neutral-50">
                    {{ __('recruitment-applicant.cancel') }}
                </button>
            </div>
        </section>
    @endif
</div>
