<!-- resources/views/livewire/layouts/breadcrumbs.blade.php -->

<div class="md:flex block items-center justify-between page-breadcrumb mb-4">
    <div class="my-auto mb-2">
        <h2 class="mb-1">{{ $title }}</h2>
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-2">
                <li class="inline-flex items-center">
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center text-xs text-gray-500 hover:text-primary">
                        <i class="ti ti-smart-home"></i>
                    </a>
                </li>

                @foreach($crumbs as $crumb)
                    <li><span class="text-default">/</span></li>
                    <li class="text-xs {{ $loop->last ? 'text-gray-900' : 'text-default' }}">
                        @if ($crumb['url'])
                            <a href="{{ $crumb['url'] }}" class="hover:underline">{{ $crumb['label'] }}</a>
                        @else
                            {{ $crumb['label'] }}
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    </div>

    <div class="flex my-xl-auto right-content items-center flex-wrap">
        @if($showAddButton)
            <div class="mb-2">
                <a href="{{ $addUrl }}"
                   data-modal-target="{{ $addModalTarget }}"
                   data-modal-toggle="{{ $addModalTarget }}"
                   class="flex items-center bg-primary text-sm font-medium py-2 rounded text-white px-3 hover:bg-primary-900 hover:text-white">
                    <i class="ti ti-circle-plus me-2"></i>{{ $addLabel }}
                </a>
            </div>
        @endif

        <div class="head-icons mr-2 mb-2">
            <a href="javascript:void(0);"
               class="border flex items-center justify-center rounded bg-white w-9 h-9 hover:bg-primary hover:text-white hover:border-primary"
               id="collapse-header"
               title="Collapse">
                <i class="ti ti-chevrons-up"></i>
            </a>
        </div>
    </div>
</div>
