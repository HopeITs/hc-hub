{{--
    The Family and documents tab of an employee profile: relatives to contact
    and the uploaded documents. Only the employee and HR in their city see it.
    Included by ⚡show.blade.php.
--}}
@use('Modules\Hr\Enums\KinshipType')
@use('Modules\Hr\Support\Employees\EnumLabel')
@use('Modules\Hr\Support\Employees\HrMedia')

<div class="grid gap-5 @4xl/directory:grid-cols-2">
    <x-ui.panel :title="__('hr_employees.records.relatives')" :count="$this->relatives->count()" class="self-start">
        <x-slot:toolbar>
            @if ($canEdit)
                <x-ui.button size="sm" icon="plus" wire:click="openRecord('relative')" data-test="add-relative">{{ __('common.add') }}</x-ui.button>
            @endif
        </x-slot:toolbar>
        @if ($this->relatives->isEmpty())
            <p class="p-4 text-sm {{ $muted }}">{{ __('hr_employees.records.none') }}</p>
        @else
            <ul class="divide-y divide-panel-border">
                @foreach ($this->relatives as $relative)
                    <li class="flex flex-wrap items-center gap-3 px-4 py-3" wire:key="relative-{{ $relative->id }}">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold {{ $ink }}" dir="auto">{{ $relative->name }}</p>
                            <p class="text-xs {{ $muted }}">{{ EnumLabel::of(KinshipType::class, (int) $relative->relation) }}@if (filled($relative->phone)) · <span dir="ltr">{{ $relative->phone }}</span>@endif</p>
                        </div>
                        <x-hr::employees.record-actions kind="relative" :id="$relative->id" :editable="$canEdit" :name="$relative->name" />
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.panel>

    <x-ui.panel :title="__('hr_employees.records.attachments')" :count="$this->attachments->count()" class="self-start">
        <x-slot:toolbar>
            @if ($canEdit)
                <x-ui.button size="sm" icon="plus" wire:click="openRecord('attachment')" data-test="add-attachment">{{ __('common.add') }}</x-ui.button>
            @endif
        </x-slot:toolbar>
        @if ($this->attachments->isEmpty())
            <p class="p-4 text-sm {{ $muted }}">{{ __('hr_employees.records.none') }}</p>
        @else
            <ul class="divide-y divide-panel-border">
                @foreach ($this->attachments as $attachment)
                    @php
                        $attachmentName = HrMedia::property($attachment, 'name', (string) $attachment->name);
                        $attachmentCategory = HrMedia::property($attachment, 'category');
                    @endphp
                    <li class="flex flex-wrap items-center gap-3 px-4 py-3" wire:key="attachment-{{ $attachment->id }}">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold {{ $ink }}" dir="auto">{{ $attachmentName }}</p>
                            <p class="text-xs {{ $muted }}">
                                @if ($attachmentCategory !== '') {{ __('hr_employees.records.categories.'.$attachmentCategory) }} · @endif
                                <span dir="ltr">{{ strtoupper(HrMedia::extension($attachment)) }} · {{ number_format($attachment->size / 1024) }} KB</span>
                            </p>
                        </div>
                        <x-hr::employees.record-actions kind="attachment" :id="$attachment->id" :file="$attachment->id" :editable="$canEdit" :name="$attachmentName" />
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.panel>
</div>
