<div class="w-full">
    <div
        x-data="notesBoard()"
        x-init="init(@js($this->notes->pluck('id')))"
        class="relative w-full ">
        <div class="bg-white rounded-3xl border border-gray-200 shadow-[0px_0px_25px_-14px_rgba(0,_0,_0,_0.30)] flex flex-col h-68 w-full">
            <div class="flex items-center justify-between px-6 pt-6 shrink-0">
                <div class="flex pt-2 items-center gap-2">
                    <h2 class="text-md font-semibold text-secondary-medium">@lang('general.note')</h2>
                    <span class="text-sm font-medium text-secondary-light px-2 py-0.5 rounded-full">
                        {{ $this->notes->count() }}
                    </span>
                    <div class="text-red-500 w-3.5 h-3.5 text-sm">
                        <img src="{{ asset('assets/icons/notes.svg') }}" alt="Notes icon">
                    </div>
                </div>

                <button
                    @click="openNewNoteModal()"
                    class="flex items-center me-4 gap-2 bg-primary hover:bg-red-700 text-white text-xs font-medium px-3 py-2 rounded-md transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    @lang('general.add-note')
                </button>
            </div>

            <div class="px-6 py-4 flex-1 overflow-hidden">
                <div class=" pr-1 pt-1 ">

                    <table class="w-full text-sm text-gray-700 ">
                        <thead class="sticky top-0 z-10 bg-white">
                        <tr class="border-b border-b-secondary-light text-gray-500">
                            <th class=" py-1 w-10 text-center font-medium"></th>
                            <th class="py-1 font-medium text-left"></th>
                            <th class="py-1 px-3 ltr:text-right rtl:text-left font-medium">@lang('general.actions')</th>
                            <th class="py-1 text-center font-medium w-16">@lang('general.done')!</th>
                        </tr>
                        </thead>

                        <tbody>
                        @forelse($notes as $index => $note)
                            <tr
                                data-id="{{ $note->id }}"
                                class="border-b border-secondary-light last:border-0 hover:bg-gray-50 transition">
                                <td class="text-center font-semibold text-secondary-medium align-middle">
                                    {{ $index + 1 }}.
                                </td>

                                <td class="text-secondary-light font-medium align-middle truncate @if($note->is_done) line-through @endif">
                                    {{ Str::limit($note->content, 60, '...') }}
                                </td>

                                <td class="w-full py-2 px-4">

                                    <div class="flex  ltr:-me-11 rtl:-ml-9 items-left rtl:justify-start ltr:text-left rtl:text-right ltr:justify-end gap-4 rtl:flex-row-reverse">

                                        {{-- Edit --}}
                                        <button
                                            @click="openEditModal({{ $note->id }}, @js($note->content))"
                                            class="inline-flex items-center justify-center text-gray-500 hover:text-primary transition"
                                            title="Edit Note">
                                            <img class="w-4 h-4" src="{{asset('assets/icons/Edit.svg')}}" alt="">
                                        </button>

                                        {{-- Delete --}}
                                        <button
                                            @click="confirmDelete({{ $note->id }})"
                                            class="inline-flex me-1 items-center justify-center text-gray-400 hover:text-red-600 transition"
                                            title="Delete Note">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                      d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>


                                        {{-- Done Checkbox --}}
                                        <button
                                            @click="toggleCheckbox({{ $note->id }})"
                                            title="Toggle Done"
                                            class="inline-flex items-center justify-center mt-[1px] w-4 h-4 rounded-full border border-gray-400 hover:border-primary transition">
                                            @if($note->is_done)
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                     class="w-4 h-4 rounded-full bg-primary text-xs text-white"
                                                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                            @endif
                                        </button>

                                    </div>

                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-6 pt-19 text-secondary-light font-light text-sm">
                                    <p>@lang('general.no-notes-1') <span class="text-red-600 font-light">“@lang('general.add-note')”</span> @lang('general.no-notes-2') </p>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div
            x-show="showModal"
            x-cloak
            @keydown.escape.window="showModal=false"
            @click.self="showModal=false"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
            x-transition>
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-gray-100">
                <div class="flex items-start justify-between mb-5">
                    <h3 class="text-lg font-semibold text-gray-900 tracking-tight"
                        x-text="modalMode === 'create' ? '{{__('general.add-new-note')}}' : '{{__('general.edit-note')}}'"></h3>
                    <button @click="showModal=false" class="text-gray-400 hover:text-gray-600 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-5">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">@lang('general.note-content')</label>
                        <textarea
                            x-model="modalContent"
                            placeholder="{{__('general.write-your-note')}}..."
                            class="block w-full h-28 rounded-lg border border-gray-300 text-sm text-gray-800 py-2 px-3 resize-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                        ></textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        @click="saveModal()"
                        class="inline-flex items-center justify-center bg-primary hover:bg-red-700 text-white px-4 py-2 rounded-lg text-xs font-medium shadow-sm transition"
                        x-text="modalMode === 'create' ? '{{__('general.add-note')}}' : '{{__('general.save-changes')}}'">
                    </button>
                    <button
                        @click="showModal=false"
                        class="inline-flex items-center justify-center border border-gray-300 px-4 py-2 rounded-lg text-xs font-medium text-gray-700 hover:bg-gray-100 transition">
                        @lang('general.cancel')
                    </button>
                </div>
            </div>
        </div>

        <div
            x-show="showDeleteModal"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
            x-transition>
            <div class="w-full max-w-sm bg-white rounded-2xl shadow-xl border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-3">@lang('general.delete-note')</h2>
                <p class="text-sm text-gray-600 mb-6">
                    @lang('general.delete-note-text')
                </p>

                <div class="flex justify-end space-x-3">
                    <button
                        @click="showDeleteModal = false"
                        class="px-4 py-2 rounded-lg border border-gray-300 text-xs font-medium text-gray-700 hover:bg-gray-100 transition">
                        @lang('general.cancel')
                    </button>
                    <button
                        @click="
                            Livewire.find('{{ $this->getId() }}').call('delete', deleteId);
                            showDeleteModal = false;"
                        class="px-4 py-2 rounded-lg bg-primary text-white text-xs font-medium hover:bg-red-700 transition">
                        @lang('general.delete')
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function notesBoard() {
            return {
                showModal: false,
                modalMode: 'create',
                modalId: null,
                modalContent: '',
                modalType: 'note',
                showDeleteModal: false,
                deleteId: null,

                init(ids) {
                    this.order = Array.isArray(ids) ? ids : [];
                },

                openNewNoteModal() {
                    this.modalMode = 'create';
                    this.modalId = null;
                    this.modalContent = '';
                    this.modalType = 'note';
                    this.showModal = true;
                },

                openEditModal(id, content) {
                    this.modalMode = 'edit';
                    this.modalId = id;
                    this.modalContent = content || '';
                    this.modalType = 'note';
                    this.showModal = true;
                },

                saveModal() {
                    const lw = Livewire.find('{{ $this->getId() }}');
                    if (!lw) return;

                    if (this.modalMode === 'create') {
                        lw.call('createNote', this.modalContent, this.modalType);
                    } else {
                        lw.call('updateContent', this.modalId, this.modalContent);
                    }

                    this.showModal = false;
                },

                toggleCheckbox(id) {
                    const lw = Livewire.find('{{ $this->getId() }}');
                    if (!lw) return;
                    lw.call('toggleDone', id);
                },

                confirmDelete(id) {
                    this.deleteId = id;
                    this.showDeleteModal = true;
                },
            };
        }
    </script>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 4px;
        }
    </style>
</div>
