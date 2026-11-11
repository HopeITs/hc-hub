{{-- One applicant file. The download link shows only to users who may download; the route asks the policy again and audits every attempt. --}}
@php
    use Illuminate\Support\Facades\Lang;
    use Modules\Hr\Enums\RecruitmentFileScanStatus;

    $muted = 'text-secondary-medium dark:text-ink-muted';
    $typeKey = 'hr_recruitment.applicant_ui.file_types.'.$file->file_type;
    $typeLabel = Lang::has($typeKey) ? __($typeKey) : str($file->file_type)->headline();
    $media = $profile['media'][$file->id] ?? null;
@endphp
<li wire:key="file-{{ $file->id }}" id="file-{{ $file->id }}" class="flex flex-wrap items-center justify-between gap-3 py-2">
    <div class="min-w-0">
        <span class="font-medium">{{ $typeLabel }}</span>
        @if ($media?->name)
            <span class="block truncate text-xs {{ $muted }}" dir="auto" title="{{ $media->name }}">{{ $media->name }}</span>
        @endif
        <span class="text-xs tabular-nums {{ $muted }}">{{ $file->uploaded_at?->translatedFormat(__('formats.date')) }}</span>
        @if ($file->scan_status !== RecruitmentFileScanStatus::Clean)
            <span @class(['ms-1 rounded-md px-2 py-0.5 text-xs font-semibold', 'bg-secondary-soft text-secondary-dark dark:bg-surface-3 dark:text-ink' => $file->scan_status === RecruitmentFileScanStatus::Pending, 'border border-primary text-primary dark:border-brand dark:text-brand-text' => $file->scan_status !== RecruitmentFileScanStatus::Pending])>{{ __('hr_recruitment.applicant_ui.scan.'.$file->scan_status) }}</span>
        @endif
        @if ($file->isArchived())
            <span class="ms-1 rounded-md bg-secondary-soft px-2 py-0.5 text-xs font-semibold dark:bg-surface-3">{{ __('hr_recruitment.applicant_ui.cv_archive.archived_on', ['date' => $file->archived_at->translatedFormat(__('formats.date')), 'name' => $file->archivedBy?->name ?? '—']) }}</span>
        @endif
    </div>
    <div class="flex flex-wrap items-center gap-1">
        @can('download', $file)
            <x-ui.button tone="ghost" size="sm" icon="arrow-down-tray" :href="route('hr.recruitment.applicant-files.download', ['file' => $file->id])">{{ __('hr_recruitment.applicant_ui.download') }}<span class="sr-only">: {{ $typeLabel }}</span></x-ui.button>
        @endcan
        @can('archive', $file)
            <x-ui.button tone="ghost" size="sm" icon="archive-box" wire:click="beginArchive({{ $file->id }})">{{ __('hr_recruitment.applicant_ui.cv_archive.archive') }}<span class="sr-only">: {{ $typeLabel }}</span></x-ui.button>
        @endcan
    </div>
</li>
