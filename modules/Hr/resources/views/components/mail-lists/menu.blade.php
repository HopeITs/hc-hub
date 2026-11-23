@props(['suggested' => null, 'people' => [], 'peopleLabel' => null, 'label' => null, 'align' => 'end'])

{{--
    "Email a list": a menu that opens a new message to the list chosen (nothing
    is sent from here). Every list of the organisation is offered, grouped, with
    a search; the lists suggested for a place or project come first, and a few
    addresses can be offered as one more choice ("these people, directly").

      <x-hr::mail-lists.menu :suggested="$lists" :people="$emails" :people-label="…" />
--}}
@php
    $suggested = collect($suggested);
    $people = array_values(array_filter((array) $people));
    $groups = \Modules\Hr\Models\MailList::menuGroups();
    $plain = fn (string $address): string => str_replace('%40', '@', rawurlencode($address));
    $peopleHref = $people ? 'mailto:'.implode(',', array_map($plain, $people)) : null;
    // A link longer than about 2000 characters is refused by the mail program, so a team too big for one is not offered.
    $peopleHref = $peopleHref !== null && strlen($peopleHref) <= 1900 ? $peopleHref : null;
    $item = 'flex flex-col px-3 py-2 text-start transition hover:bg-zinc-100 dark:hover:bg-white/[0.06]';
    $heading = 'px-3 pt-2 text-xs font-bold uppercase tracking-wide text-secondary-medium dark:text-ink-muted';
@endphp

@if ($groups->isNotEmpty() || $peopleHref !== null)
    <div class="relative" x-data="{ open: false, q: '' }" x-on:keydown.escape.window="open = false" x-on:click.outside="open = false" data-mail-list-menu>
        <x-ui.button type="button" icon="envelope" icon:trailing="chevron-down" x-on:click="open = ! open; if (open) $nextTick(() => $refs.q.focus())" x-bind:aria-expanded="open" aria-haspopup="true">{{ $label ?? __('hr_mail_lists.email_a_list') }}</x-ui.button>

        <div x-show="open" x-cloak
             class="absolute z-30 mt-1 w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-white/10 dark:bg-zinc-900 {{ $align === 'end' ? 'end-0' : 'start-0' }}">
            <div class="border-b border-zinc-200 p-2 dark:border-white/10">
                <input type="search" x-ref="q" x-model="q" dir="auto" placeholder="{{ __('hr_mail_lists.menu_search') }}" aria-label="{{ __('hr_mail_lists.menu_search') }}"
                       class="block w-full rounded-md border border-zinc-200 bg-transparent px-3 py-2 text-sm dark:border-white/10">
            </div>

            <div class="max-h-64 overflow-y-auto py-1">
                @if ($peopleHref !== null)
                    <a href="{{ $peopleHref }}" data-mail-people x-show="! q" class="{{ $item }}">
                        <span class="text-sm font-semibold">{{ $peopleLabel ?? __('hr_mail_lists.menu_people') }}</span>
                        <span class="text-xs text-secondary-medium dark:text-ink-muted">{{ trans_choice('hr_mail_lists.menu_people_count', count($people), ['count' => count($people)]) }}</span>
                    </a>
                @endif

                @if ($suggested->isNotEmpty())
                    <p class="{{ $heading }}" x-show="! q">{{ __('hr_mail_lists.menu_suggested') }}</p>
                    @foreach ($suggested as $list)
                        <a href="{{ $list->mailto() }}" data-mail-list-suggested x-show="! q" class="{{ $item }}">
                            <span class="text-sm font-semibold"><bdi>{{ $list->name }}</bdi></span>
                            <span class="text-xs text-secondary-medium dark:text-ink-muted" dir="ltr">{{ $list->address }}</span>
                        </a>
                    @endforeach
                @endif

                @foreach ($groups as $category => $lists)
                    <p class="{{ $heading }}" x-show="! q">{{ __('hr_mail_lists.cat.'.$category) }}</p>
                    @foreach ($lists as $list)
                        <a href="{{ $list->mailto() }}" class="{{ $item }}"
                           x-show="! q || {{ \Illuminate\Support\Js::from(\Illuminate\Support\Str::lower($list->name.' '.$list->address)) }}.includes(q.toLowerCase())">
                            <span class="text-sm font-semibold"><bdi>{{ $list->name }}</bdi></span>
                            <span class="text-xs text-secondary-medium dark:text-ink-muted" dir="ltr">{{ $list->address }}</span>
                        </a>
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>
@endif
