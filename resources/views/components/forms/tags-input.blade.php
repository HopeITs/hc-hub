@props([
    'placeholder' => 'Add tag',
    'suggestions' => [],
    'forceSuggestion' => false, // new option
])

<div
    x-data="tagsInput({
        model: @entangle($attributes->wire('model')),
        suggestions: @js($suggestions),
        forceSuggestion: {{ $forceSuggestion ? 'true' : 'false' }},
    })"
    class="flex flex-wrap items-center gap-2 relative"
>
    <x-forms.input
        x-ref="input"
        type="text"
        :placeholder="$placeholder"
        @input="filterSuggestions($event.target.value)"
        @keydown.enter.prevent="addFromInput()"
        @keydown.tab.prevent="addFromInput()"
        @keydown.backspace="maybeRemove()"
        @blur="addFromInput(); hideSuggestionsLater()"
        @paste.prevent="paste($event)"
        class="flex-1"
    />

    <!-- suggestions -->
    <template x-if="showSuggestions && filtered.length">
        <ul
            class="absolute top-full left-0 z-10 w-full bg-white border border-gray-300 mt-1 rounded-md shadow-md max-h-40 overflow-y-auto"
        >
            <template x-for="(s, i) in filtered" :key="i">
                <li
                    @mousedown.prevent="selectSuggestion(s)"
                    class="px-3 py-1 cursor-pointer hover:bg-gray-100"
                    x-text="capitalize(s)"
                ></li>
            </template>
        </ul>
    </template>

    <!-- current tags -->
    <template x-for="(tag, i) in tags" :key="i">
        <span
            class="inline-flex mt-2 items-center gap-1 rounded-full bg-gray-200 px-2 py-1 text-sm"
        >
            <span x-text="capitalize(tag)"></span>
            <button type="button" @click="remove(i)" class="text-gray-600 hover:text-red-600">×</button>
        </span>
    </template>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('tagsInput', ({ model, suggestions = [], forceSuggestion = false }) => ({
            model, // comma-separated Livewire string
            tags: [],
            suggestions,
            filtered: [],
            showSuggestions: false,
            forceSuggestion,

            init() {
                this.syncFromModel();
                this.$watch('model', () => this.syncFromModel());
                this.$watch('tags', () => this.syncToModel());
            },

            syncFromModel() {
                this.tags = typeof this.model === 'string'
                    ? this.model.split(',').map(t => t.trim().toLowerCase()).filter(Boolean)
                    : [];
            },
            syncToModel() {
                this.model = this.tags.join(',');
            },

            add(tag) {
                tag = String(tag || '').trim().toLowerCase();
                if (!tag) return;

                // block non-suggestions if forced
                if (this.forceSuggestion && !this.suggestions.map(s => s.toLowerCase()).includes(tag)) {
                    this.$refs.input.value = '';
                    this.hideSuggestions();
                    return;
                }

                tag.split(/[,\n]/).forEach(t => {
                    t = t.trim();
                    if (t && !this.tags.includes(t)) this.tags.push(t);
                });
                this.hideSuggestions();
            },

            addFromInput() {
                const v = this.$refs.input.value.trim();
                if (!v) return;
                this.add(v);
                this.$refs.input.value = '';
            },

            remove(i) { this.tags.splice(i, 1); },
            maybeRemove() {
                if (this.$refs.input.value === '' && this.tags.length)
                    this.remove(this.tags.length - 1);
            },
            paste(e) {
                const text = (e.clipboardData || window.clipboardData).getData('text');
                this.add(text);
                this.$refs.input.value = '';
            },

            // suggestions
            filterSuggestions(value) {
                const v = value.trim().toLowerCase();
                if (!v) { this.filtered = []; this.showSuggestions = false; return; }
                this.filtered = this.suggestions
                    .map(s => s.toLowerCase())
                    .filter(s => s.includes(v) && !this.tags.includes(s))
                    .slice(0, 10);
                this.showSuggestions = this.filtered.length > 0;
            },
            selectSuggestion(s) {
                this.add(s);
                this.$refs.input.value = '';
                this.hideSuggestions();
            },
            hideSuggestions() { this.showSuggestions = false; },
            hideSuggestionsLater() { setTimeout(() => this.hideSuggestions(), 150); },

            capitalize(s) {
                return s.charAt(0).toUpperCase() + s.slice(1);
            },
        }))
    })
</script>
