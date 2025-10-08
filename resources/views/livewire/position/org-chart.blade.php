<div class="flex flex-col items-center w-full overflow-x-auto py-6">

    {{-- CEO --}}
    @if(isset($tree['root']))
        <div class="flex flex-col items-center mb-10">
            <x-org-node :position="$tree['root']" color="blue" size="lg" />
        </div>
    @endif

    {{-- Level 1: Board --}}
    <div class="flex flex-row gap-4 mb-10">
        @foreach($tree['board'] as $p)
            <x-org-node :position="$p" color="indigo" />
        @endforeach
    </div>

    {{-- Level 2: Managers & Teams --}}
    <div class="flex flex-row gap-8 flex-wrap justify-center">
        @foreach($tree['managers'] as $m)
            <div class="flex flex-col items-center">
                <x-org-node :position="$m" color="cyan" />
                <div class="flex flex-row gap-2 mt-2">
                    @foreach($tree['teams'][$m->id] ?? [] as $t)
                        <x-org-node :position="$t" color="gray" size="sm" />
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

</div>
