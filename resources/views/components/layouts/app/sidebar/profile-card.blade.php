@php use Mcamara\LaravelLocalization\Facades\LaravelLocalization; @endphp
@auth
    <div class="flex items-center cursor-pointer p-4"
         type="button"
         data-dropdown-toggle="userDropdown"
         data-dropdown-placement="top-start">

        <img class="size-10 rounded-full"
             src="https://flowbite.com/docs/images/people/profile-picture-2.jpg"
             alt="user photo">

        <div class="ms-4">
            <div class="text-sm font-semibold text-gray-900">
                {{ Auth::user()->profile?->name }}
            </div>


            @if(Auth::user()->hasMultipleRoles())
                <div class="mt-2">
                    <livewire:auth.role-switcher :wire:key="'role-switcher-inline'" />
                </div>
            @endif
        </div>
    </div>

    <div id="userDropdown"
         class="z-10 hidden bg-gray-100 divide-y divide-gray-100 rounded-lg shadow-md w-52">

        <div class="px-4 py-3 text-sm text-gray-900">
            <div>{{ Auth::user()->profile?->name }}</div>
            <div class="font-medium truncate">{{ Auth::user()->email }}</div>
        </div>

        <ul class="py-2 text-sm text-gray-700" aria-labelledby="avatarButton">
            <li>
                <a href="{{ route('settings.profile') }}"
                   class="block px-4 py-2 hover:bg-gray-200">
                    @lang('general.my-account')
                </a>
            </li>

            <li>
                @php
                    $currentLocale = app()->getLocale();
                    $switchTo = $currentLocale === 'ar' ? 'en' : 'ar';
                    $localizedURL = LaravelLocalization::getLocalizedURL($switchTo, null, [], true);
                @endphp
                <a href="{{ $localizedURL }}"
                   class="block px-4 py-2 hover:bg-gray-200 transition">
                    {{ $switchTo === 'ar' ? 'العربية' : 'English' }}
                </a>
            </li>
        </ul>

        @if(Auth::user()->hasMultipleRoles())
            <div class="px-4 py-3">
                <div class="text-xs text-gray-500 mb-2">{{ __('Switch Role') }}</div>
                <livewire:auth.role-switcher :wire:key="'role-switcher-dropdown'" />
            </div>
        @endif

        {{-- Logout --}}
        <div class="py-1 px-4">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="block w-full text-start py-2 text-sm text-gray-700 hover:bg-gray-200">
                    @lang('auth.logout')
                </button>
            </form>
        </div>
    </div>
@endauth

@guest
    <div class="p-4 space-y-2">
        <a href="{{ route('login') }}"
           class="block w-full text-center py-2 px-4 text-sm font-semibold text-white bg-primary hover:opacity-90 rounded-md">
            @lang('auth.login')
        </a>
        <a href="{{ route('register') }}"
           class="block w-full text-center py-2 px-4 text-sm font-semibold text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md">
            @lang('auth.register')
        </a>
    </div>
@endguest
