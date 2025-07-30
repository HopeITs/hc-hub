@props([
    'title' => 'list',
    'headings' => ['#'],
    'items' => ''
])
<div class="card border border-borderColor rounded-[5px] shadow-xs bg-white mt-2 mb-8">


    <div class="card-body p-0">
        <div class="custom-datatable-filter">
            <table class="table w-full border-b border-borderColor">
                <thead class="thead-light">
                <tr>
                    @foreach($headings as $heading)
                        <x-datatable.th>{{$heading}}</x-datatable.th>
                    @endforeach
                </tr>
                </thead>

                <tbody class="bg-white divide-y divide-borderColor">
                {{$slot}}
                </tbody>

            </table>
        </div>
    </div>

    <div class="p-4">
        {{ $items->links() }}
    </div>
</div>
