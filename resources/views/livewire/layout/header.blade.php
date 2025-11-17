<nav class="relative shadow-sm top-0 z-30 w-full h-full bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
    <div class="px-3 py-1  lg:px-5 lg:pl-3">
        <div class="flex items-center justify-end">

            <div class="flex w-[210px]  justify-between items-center">
                <x-layouts.app.notification/>
                    <button class="pl-4 p-2" >
                        <x-icons.document-text class="w-4.5 h-4.5 text-secondary-dark"/>
                    </button>


                    <button class="p-2" >
                        <x-icons.envelope class="w-4 h-4 text-secondary-dark"/>
                    </button>


                    <button class="p-2">
                        <x-icons.building class="w-4.5 h-4.5 text-secondary-dark"/>
                    </button>

                    <div class="p-2">
                        <img src="{{ auth()->user()->profile->getFirstMediaUrl('avatar') }}"
                             class="w-7.5 h-7.5 ring-1 ring-primary rounded-full border object-cover">
                    </div>
            </div>
        </div>
    </div>
</nav>
