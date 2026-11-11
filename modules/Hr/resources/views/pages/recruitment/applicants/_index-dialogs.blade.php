{{-- The bulk-action dialog and the shared tag list (applicants index). Every id is re-scoped by the service before anything is written. --}}
@php
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $limit = app(\Modules\Hr\Actions\Recruitment\RecruitmentTagService::class)->bulkLimit();
    $kinds = \Modules\Hr\Models\RecruitmentTag::kinds();
    $palette = $this->tagPalette();
@endphp

<flux:modal wire:model="showBulk" class="w-full max-w-lg">
    @if ($bulk !== '')
        <form wire:submit="applyBulk" class="space-y-4">
            <flux:heading size="lg">{{ match ($bulk) {
                'tags' => __('hr_recruitment.applicant_ui.tags.bulk_title'),
                'reject' => __('hr_recruitment.applicant_ui.decisions.bulk_title'),
                default => __('hr_recruitment.applicant_ui.profile_reviews.bulk_title'),
            } }}</flux:heading>
            <p class="text-sm {{ $muted }}">
                {{ trans_choice('hr_recruitment.applicant_ui.tags.bulk_selected', count($selected), ['count' => count($selected)]) }}
                · {{ $bulk === 'reject' ? __('hr_recruitment.applicant_ui.decisions.bulk_hint', ['max' => $limit]) : __('hr_recruitment.applicant_ui.tags.bulk_hint', ['max' => $limit]) }}
            </p>

            @if ($bulk === 'tags')
                <fieldset class="space-y-2">
                    <legend class="text-sm font-semibold">{{ __('hr_recruitment.applicant_ui.tags.bulk_mode') }}</legend>
                    <flux:radio.group wire:model="bulkMode" variant="segmented">
                        <flux:radio value="attach" :label="__('hr_recruitment.applicant_ui.tags.bulk_attach')" />
                        <flux:radio value="detach" :label="__('hr_recruitment.applicant_ui.tags.bulk_detach')" />
                    </flux:radio.group>
                    @error('bulkMode')<p class="text-sm text-primary">{{ $message }}</p>@enderror
                </fieldset>
                <x-ui.form-select wire:model="bulkTagId" :label="__('hr_recruitment.applicant_ui.tags.tag')" :placeholder="__('hr_recruitment.applicant_ui.tags.choose')" required searchable>
                    @foreach ($this->options['tags'] as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </x-ui.form-select>
            @elseif ($bulk === 'reject')
                <x-ui.form-select wire:model.live="bulkReasonCode" :label="__('hr_recruitment.applicant_ui.decisions.reason_code')" :placeholder="__('hr_recruitment.hub.select')" required>
                    @foreach (app(\Modules\Hr\Actions\Recruitment\RecruitmentApplicationStatusService::class)->rejectionReasons() as $code)
                        <option value="{{ $code }}">{{ __('hr_recruitment.applicant_ui.decisions.rejection_reasons.'.$code) }}</option>
                    @endforeach
                </x-ui.form-select>
                <x-ui.form-textarea wire:model="bulkReason" :label="__('hr_recruitment.applicant_ui.decisions.internal_note')" :help="__('hr_recruitment.applicant_ui.decisions.internal_note_hint')" :required="$bulkReasonCode === 'other'" rows="3" dir="auto" />
                <flux:checkbox wire:model="bulkNotify" :label="__('hr_recruitment.applicant_ui.decisions.bulk_notify')" :description="__('hr_recruitment.applicant_ui.decisions.notify_hint')" />
            @else
                <x-ui.form-select wire:model="bulkUserId" :label="__('hr_recruitment.applicant_ui.profile_reviews.person')" :placeholder="__('hr_recruitment.choose')" required searchable>
                    @foreach ($this->activeStaff as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </x-ui.form-select>
                <x-ui.form-select wire:model="bulkRole" :label="__('hr_recruitment.applicant_ui.profile_reviews.access')" required>
                    @foreach (\Modules\Hr\Models\RecruitmentApplicationParticipant::roles() as $role)
                        <option value="{{ $role }}">{{ __('hr_recruitment.applicant_ui.profile_reviews.roles.'.$role) }}</option>
                    @endforeach
                </x-ui.form-select>
            @endif

            @error('selected')<p class="text-sm text-primary">{{ $message }}</p>@enderror

            <div class="flex justify-end gap-2">
                <x-ui.button tone="ghost" wire:click="$set('showBulk', false)">{{ __('hr_recruitment.applicant_ui.cancel') }}</x-ui.button>
                @if ($bulk === 'reject')
                    <x-ui.button type="submit" tone="neutral" wire:loading.attr="disabled" wire:target="applyBulk" wire:confirm="{{ __('hr_recruitment.applicant_ui.decisions.bulk_confirm') }}">{{ __('hr_recruitment.applicant_ui.decisions.bulk_apply') }}</x-ui.button>
                @else
                    <x-ui.button type="submit" tone="primary" wire:loading.attr="disabled" wire:target="applyBulk">{{ $bulk === 'tags' ? __('hr_recruitment.applicant_ui.tags.bulk_apply') : __('hr_recruitment.applicant_ui.profile_reviews.bulk_apply') }}</x-ui.button>
                @endif
            </div>
        </form>
    @endif
</flux:modal>

<flux:modal wire:model="showTags" class="w-full max-w-2xl">
    @if ($showTags)
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('hr_recruitment.applicant_ui.tags.manage_title') }}</flux:heading>
                <p class="text-sm {{ $muted }}">{{ __('hr_recruitment.applicant_ui.tags.manage_hint') }}</p>
            </div>

            <form wire:submit="createTag" class="grid items-end gap-3 sm:grid-cols-[minmax(0,1fr)_10rem_9rem_auto]">
                <x-ui.form-input wire:model="tagName" :label="__('hr_recruitment.applicant_ui.tags.new_name')" maxlength="40" required dir="auto" />
                <x-ui.form-select wire:model="tagKind" :label="__('hr_recruitment.applicant_ui.tags.kind')">
                    @foreach ($kinds as $kind)
                        <option value="{{ $kind }}">{{ __('hr_recruitment.applicant_ui.tags.kinds.'.$kind) }}</option>
                    @endforeach
                </x-ui.form-select>
                <x-ui.form-select wire:model="tagColor" :label="__('hr_recruitment.applicant_ui.tags.color')">
                    @foreach ($palette as $color)
                        <option value="{{ $color }}">{{ __('hr_recruitment.applicant_ui.tags.colors.'.$color) }}</option>
                    @endforeach
                </x-ui.form-select>
                <x-ui.button type="submit" tone="primary" wire:loading.attr="disabled" wire:target="createTag">{{ __('hr_recruitment.applicant_ui.tags.create') }}</x-ui.button>
            </form>
            @foreach (['tagName', 'tagKind', 'tagColor'] as $field)
                @error($field)<p class="text-sm text-primary">{{ $message }}</p>@enderror
            @endforeach

            <ul class="divide-y divide-panel-border rounded-lg border border-panel-border">
                @forelse ($this->managedTags as $managed)
                    <li wire:key="managed-tag-{{ $managed->id }}" class="flex flex-wrap items-center justify-between gap-3 px-3 py-2">
                        @if ($renamingId === $managed->id)
                            <form wire:submit="renameTag" class="grid w-full items-end gap-3 sm:grid-cols-[minmax(0,1fr)_10rem_9rem_auto]">
                                <x-ui.form-input wire:model="renameName" :label="__('hr_recruitment.applicant_ui.tags.new_name')" maxlength="40" required dir="auto" />
                                <x-ui.form-select wire:model="renameKind" :label="__('hr_recruitment.applicant_ui.tags.kind')">
                                    @foreach ($kinds as $kind)
                                        <option value="{{ $kind }}">{{ __('hr_recruitment.applicant_ui.tags.kinds.'.$kind) }}</option>
                                    @endforeach
                                </x-ui.form-select>
                                <x-ui.form-select wire:model="renameColor" :label="__('hr_recruitment.applicant_ui.tags.color')">
                                    @foreach ($palette as $color)
                                        <option value="{{ $color }}">{{ __('hr_recruitment.applicant_ui.tags.colors.'.$color) }}</option>
                                    @endforeach
                                </x-ui.form-select>
                                <span class="flex gap-1">
                                    <x-ui.button type="submit" tone="primary" size="sm">{{ __('hr_recruitment.applicant_ui.tags.save') }}</x-ui.button>
                                    <x-ui.button tone="ghost" size="sm" wire:click="cancelRename">{{ __('hr_recruitment.applicant_ui.cancel') }}</x-ui.button>
                                </span>
                                @foreach (['renameName', 'renameKind', 'renameColor'] as $field)
                                    @error($field)<p class="text-sm text-primary sm:col-span-4">{{ $message }}</p>@enderror
                                @endforeach
                            </form>
                        @else
                            <span class="flex min-w-0 flex-wrap items-center gap-2">
                                <span @class(['inline-flex rounded-md px-2 py-0.5 text-xs font-semibold', $managed->classes(), 'line-through opacity-60' => $managed->trashed()])>{{ $managed->kindLabel() }} — {{ $managed->name }}</span>
                                <span class="text-xs {{ $muted }}">{{ trans_choice('hr_recruitment.applicant_ui.tags.usage', $managed->visible_count, ['count' => $managed->visible_count]) }}@if ($managed->trashed()) · {{ __('hr_recruitment.applicant_ui.tags.deleted') }}@endif</span>
                            </span>
                            <span class="flex gap-1">
                                @if ($managed->trashed())
                                    <x-ui.button tone="ghost" size="sm" wire:click="restoreTag({{ $managed->id }})">{{ __('hr_recruitment.applicant_ui.tags.restore') }}<span class="sr-only">: {{ $managed->name }}</span></x-ui.button>
                                @else
                                    <x-ui.button tone="ghost" size="sm" wire:click="beginRename({{ $managed->id }})">{{ __('hr_recruitment.applicant_ui.tags.rename') }}<span class="sr-only">: {{ $managed->name }}</span></x-ui.button>
                                    <x-ui.button tone="ghost" size="sm" wire:click="deleteTag({{ $managed->id }})" wire:confirm="{{ __('hr_recruitment.applicant_ui.tags.delete_confirm') }}">{{ __('hr_recruitment.applicant_ui.tags.delete') }}<span class="sr-only">: {{ $managed->name }}</span></x-ui.button>
                                @endif
                            </span>
                        @endif
                    </li>
                @empty
                    <li class="px-3 py-4 text-center text-sm {{ $muted }}">{{ __('hr_recruitment.applicant_ui.tags.none_defined') }}</li>
                @endforelse
            </ul>
        </div>
    @endif
</flux:modal>
