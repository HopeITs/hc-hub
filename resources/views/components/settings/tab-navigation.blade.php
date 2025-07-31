@props(['tabs'])

<div class="space-y-2">
    @foreach ($tabs as $tab)
        <a href="{{ route($tab['route']) }}"
           class="block text-center px-4 py-2 rounded-md text-sm font-medium transition
           {{ request()->routeIs($tab['route'])
               ? 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white font-semibold'
               : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
            {{ $tab['label'] }}
        </a>
    @endforeach
</div>
