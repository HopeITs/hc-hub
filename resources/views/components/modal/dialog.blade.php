@props(['id'=> 'modal', 'maxWidth' => null ,'title' => 'modal'])

<x-modal :id="$id" :maxWidth="$maxWidth" {{$attributes}}>
{{--    <div class="relative p-4 w-full max-w-md max-h-full">--}}
        <!-- Modal content -->
        <div class="relative bg-white rounded-surface shadow-e1 dark:bg-neutral-700">
            <!-- Modal header -->
            <div class="flex items-center justify-between p-4 md:p-4 border-b rounded-t-surface dark:border-neutral-600 border-neutral-200">
                <h3 class="text-h4 font-semibold text-neutral-900 dark:text-white">
                 {{$title}}
                </h3>

            </div>
            <!-- Modal body -->
            <div class="p-4 md:p-4">
              {{$slot}}
            </div>
        </div>
{{--    </div>--}}
</x-modal>
