<div class="md:flex block items-center justify-between page-breadcrumb mb-4">
    <div class="my-auto mb-2">
        <h2 class="mb-1">{{ $title }}</h2>

        @if (!empty($crumbs))
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
                            @if (!empty($crumb['url']))
                                <a href="{{ $crumb['url'] }}" class="hover:underline">{{ $crumb['label'] }}</a>
                            @else
                                {{ $crumb['label'] }}
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif
    </div>

    <div class="flex my-xl-auto right-content items-center flex-wrap">
        @if($showButton)
            <div class="mb-2">
                <a
                    href="{{ $buttonUrl }}"
                    @if($buttonModalTarget) data-modal-target="{{ $buttonModalTarget }}" @endif
                    @if($buttonModalToggle) data-modal-toggle="{{ $buttonModalToggle }}" @endif
                    class="flex items-center bg-primary text-sm font-medium py-2 rounded text-white px-3 hover:bg-primary-900 hover:text-white"
                >
                    <i class="{{ $buttonIcon }} me-2"></i>{{ $buttonLabel }}
                </a>
            </div>
        @endif


    </div>
</div>
