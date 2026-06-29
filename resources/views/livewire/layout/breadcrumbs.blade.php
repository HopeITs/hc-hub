<div class="md:flex block items-center justify-between page-breadcrumb mb-4">
    <div class="my-auto mb-2">
        <h2 class="mb-1">{{ $title }}</h2>

        @if (!empty($items))
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-2">

                    {{-- Home --}}
                    <li class="inline-flex items-center">
                        <a href="{{ route('dashboard') }}"
                           class="inline-flex items-center text-xs text-gray-500 hover:text-primary">
                            <i class="ti ti-smart-home"></i>
                        </a>
                    </li>

                    @foreach($items as $i => $crumb)
                        <li><span class="text-default">/</span></li>

                        <li class="text-sm {{ $loop->last ? 'text-gray-900' : 'text-default' }}">
                            @if(!empty($crumb['href']))
                                <a href="{{ $crumb['href'] }}" class="hover:underline">
                                    {{ $crumb['label'] }}
                                </a>
                            @else
                                {{ $crumb['label'] }}
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif
    </div>
</div>
