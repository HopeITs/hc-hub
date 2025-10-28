<div
    x-data="notesBoard()"
    x-init="init(@js($this->notes->pluck('id')))"
    class="max-w-6xl mx-auto"
>
    <div class="bg-white/80 backdrop-blur rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

        <div
            class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-b border-gray-100 ">
            <div class="flex items-center gap-2">
                <h2 class="text-lg font-semibold text-[var(--color-neutral-800)] tracking-tight">Your Notes & To-Do List</h2>
                <span
                    class="text-xs font-medium text-[var(--color-primary-600)] bg-[var(--color-primary-100)] px-2 py-0.5 rounded-full">
                    {{ $this->notes->count() }}
                </span>
            </div>

            <div class="flex items-center gap-2">
                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search..."
                    class="h-9 w-44 rounded-lg border-[var(--color-neutral-300)] text-sm focus:ring-1 focus:ring-[var(--color-primary-500)] focus:border-[var(--color-primary-500)] placeholder-[var(--color-neutral-400)]"
                />
                <select
                    wire:model.live="filter"
                    class="h-9 rounded-lg border-[var(--color-neutral-300)] text-sm focus:ring-1 focus:ring-[var(--color-primary-500)] focus:border-[var(--color-primary-500)]"
                >
                    <option value="all">All</option>
                    <option value="open">Open</option>
                    <option value="done">Done</option>
                </select>
            </div>
        </div>

        <div class="px-6 pt-3 border-b border-[var(--color-neutral-100)] bg-[var(--color-neutral-50)]">
            <div class="flex gap-6">
                <button
                    x-on:click="activeTab = 'notes'"
                    :class="activeTab === 'notes' ? 'text-[var(--color-primary-600)] border-[var(--color-primary-600)]' : 'text-[var(--color-neutral-500)] border-transparent hover:text-[var(--color-neutral-700)]'"
                    class="pb-3 border-b-2 font-medium text-sm transition"
                >Notes
                </button>
                <button
                    x-on:click="activeTab = 'checkboxes'"
                    :class="activeTab === 'checkboxes' ? 'text-[var(--color-primary-600)] border-[var(--color-primary-600)]' : 'text-[var(--color-neutral-500)] border-transparent hover:text-[var(--color-neutral-700)]'"
                    class="pb-3 border-b-2 font-medium text-sm transition"
                >Checkboxes
                </button>
            </div>
        </div>

        <div x-show="activeTab === 'notes'" class="p-6 space-y-5" x-cloak>
            <div class="flex flex-wrap items-center gap-3">
                <input
                    type="text"
                    wire:model.defer="newText"
                    placeholder="Write a note..."
                    class="flex-1 h-10 rounded-lg border-[var(--color-neutral-300)] text-sm focus:ring-1 focus:ring-[var(--color-primary-500)] focus:border-[var(--color-primary-500)] placeholder-[var(--color-neutral-400)]"
                />
                <button
                    wire:click="addText"
                    class="h-10 px-4 rounded-lg text-sm font-medium bg-[var(--color-primary-600)] text-white hover:bg-[var(--color-primary-700)] transition"
                >Add note
                </button>
            </div>

            <div id="noteList" @dragover.prevent @drop="drop($event)" class="space-y-3">
                @forelse($this->notes->where('type','text') as $note)
                    @php $wordCount = str_word_count(strip_tags($note->content)); @endphp
                    <div
                        :draggable="true"
                        @dragstart="dragStart($event, {{ $note->id }})"
                        @dragend="dragEnd($event)"
                        data-id="{{ $note->id }}"
                        class="group flex justify-between items-center bg-white border border-[var(--color-neutral-200)] rounded-xl px-4 py-3 shadow-sm hover:shadow-md transition"
                    >
                        <div class="flex-1 text-sm font-medium"
                             :style="`color: ${colors[{{ $note->id }}] || '{{ $note->color }}'}`">
                            @if($wordCount > 5)
                                <button
                                    @click="openModal({{ $note->id }}, @js($note->content), '{{ $note->color }}')"
                                    class="text-left w-full truncate hover:text-[var(--color-primary-600)]"
                                >{{ \Illuminate\Support\Str::words($note->content, 5, '...') }}</button>
                            @else
                                <div>{{ $note->content }}</div>
                            @endif
                        </div>

                        <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition">
                            <input
                                type="color"
                                x-on:change="changeColor('{{ $note->id }}', $event.target.value)"
                                value="{{ $note->color }}"
                                list="themeColors"
                                class="w-6 h-6 cursor-pointer border rounded-md bg-transparent p-0"
                                title="Change color"
                            />

                            <button
                                @click="openModal({{ $note->id }}, @js($note->content), '{{ $note->color }}')"
                                class="p-1.5 rounded-lg hover:bg-[var(--color-primary-50)] text-[var(--color-primary-600)] hover:scale-110 transition-transform"
                                title="Edit"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                     stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="m16.862 3.487 3.651 3.651m-2.579-2.579-11.89 11.89a2 2 0 0 0-.518.889l-.786 3.143a.5.5 0 0 0 .606.606l3.143-.786a2 2 0 0 0 .889-.518l11.89-11.89z"/>
                                </svg>
                            </button>

                            <button
                                wire:click="delete({{ $note->id }})"
                                class="p-1.5 rounded-lg hover:bg-red-50 text-red-500 hover:scale-110 transition-transform"
                                title="Delete"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                     stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6z"/>
                                </svg>
                            </button>
                        </div>

                    </div>
                @empty
                    <p class="text-sm text-[var(--color-neutral-400)] text-center py-4">No notes yet. Add one above.</p>
                @endforelse
            </div>
        </div>

        <div x-show="activeTab === 'checkboxes'" class="p-6 space-y-5" x-cloak>
            <div class="flex flex-wrap items-center gap-3">
                <input
                    type="text"
                    wire:model.defer="newCheckbox"
                    placeholder="New checkbox..."
                    class="flex-1 h-10 rounded-lg border-[var(--color-neutral-300)] text-sm focus:ring-1 focus:ring-[var(--color-primary-500)] focus:border-[var(--color-primary-500)] placeholder-[var(--color-neutral-400)]"
                />
                <button
                    wire:click="addCheckbox"
                    class="h-10 px-4 rounded-lg text-sm font-medium bg-[var(--color-primary-600)] text-white hover:bg-[var(--color-primary-700)] transition"
                >Add task
                </button>
            </div>

            <div id="checkboxList" @dragover.prevent @drop="drop($event)" class="space-y-3">
                @foreach($this->notes->where('type','checkbox') as $note)
                    <div
                        :draggable="true"
                        @dragstart="dragStart($event, {{ $note->id }})"
                        @dragend="dragEnd($event)"
                        data-id="{{ $note->id }}"
                        class="group flex justify-between items-center bg-white border border-[var(--color-neutral-200)] rounded-xl px-4 py-3 shadow-sm hover:shadow-md transition"
                    >
                        <div class="flex items-center gap-3 flex-1">
                            <input
                                type="checkbox"
                                @click="toggleCheckbox({{ $note->id }})"
                                :checked="{{ $note->is_done ? 'true' : 'false' }}"
                                class="rounded border-[var(--color-neutral-300)] text-[var(--color-primary-600)] focus:ring-[var(--color-primary-500)] cursor-pointer"
                            />
                            <div
                                class="outline-none text-sm font-medium"
                                :class="{{ $note->is_done ? "'line-through text-[var(--color-neutral-400)]'" : "''" }}"
                                :style="`color: ${colors[{{ $note->id }}] || '{{ $note->color }}'}`"
                            >{{ $note->content }}</div>
                        </div>

                        <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition">
                            <input
                                type="color"
                                x-on:change="changeColor('{{ $note->id }}', $event.target.value)"
                                value="{{ $note->color }}"
                                list="themeColors"
                                class="w-6 h-6 cursor-pointer border rounded-md bg-transparent p-0"
                                title="Change color"
                            />

                            <button
                                @click="openModal({{ $note->id }}, @js($note->content), '{{ $note->color }}')"
                                class="p-1.5 rounded-lg hover:bg-[var(--color-primary-50)] text-[var(--color-primary-600)] hover:scale-110 transition-transform"
                                title="Edit"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                     stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="m16.862 3.487 3.651 3.651m-2.579-2.579-11.89 11.89a2 2 0 0 0-.518.889l-.786 3.143a.5.5 0 0 0 .606.606l3.143-.786a2 2 0 0 0 .889-.518l11.89-11.89z"/>
                                </svg>
                            </button>

                            <button
                                wire:click="delete({{ $note->id }})"
                                class="p-1.5 rounded-lg hover:bg-red-50 text-red-500 hover:scale-110 transition-transform"
                                title="Delete"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                     stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6z"/>
                                </svg>
                            </button>
                        </div>

                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div
        x-show="showModal"
        x-cloak
        @keydown.escape.window="showModal=false"
        @click.self="showModal=false"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
    >
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
            <h3 class="mb-4 text-lg font-semibold text-[var(--color-neutral-800)]">Edit Note</h3>
            <textarea
                x-model="modalContent"
                :style="`color: ${modalColor}`"
                class="h-32 w-full rounded-lg border-[var(--color-neutral-300)] text-sm focus:border-[var(--color-primary-500)] focus:ring-[var(--color-primary-500)]"
            ></textarea>

            <div class="mt-4 flex items-center justify-between">
                <input
                    type="color"
                    x-model="modalColor"
                    list="themeColors"
                    class="h-8 w-8 cursor-pointer rounded-md border bg-transparent"
                    title="Change color"
                />
                <div class="flex gap-2">
                    <button
                        @click="saveModal()"
                        class="rounded-lg bg-[var(--color-primary-600)] px-4 py-2 text-sm font-medium text-white hover:bg-[var(--color-primary-700)]"
                    >Save
                    </button>
                    <button
                        @click="showModal=false"
                        class="rounded-lg border px-4 py-2 text-sm font-medium text-[var(--color-neutral-600)] hover:bg-[var(--color-neutral-50)]"
                    >Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <datalist id="themeColors">
        <option value="#be1f24"></option>
        <option value="#98191d"></option>
        <option value="#731316"></option>
        <option value="#4d0d0f"></option>
        <option value="#2a0808"></option>
        <option value="#150404"></option>
        <option value="#414042"></option>
        <option value="#6d6e71"></option>
        <option value="#939598"></option>
        <option value="#dcddde"></option>
        <option value="#f5f5f6"></option>
        <option value="#2b2a2c"></option>
    </datalist>

    <script>
        function notesBoard() {
            return {
                activeTab: 'notes',
                showModal: false,
                modalId: null,
                modalContent: '',
                modalColor: '#000000',
                colors: {},
                draggingId: null,
                order: [],

                init(ids) {
                    this.order = ids

                    Livewire.hook('morph.updated', () => {
                        this.$nextTick(() => {
                            for (const [id, color] of Object.entries(this.colors)) {
                                const el = document.querySelector(`[data-id="${id}"] [style]`)
                                if (el) el.style.color = color
                            }
                        })
                    })
                },

                changeColor(id, color) {
                    this.colors[id] = color
                    const el = document.querySelector(`[data-id="${id}"] [style]`)
                    if (el) el.style.color = color

                    Livewire.find('{{ $this->getId() }}').call('updateColor', id, color)
                },

                toggleCheckbox(id) {
                    Livewire.find('{{ $this->getId() }}').call('toggleDone', id)
                },

                openModal(id, content, color) {
                    this.modalId = id
                    this.modalContent = content || ''
                    this.modalColor = this.colors[id] || color || '#000000'
                    this.showModal = true
                },

                saveModal() {
                    const livewire = Livewire.find('{{ $this->getId() }}')
                    this.colors[this.modalId] = this.modalColor

                    const el = document.querySelector(`[data-id="${this.modalId}"] [style]`)
                    if (el) el.style.color = this.modalColor

                    livewire.call('updateContent', this.modalId, this.modalContent)
                    livewire.call('updateColor', this.modalId, this.modalColor)

                    this.showModal = false
                },

                dragStart(e, id) {
                    this.draggingId = id
                    e.dataTransfer.effectAllowed = 'move'
                },

                dragEnd() {
                    this.draggingId = null
                },

                drop() {
                    const list = document.querySelector(
                        this.activeTab === 'notes' ? '#noteList' : '#checkboxList'
                    )
                    const newOrder = Array.from(list.children).map(li => Number(li.dataset.id))
                    Livewire.dispatch('reorder-notes', {orderedIds: newOrder})
                }
            }
        }
    </script>
</div>
