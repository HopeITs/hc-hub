@props(['id'=> 'modal', 'maxWidth' => null ,'title' => 'modal', 'closeAction' => null])

@php($titleId = $id.'-title')

<x-modal :id="$id" :maxWidth="$maxWidth" :labelledby="$titleId" :close-action="$closeAction" {{$attributes}}>
        <!-- Modal content -->
        <div class="relative bg-white rounded-surface shadow-e1 dark:bg-neutral-700">
            <!-- Modal header -->
            <div class="flex items-center justify-between gap-2 p-4 md:p-4 border-b rounded-t-surface dark:border-neutral-600 border-neutral-200">
                <h2 id="{{ $titleId }}" class="text-h4 font-semibold text-neutral-900 dark:text-white">
                 {{$title}}
                </h2>

                {{-- Same path as Escape and Cancel: closes locally, never submits. --}}
                <button type="button"
                        x-on:click="close()"
                        aria-label="{{ __('general.a11y.close-dialog') }}"
                        data-modal-close
                        class="-me-2 grid size-11 shrink-0 place-items-center rounded-control text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary motion-reduce:transition-none">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                        <path d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>
            </div>
            <!-- Modal body -->
            <div class="p-4 md:p-4">
              {{$slot}}
            </div>
        </div>
</x-modal>
