@props([
    'name',        // assignment | contract | schedule | pay
    'index',
    'title',
    'hint' => null,
    'summary',     // Alpine expression producing the collapsed one-line summary
])

@php($state = "stateOf('{$name}')")

<section
    {{-- No overflow-hidden: the comboboxes inside render an absolutely
         positioned listbox that must be free to escape this card. --}}
    class="rounded-surface border transition-colors"
    x-bind:class="{
        'border-primary bg-white shadow-e1': {{ $state }} === 'open',
        'border-neutral-200 bg-neutral-50': {{ $state }} === 'locked',
        'border-neutral-200 bg-white shadow-e1': {{ $state }} === 'done' || {{ $state }} === 'idle',
    }"
>
    <h2>
        <button
            type="button"
            class="group flex w-full items-center gap-3 px-4 py-3 text-start transition
                   focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-primary
                   disabled:cursor-not-allowed"
            {{-- Corner radius follows the header: it is the whole card when the
                 section is collapsed, and only its top when it is open. --}}
            x-bind:class="[
                section === '{{ $name }}' ? 'rounded-t-surface' : 'rounded-surface',
                {{ $state }} === 'done' || {{ $state }} === 'idle' ? 'hover:bg-neutral-50' : '',
            ]"
            x-bind:disabled="!isUnlocked('{{ $name }}')"
            x-bind:aria-expanded="section === '{{ $name }}'"
            aria-controls="composer-section-{{ $name }}"
            x-on:click="openSection('{{ $name }}')"
        >
            {{-- Step marker. Doubles as the state indicator: numeral, tick, or padlock. --}}
            <span
                class="grid size-6 shrink-0 place-items-center rounded-round text-caption font-semibold transition-colors"
                x-bind:class="{
                    'bg-primary text-white': {{ $state }} === 'open' || {{ $state }} === 'done',
                    'bg-neutral-200 text-secondary-medium': {{ $state }} === 'locked' || {{ $state }} === 'idle',
                }"
                aria-hidden="true"
            >
                <template x-if="{{ $state }} === 'done'">
                    <svg class="size-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.75">
                        <path d="M4.5 10.5l3.5 3.5 7.5-8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </template>
                <template x-if="{{ $state }} === 'locked'">
                    <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 2a3.5 3.5 0 00-3.5 3.5V8H6a1.5 1.5 0 00-1.5 1.5v6A1.5 1.5 0 006 17h8a1.5 1.5 0 001.5-1.5v-6A1.5 1.5 0 0014 8h-.5V5.5A3.5 3.5 0 0010 2zm2 6H8V5.5a2 2 0 114 0V8z"/>
                    </svg>
                </template>
                <template x-if="{{ $state }} === 'open' || {{ $state }} === 'idle'">
                    <span>{{ $index }}</span>
                </template>
            </span>

            <span
                class="shrink-0 text-body font-semibold"
                x-bind:class="{{ $state }} === 'locked' ? 'text-secondary-light' : 'text-neutral-900'"
            >{{ $title }}</span>

            <span class="ms-auto flex min-w-0 items-center gap-3 text-small">
                <template x-if="{{ $state }} === 'locked'">
                    <span class="truncate text-secondary-light" x-text="lockedHintFor('{{ $name }}')"></span>
                </template>

                @if($hint)
                    <template x-if="{{ $state }} === 'open'">
                        <span class="hidden truncate text-secondary-light sm:inline">{{ $hint }}</span>
                    </template>
                @endif

                <template x-if="{{ $state }} === 'done'">
                    <span class="flex min-w-0 items-center gap-3">
                        <span class="truncate font-medium text-neutral-800" x-text="{{ $summary }}"></span>
                        {{-- Looks like the button it behaves as; the whole header is
                             the hit target, so this is an affordance, not a control. --}}
                        <span class="hidden shrink-0 items-center gap-1.5 rounded-control border
                                     border-neutral-300 bg-white px-2.5 py-1 text-caption font-semibold
                                     text-secondary-dark shadow-e1 transition
                                     group-hover:border-primary group-hover:text-primary sm:inline-flex"
                              aria-hidden="true">
                            <svg class="size-3" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M13.5 3.5l3 3L7 16H4v-3l9.5-9.5z" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            {{ __('general.edit') }}
                        </span>
                    </span>
                </template>
            </span>
        </button>
    </h2>

    <div
        id="composer-section-{{ $name }}"
        {{-- Plain x-show, not x-collapse: the collapse plugin pins
             overflow:hidden on this element, which clipped every dropdown. --}}
        x-show="section === '{{ $name }}'"
        x-cloak
    >
        <div class="border-t border-neutral-100 px-4 pb-5 pt-1">
            {{ $slot }}
        </div>
    </div>
</section>
