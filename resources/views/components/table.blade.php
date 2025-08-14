<div class="card border border-borderColor rounded-[5px] shadow-xs bg-white mt-2 mb-8">
    <div class="card-body p-0">

        <div class="custom-datatable-filter overflow-x-auto">
            <table id="datatable" class="table w-full border-b border-borderColor text-right">
            <thead class="thead-light">
                <tr>
                    {{ $headings ?? '' }}
                </tr>
                </thead>
                <tbody class="bg-white divide-y divide-borderColor">
                {{ $body ?? '' }}
                </tbody>

            </table>
        </div>
    </div>

    <div class="p-4">
        {{ $pagination }}
    </div>
</div>
