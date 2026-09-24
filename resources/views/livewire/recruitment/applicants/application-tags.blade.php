{{-- Internal labels. Nothing here can change an application's status. --}}
<div class="mt-4">
    <h4 class="text-sm font-semibold">{{ __('recruitment-applicant.tags.title') }}</h4>

    <ul class="mt-2 flex flex-wrap gap-2">
        @forelse ($assignments as $assignment)
            <li wire:key="assignment-{{ $assignment->id }}"
                title="{{ __('recruitment-applicant.tags.applied_by', [
                    'name' => $assignment->creator?->profile?->name ?? $assignment->creator?->name ?? '—',
                    'date' => $assignment->created_at?->format('Y-m-d H:i') ?? '—',
                ]) }}"
                class="inline-flex items-center gap-1 rounded-control px-2 py-0.5 text-xs font-semibold {{ $assignment->tag->classes() }}">
                {{ $assignment->tag->name }}
                <span class="font-normal opacity-75">&middot; {{ $assignment->creator?->profile?->name ?? $assignment->creator?->name ?? '—' }}, {{ $assignment->created_at?->format('Y-m-d') }}</span>
                @if ($mayTag)
                    <button type="button" wire:click="detach({{ $assignment->tag_id }})" wire:loading.attr="disabled"
                        class="ms-1 cursor-pointer font-bold hover:opacity-70" aria-label="{{ __('recruitment-applicant.tags.remove', ['name' => $assignment->tag->name]) }}">&times;</button>
                @endif
            </li>
        @empty
            <li class="text-sm text-secondary-medium">{{ __('recruitment-applicant.tags.none') }}</li>
        @endforelse
    </ul>

    @if ($mayTag)
        <div class="mt-3 flex flex-wrap items-end gap-2">
            @if ($available->isNotEmpty())
                <div>
                    <label for="tag-{{ $applicationId }}" class="sr-only">{{ __('recruitment-applicant.tags.tag') }}</label>
                    <select id="tag-{{ $applicationId }}" wire:model="tagId" class="rounded-control border-neutral-300 text-sm">
                        <option value="">{{ __('recruitment-applicant.tags.choose') }}</option>
                        @foreach ($available as $tag)
                            <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" wire:click="attach" wire:loading.attr="disabled" wire:target="attach"
                    class="cursor-pointer rounded-control border border-neutral-300 px-3 py-2 text-sm font-semibold hover:bg-neutral-50">
                    {{ __('recruitment-applicant.tags.add') }}
                </button>
            @endif

            @if ($mayCreate)
                <form wire:submit="createAndAttach" class="flex flex-wrap items-end gap-2">
                    <label for="new-tag-{{ $applicationId }}" class="sr-only">{{ __('recruitment-applicant.tags.new_name') }}</label>
                    <input id="new-tag-{{ $applicationId }}" type="text" wire:model="newTagName" maxlength="{{ config('recruitment.tags.max_length') }}"
                        placeholder="{{ __('recruitment-applicant.tags.new_name') }}" class="rounded-control border-neutral-300 text-sm">
                    <label for="new-tag-color-{{ $applicationId }}" class="sr-only">{{ __('recruitment-applicant.tags.color') }}</label>
                    <select id="new-tag-color-{{ $applicationId }}" wire:model="newTagColor" class="rounded-control border-neutral-300 text-sm">
                        @foreach ($palette as $color)
                            <option value="{{ $color }}">{{ __('recruitment-applicant.tags.colors.'.$color) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" wire:loading.attr="disabled" wire:target="createAndAttach"
                        class="cursor-pointer rounded-control border border-neutral-300 px-3 py-2 text-sm font-semibold hover:bg-neutral-50">
                        {{ __('recruitment-applicant.tags.create_and_add') }}
                    </button>
                </form>
            @endif
        </div>
        @error('tagId') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        @error('tagName') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        @error('tagColor') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    @endif
</div>
