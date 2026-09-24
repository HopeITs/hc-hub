<div>
    @if ($mayManage)
        <details class="rounded-surface border border-neutral-200 bg-white p-6">
            <summary class="cursor-pointer text-h4 font-semibold">{{ __('recruitment-applicant.tags.manage_title') }}</summary>
            <p class="mt-1 text-sm text-secondary-medium">{{ __('recruitment-applicant.tags.manage_hint') }}</p>

            <form wire:submit="create" class="mt-4 flex flex-wrap items-end gap-3">
                <div>
                    <x-forms.input-label for="tagName" required>{{ __('recruitment-applicant.tags.new_name') }}</x-forms.input-label>
                    <input id="tagName" type="text" wire:model="tagName" maxlength="{{ config('recruitment.tags.max_length') }}" class="mt-1 rounded-control border-neutral-300">
                </div>
                <div>
                    <x-forms.input-label for="tagColor">{{ __('recruitment-applicant.tags.color') }}</x-forms.input-label>
                    <select id="tagColor" wire:model="tagColor" class="mt-1 rounded-control border-neutral-300">
                        @foreach ($palette as $color)
                            <option value="{{ $color }}">{{ __('recruitment-applicant.tags.colors.'.$color) }}</option>
                        @endforeach
                    </select>
                </div>
                <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="create">{{ __('recruitment-applicant.tags.create') }}</x-primary-button>
            </form>
            @error('tagName') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            @error('tagColor') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror

            <ul class="mt-6 divide-y divide-neutral-100 text-sm">
                @forelse ($tags as $tag)
                    <li wire:key="tag-{{ $tag->id }}" class="flex flex-wrap items-center justify-between gap-3 py-3">
                        @if ($renamingId === $tag->id)
                            <form wire:submit="rename" class="flex flex-wrap items-end gap-2">
                                <label for="renameName" class="sr-only">{{ __('recruitment-applicant.tags.new_name') }}</label>
                                <input id="renameName" type="text" wire:model="renameName" maxlength="{{ config('recruitment.tags.max_length') }}" class="rounded-control border-neutral-300 text-sm">
                                <label for="renameColor" class="sr-only">{{ __('recruitment-applicant.tags.color') }}</label>
                                <select id="renameColor" wire:model="renameColor" class="rounded-control border-neutral-300 text-sm">
                                    @foreach ($palette as $color)
                                        <option value="{{ $color }}">{{ __('recruitment-applicant.tags.colors.'.$color) }}</option>
                                    @endforeach
                                </select>
                                <x-primary-button type="submit">{{ __('recruitment-applicant.tags.save') }}</x-primary-button>
                                <button type="button" wire:click="cancelRename" class="cursor-pointer rounded-control border border-neutral-300 px-3 py-2 font-semibold hover:bg-neutral-50">{{ __('recruitment-applicant.cancel') }}</button>
                                @error('renameName') <p class="w-full text-red-700">{{ $message }}</p> @enderror
                            </form>
                        @else
                            <div class="flex items-center gap-2">
                                <span class="rounded-control px-2 py-0.5 text-xs font-semibold {{ $tag->classes() }} {{ $tag->trashed() ? 'line-through opacity-60' : '' }}">{{ $tag->name }}</span>
                                <span class="text-secondary-medium">{{ trans_choice('recruitment-applicant.tags.usage', $tag->visible_count, ['count' => $tag->visible_count]) }}</span>
                                @if ($tag->trashed())
                                    <span class="text-xs text-secondary-medium">&middot; {{ __('recruitment-applicant.tags.deleted') }}</span>
                                @endif
                            </div>
                            <div class="flex gap-3">
                                @if ($tag->trashed())
                                    <button type="button" wire:click="restore({{ $tag->id }})" class="cursor-pointer font-semibold text-primary hover:underline">{{ __('recruitment-applicant.tags.restore') }}</button>
                                @else
                                    <button type="button" wire:click="beginRename({{ $tag->id }})" class="cursor-pointer font-semibold text-primary hover:underline">{{ __('recruitment-applicant.tags.rename') }}</button>
                                    <button type="button" wire:click="delete({{ $tag->id }})" wire:confirm="{{ __('recruitment-applicant.tags.delete_confirm') }}"
                                        class="cursor-pointer font-semibold text-red-700 hover:underline">{{ __('recruitment-applicant.tags.delete') }}</button>
                                @endif
                            </div>
                        @endif
                    </li>
                @empty
                    <li class="py-3 text-secondary-medium">{{ __('recruitment-applicant.tags.none_defined') }}</li>
                @endforelse
            </ul>
        </details>
    @endif
</div>
