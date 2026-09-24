{{-- One applicant file. Expects $file; the archive panel lives on the profile. --}}
@php
    $scan = $file->scan_status;
@endphp
<li wire:key="file-{{ $file->id }}" class="flex flex-wrap items-center justify-between gap-3 py-2">
    <div>
        <span class="font-medium">{{ __('recruitment-applicant.file_types.'.$file->file_type) === 'recruitment-applicant.file_types.'.$file->file_type ? str($file->file_type)->headline() : __('recruitment-applicant.file_types.'.$file->file_type) }}</span>
        <span class="text-secondary-medium">&middot; {{ $file->uploaded_at?->format('Y-m-d') }}</span>
        @if ($scan !== \App\Enums\RecruitmentFileScanStatus::Clean)
            <span class="ms-1 rounded-control bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-800">{{ __('recruitment-applicant.scan.'.$scan) }}</span>
        @endif
        @if ($file->isArchived())
            <span class="ms-1 rounded-control bg-neutral-100 px-2 py-0.5 text-xs font-semibold text-secondary-medium">
                {{ __('recruitment-applicant.cv_archive.archived_on', ['date' => $file->archived_at->format('Y-m-d'), 'name' => $file->archivedBy?->profile?->name ?? $file->archivedBy?->name ?? '—']) }}
            </span>
        @endif
    </div>
    <div class="flex flex-wrap items-center gap-3">
        @can('download', $file)
            <a href="{{ route('recruitment.applicant-files.download', $file->id) }}" class="font-semibold text-primary hover:underline">
                {{ __('recruitment-applicant.download') }}
            </a>
        @endcan
        @can('archive', $file)
            <button type="button" wire:click="beginArchive({{ $file->id }})" class="cursor-pointer font-semibold text-secondary-medium hover:underline">
                {{ __('recruitment-applicant.cv_archive.archive') }}
            </button>
        @endcan
    </div>
</li>
