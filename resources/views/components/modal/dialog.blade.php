@props(['id'=> 'modal', 'maxWidth' => null ,'title' => 'modal'])

<x-modal :id="$id" :maxWidth="$maxWidth" {{$attributes}}>
{{--    <div class="relative p-4 w-full max-w-md max-h-full">--}}
        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow-sm dark:bg-gray-700">
            <!-- Modal header -->
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600 border-gray-200">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                 {{$title}}
                </h3>

            </div>
            <!-- Modal body -->
            <div class="p-4 md:p-5">
              {{$slot}}
            </div>
        </div>
{{--    </div>--}}
</x-modal>
